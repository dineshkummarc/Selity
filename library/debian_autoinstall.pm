#!/usr/bin/perl

# Selity - When virtual hosting becomes scalable
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

sub _init {

	my $self = shift;

	$self->{nonfree} = 'non-free';

	0;
}

sub preBuild {

	my $self = shift;
	my $rs;

	$rs = $self->preRequish();
	return $rs if $rs;

	$self->loadOldSelityConfigFile();

	do{

		$rs = $self->readPackagesList();

	} while ($rs == -1);

	return $rs if $rs;

	$rs = $self->removeNotNeeded();
	return $rs if $rs;

	$rs = $self->installPackagesList();
	return $rs if $rs;

	0;
}

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

sub loadOldSelityConfigFile {

	debug('Starting...');

	use Selity::Config;

	$main::selityConfigOld = {};

	my $oldConf = "$main::defaultConf{'CONF_DIR'}/selity.old.conf";

	tie %main::selityConfigOld, 'Selity::Config', 'fileName' => $oldConf, noerrors => 1 if (-f $oldConf);

	debug('Ending...');
	0;
}

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

sub _trim {
	my $var = shift;
	$var =~ s/^\s+//;
	$var =~ s/\s+$//;
	$var;
}

sub _clean {
	my $var = shift;
	$var =~ s/\n+//mg;
	$var =~ s/\t+/ /mg;
	$var =~ s/\s+/ /mg;
	$var;
}

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
