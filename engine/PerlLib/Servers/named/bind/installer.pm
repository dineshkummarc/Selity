#!/usr/bin/perl

# Selity - multiserver hosting control panel
# Copyright 2012 by Selity
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category		Selity
# @copyright	2012 by Selity | http://selity.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @link			http://selity.net Selity Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::named::bind::installer;

use strict;
use warnings;
use Selity::Debug;


use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{

	my $self		= shift;

	$self->{cfgDir}	= "$main::selityConfig{'CONF_DIR'}/bind";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	my $conf		= "$self->{cfgDir}/bind.data";
	my $oldConf		= "$self->{cfgDir}/bind.old.data";

	tie %self::bindConfig, 'Selity::Config','fileName' => $conf;
	tie %self::bindOldConfig, 'Selity::Config','fileName' => $oldConf, noerrors => 1 if -f $oldConf;

	0;
}

sub buildConf{

	use Selity::File;

	my $self		= shift;
	my ($rs, $rdata, $cfgTpl, $cfg, $err);

	## Building new configuration file

	# Loading the system main configuration file named.conf.system if it exists
	if(-f "$self->{bkpDir}/named.conf.system") {
		$cfg = Selity::File->new(filename => "$self->{bkpDir}/named.conf.system")->get();
		return 1 if(!$cfg);

		# Adjusting the configuration if needed
		$cfg =~ s/listen-on ((.*) )?{ 127.0.0.1; };/listen-on $1 { any; };/;
		$cfg .= "\n";
	} else {
		warning("Can't find the default distribution file for named...");
		$cfg = '';
	}

	# Loading the template from /etc/selity/bind/named.conf
	$cfgTpl = Selity::File->new(filename => "$self->{cfgDir}/named.conf")->get();
	return 1 if(!$cfgTpl);

	# Building new file
	$cfg .= $cfgTpl;

	## Storage and installation of new file

	# Storing new file in the working directory
	my $file = Selity::File->new(filename => "$self->{wrkDir}/named.conf");
	$file->set($cfg) and return 1;
	$file->save() and return 1;
	$file->owner($main::selityConfig{'ROOT_USER'}, $main::selityConfig{'ROOT_GROUP'}) and return 1;
	$file->mode(0644) and return 1;

	# Install the new file in the production directory
	$file->copyFile($self::bindConfig{'BIND_CONF_FILE'}) and return 1;

	0;
}

sub install{

	my $self	= shift;
	my $rs		= 0;

	# Saving all system configuration files if they exists
	for ((
		$self::bindConfig{'BIND_CONF_FILE'}
	)) {
		$rs |= $self->bkpConfFile($_) and return 1;
	}

	$rs |= $self->buildConf();
	$rs |= $self->askMode();
	$rs |= $self->addMasterZone();
	$rs |= $self->saveConf();

	$rs;
}

sub addMasterZone{

	use Servers::named;

	my $self	= shift;
	my $named	= Servers::named->factory();

	my $rs = $named->addDmn({
		DMN_NAME	=> $main::selityConfig{BASE_SERVER_VHOST},
		DMN_IP		=> $main::selityConfig{BASE_SERVER_IP},
		MX			=> ''
	});
	return $rs if $rs;

	0;
}
sub askMode{

	use Selity::Dialog;

	my $self	= shift;
	my $ip		= Selity::IP->new();
	my @ips		= ();

	$self::bindConfig{'BIND_MODE'} = $self::bindOldConfig{'BIND_MODE'}
		if $self::bindOldConfig{'BIND_MODE'} && $self::bindConfig{'BIND_MODE'} ne $self::bindOldConfig{'BIND_MODE'};

	@ips = (@ips, split(';', $self::bindConfig{PRIMARY_DNS}))
		if $self::bindConfig{PRIMARY_DNS};
	@ips = (@ips, split(';', $self::bindOldConfig{PRIMARY_DNS}))
		if $self::bindOldConfig{PRIMARY_DNS};
	@ips = (@ips, split(';', $self::bindConfig{SECONDARY_DNS}))
		if $self::bindConfig{SECONDARY_DNS} && $self::bindConfig{SECONDARY_DNS} ne 'no';
	@ips = (@ips, split(';', $self::bindOldConfig{SECONDARY_DNS}))
		if $self::bindOldConfig{SECONDARY_DNS} && $self::bindOldConfig{SECONDARY_DNS} ne 'no';

	if($self::bindConfig{'BIND_MODE'} eq 'slave' && !scalar @ips){
		push(@ips, 'wrongip');
	}

	foreach(@ips){
		if($_ && !$ip->isValidIp($_)){
			debug("$_ is invalid ip");
			for(qw/BIND_MODE PRIMARY_DNS SECONDARY_DNS/){
				$self::bindConfig{$_}		= undef;
				$self::bindOldConfig{$_}	= undef;
			}
			last;
		}
	}

	if($self::bindConfig{'BIND_MODE'}){
		return 0;
	}

	my $out;
	while (! ($out = Selity::Dialog->factory()->radiolist("Select bind mode", 'master', 'slave'))){}
	$self::bindConfig{'BIND_MODE'} = $out;

	$self->askOtherDNS();

	0;
}

sub askOtherDNS{


	use Selity::Dialog;

	my $self = shift;
	my $out;

	if($self::bindConfig{'BIND_MODE'} eq 'master'){
		while (! ($out = Selity::Dialog->factory()->radiolist("Enable secondary DNS server address IP?", 'no', 'yes'))){}
		if($out eq 'no'){
			$self::bindConfig{'SECONDARY_DNS'} = 'no';
			return 0;
		}
	}

	use Selity::IP;
	my $ip = Selity::IP->new();

	my $mode = $self::bindConfig{'BIND_MODE'} eq 'primary' ? 'secondary' : 'primary';

	my @ips = ();

	do{
		$out = Selity::Dialog->factory()->inputbox(
			"Please enter $mode DNS server address IP. Leave blank for end"
		);
		push(@ips, $out) if $ip->isValidIp($out) && $out ne '127.0.0.1';
	}while(scalar @ips == 0 || $out ne '');

	$self::bindConfig{
			$self::bindConfig{'BIND_MODE'} eq 'master'
			?
			'SECONDARY_DNS'
			:
			'PRIMARY_DNS'
	} = join (';', @ips);

	0;
}

sub bkpConfFile{

	use File::Basename;

	my $self		= shift;
	my $cfgFile		= shift;
	my $timestamp	= time;

	if(-f $cfgFile){
		my $file	= Selity::File->new( filename => $cfgFile );
		my ($filename, $directories, $suffix) = fileparse($cfgFile);
		if(!-f "$self->{bkpDir}/$filename$suffix.system") {
			$file->copyFile("$self->{bkpDir}/$filename$suffix.system") and return 1;
		} else {
			$file->copyFile("$self->{bkpDir}/$filename$suffix.$timestamp") and return 1;
		}
	}

	0;
}

sub saveConf{

	use Selity::File;

	my $self	= shift;
	my $rs		= 0;
	my $file	= Selity::File->new(filename => "$self->{cfgDir}/bind.data");

	$self::bindConfig{'PRIMARY_DNS'} = $self::bindOldConfig{'PRIMARY_DNS'}
		if $self::bindOldConfig{'PRIMARY_DNS'} && $self::bindConfig{'PRIMARY_DNS'} ne $self::bindOldConfig{'PRIMARY_DNS'};

	$self::bindConfig{'SECONDARY_DNS'} = $self::bindOldConfig{'SECONDARY_DNS'}
		if $self::bindOldConfig{'SECONDARY_DNS'} && $self::bindConfig{'SECONDARY_DNS'} ne $self::bindOldConfig{'SECONDARY_DNS'};

	my $cfg		= $file->get() or return 1;

	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::selityConfig{'ROOT_USER'}, $main::selityConfig{'ROOT_GROUP'});

	$file = Selity::File->new(filename => "$self->{cfgDir}/bind.old.data");
	$rs |= $file->set($cfg);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::selityConfig{'ROOT_USER'}, $main::selityConfig{'ROOT_GROUP'});

	$rs;
}

1;
