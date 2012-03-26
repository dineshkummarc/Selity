#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010 - 2012 by internet Multi Server Control Panel
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
# @category		i-MSCP
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

#####################################################################################
# Package description:
#
# This package provides a class that is responsible to install all dependencies
# (libraries, tools and softwares) required by i-MSCP on Debian like operating systems.
#

package library::debian_autoinstall;

use strict;
use warnings;

use Selity::Debug;
use Symbol;
use Selity::Execute qw/execute/;
use Selity::Dialog;

use vars qw/@ISA/;
@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

# Initializer.
#
# @param self $self Selity::debian_autoinstall instance
# @return int 0
sub _init {
	debug('Starting...');

	my $self = shift;

	$self->{nonfree} = 'non-free';

	debug('Ending...');
	0;
}

# Process pre-build tasks.
#
# @param self $self Selity::debian_autoinstall instance
# @return int 0 on success, other on failure
sub preBuild {
	debug('Starting...');

	my $self = shift;
	my $rs;

	#$rs = $self->updateSystemPackagesIndex();
	return $rs if $rs;

	$rs = $self->preRequish();
	return $rs if $rs;

	$self->loadOldSelityConfigFile();

	$rs = $self->UpdateAptSourceList();
	return $rs if $rs;

	do{

		$rs = $self->readPackagesList();

	} while ($rs == -1);

	return $rs if $rs;

	$rs = $self->removeNotNeeded();
	return $rs if $rs;

	$rs = $self->installPackagesList();
	return $rs if $rs;

	debug('Ending...');
	0;
}

# Updates system packages index from remote repository.
#
# @return int 0 on success, other on failure
sub updateSystemPackagesIndex {
	debug('Starting...');

	Selity::Dialog->factory()->infobox('Updating system packages index');

	my ($rs, $stdout, $stderr);

	$rs = execute('apt-get update', \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	error('Unable to update package index from remote repository') if $rs && !$stderr;
	return $rs if $rs;

	debug('Ending...');
	0;
}

# Installs pre-required packages.
#
# @param self $self Selity::debian_autoinstall instance
# @return int 0 on success, other on failure
sub preRequish {
	debug('Starting...');

	my $self = shift;

	Selity::Dialog->factory()->infobox('Installing pre-required packages');

	my($rs, $stderr);

	$rs = execute('apt-get -y install dialog libxml-simple-perl', undef, \$stderr);
	error("$stderr") if $stderr;
	error('Unable to install pre-required packages.') if $rs && ! $stderr;
	return $rs if $rs;

	# Force dialog now
	Selity::Dialog->reset();

	debug('Ending...');
	0;
}

# Load old i-MSCP main configuration file.
#
# @return int 0
sub loadOldSelityConfigFile {

	debug('Starting...');

	use Selity::Config;

	$main::selityConfigOld = {};

	my $oldConf = "$main::defaultConf{'CONF_DIR'}/selity.old.conf";

	tie %main::selityConfigOld, 'Selity::Config', 'fileName' => $oldConf, noerrors => 1 if (-f $oldConf);

	debug('Ending...');
	0;
}

# Process apt source list.
#
# This subroutine parse the apt source list file to ensure presence of the non-free
# packages availability. If non-free section is not already enabled, this method try
# to find in on the remote repository and add it to the current Debian repository URI.
#
# @param self $self Selity::debian_autoinstall instance
# @return int 0 on success, other on failure
sub UpdateAptSourceList {
	debug('Starting...');

	my $self = shift;

	use Selity::File;

	Selity::Dialog->factory()->infobox('Processing apt sources list');

	my $file = Selity::File->new(filename => '/etc/apt/sources.list');

	$file->copyFile('/etc/apt/sources.list.bkp') unless( -f '/etc/apt/sources.list.bkp');
	my $content = $file->get();

	unless ($content){
		error('Unable to read /etc/apt/sources.list file');
		return 1;
	}

	my ($foundNonFree, $needUpdate, $rs, $stdout, $stderr);

	while($content =~ /^deb\s+(?<uri>(?:https?|ftp)[^\s]+)\s+(?<distrib>[^\s]+)\s+(?<components>.+)$/mg){
		my %repos = %+;

		# is non-free repository available?
		unless($repos{'components'} =~ /\s?$self->{nonfree}(\s|$)/ ){
			my $uri = "$repos{uri}/dists/$repos{distrib}/$self->{nonfree}/";
			$rs = execute("wget --spider $uri", \$stdout, \$stderr);
			debug("$stdout") if $stdout;
			debug("$stderr") if $stderr;

			unless ($rs){
				$foundNonFree = 1;
				debug("Enabling non free section on $repos{uri}");
				$content =~ s/^($&)$/$1 $self->{nonfree}/mg;
				$needUpdate = 1;
			}
		} else {
			debug("Non free section is already enabled on $repos{uri}");
			$foundNonFree = 1;
		}

	}

	unless($foundNonFree){
		error('Unable to found repository that support non-free packages');
		return 1;
	}

	if($needUpdate){
		$file->set($content);
		$file->save() and return 1;

		$rs = $self->updateSystemPackagesIndex();
		return $rs if $rs;
	}

	debug('Ending...');
	0;
}

# Reads packages list to be installed.
#
# @param self $self Selity::debian_autoinstall instance
# @return int 0 on success, other on failure
sub readPackagesList {
	debug('Starting...');

	my $self = shift;
	my $SO = Selity::SO->new();
	my $confile = "$FindBin::Bin/docs/" . ucfirst($SO->{Distribution}) . "/" .
		lc($SO->{Distribution}) . "-packages-" . lc($SO->{CodeName}) . ".xml";

	fatal(ucfirst($SO->{Distribution})." $SO->{CodeName} is not supported!") if (! -f  $confile);

	eval "use XML::Simple";

	fatal('Unable to load perl module XML::Simple...') if($@);

	my $xml = XML::Simple->new(NoEscape => 1, SuppressEmpty => 1);
	my $data = eval { $xml->XMLin($confile, KeyAttr => 'name') };

	my %alternatives;
	$self->{install} = '';
	$self->{require_server} = '';
	$self->{remove} = '';

	foreach (keys %$data) {
		if (exists $data->{$_}->{section}){
			push(@{$alternatives{$data->{$_}->{section}}}, $_);
		} else {
			$self->{install} .= ' '.$data->{$_}->{install} if(exists $data->{$_}->{install});
			$self->{require_server} .= ' '.$data->{$_}->{require_server} if(exists $data->{$_}->{require_server});
		}
	}

	foreach(keys %alternatives){
		my $rs;

		for (my $index = $#{$alternatives{$_}}; $index >= 0; --$index ){
			my $defServer = @{$alternatives{$_}}[$index];
			my $oldServer = $main::selityConfigOld{uc($_) . '_SERVER'};

			if($@){
				error("$@");
				return 1;
			}

			if($oldServer && $defServer eq $oldServer){
				splice @{$alternatives{$_}}, $index, 1 ;
				unshift @{$alternatives{$_}}, $defServer;
				last;
			}
		}

		do{
			$rs = Selity::Dialog->factory()->radiolist(
					"Choose server $_",
					@{$alternatives{$_}},
					'Not Used'
				);
		} while (!$rs);

		if(lc($rs) ne 'not used'){
			$self->{install} .= ' '.$data->{$rs}->{install} if(exists $data->{$rs}->{install});
			$self->{require_server} .= ' '.$data->{$rs}->{require_server} if(exists $data->{$rs}->{require_server});
		}

		$self->{userSelection}->{$_} = lc($rs) eq 'not used' ? 'no' : $rs;
	}

	$self->{install} = _clean($self->{install});
	$self->{require_server} = _clean($self->{require_server});

	foreach(keys %{$self->{userSelection}}){
		next unless $data->{$self->{userSelection}->{$_}}->{remove};;
		foreach(split(' ',$data->{$self->{userSelection}->{$_}}->{remove})){
			$self->{remove} .= ' '.$data->{$_}->{install} if(exists $data->{$_}->{install});
		}
	}
	$self->{remove} = _clean($self->{remove});

	foreach(split(" ", $self->{require_server})){
		next unless $_;
		unless( exists $self->{userSelection}->{$_} && $self->{userSelection}->{$_} ne 'no') {
			Selity::Dialog->factory()->msgbox("Following selection is not valid, require $_ server but was not selected");
			return -1;
		}
	}

	debug('Ending...');
	0;
}

# Install Debian packages list required by i-MSCP.
#
# @param self $self Selity::debian_autoinstall instance
# @return in 0 on success, other on failure
sub installPackagesList {
	debug('Starting...');

	my $self = shift;

	Selity::Dialog->factory()->infobox('Installing needed packages');

	my($rs, $stderr);

	$rs = execute("apt-get -f -y install $self->{install}", undef, \$stderr);
	error("$stderr") if $stderr && $rs;
	error('Can not install packages.') if $rs && ! $stderr;
	return $rs if $rs;

	debug('Ending...');
	0;
}

# Remove Debian packages that might conflict with those required by i-MSCP.
#
# @param self $self Selity::debian_autoinstall instance
# @return in 0 on success, other on failure
sub removeNotNeeded {
	debug('Starting...');

	my $self = shift;

	return 0 unless $self->{remove};

	Selity::Dialog->factory()->infobox('Removing needed packages');

	my($rs, $stderr);

	$rs = execute("dpkg -r --force-depends $self->{remove}", undef, \$stderr);
	error("$stderr") if $stderr && $rs;
	error('Can not remove packages.') if $rs && ! $stderr;
	return $rs if $rs;

	debug('Ending...');
	0;
}

# Perfomr post-build tasks.
#
# @param self $self Selity::debian_autoinstall instance
# @return in 0 on success, other on failure
sub postBuild {
	debug('Starting...');

	my $self = shift;

	my $x = qualify_to_ref("SYSTEM_CONF", 'main');

	my $nextConf = $$$x . '/selity.conf';
	tie %main::nextConf, 'Selity::Config', 'fileName' => $nextConf;

	$main::nextConf{uc($_) . "_SERVER"} = lc($self->{userSelection}->{$_}) foreach(keys %{$self->{userSelection}});

	debug('Ending...');
	0;
}

# Trim a string.
#
# @access private
# @param string $var String to be trimmed
# @return string
sub _trim {
	my $var = shift;
	$var =~ s/^\s+//;
	$var =~ s/\s+$//;
	$var;
}

# Clean a string.
#
# @access private
# @param string $var String to be trimmed
# @return string
sub _clean {
	my $var = shift;
	$var =~ s/\n+//mg;
	$var =~ s/\t+/ /mg;
	$var =~ s/\s+/ /mg;
	$var;
}

# Parse hash.
#
# @access private
# @param self $self Selity::debian_autoinstall instance
# @param HASH $hash Hash to be parsed
# @return void
sub _parseHash {
	my $self = shift;
	my $hash = shift;
	my $rv = '';

	foreach(values %{$hash}) {
		if(ref($_) eq 'HASH') {
			$self->_parseHash($_);
		} elsif(ref($_) eq 'ARRAY') {
			$self->_parseArray($_);
		} else {
			$rv .= " " . _trim($_);
		}
	}
	$rv;
}

# Parse array
#
# @access private
# @param self $self Selity::debian_autoinstall instance
# @param ARRAY $array Array to be parsed
# @return void
sub _parseArray {
	my $self = shift;
	my $array = shift;
	my $rv = '';

	foreach(@{$array}){
		if(ref($_) eq 'HASH') {
			$self->_parseHash($_);
		}elsif(ref($_) eq 'ARRAY') {
			$self->_parseArray($_);
		} else {
			$rv .= " " . _trim($_);
		}
	}
	$rv;
}

1;
