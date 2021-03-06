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

package Addons::awstats;

use strict;
use warnings;
use Data::Dumper;
use Selity::Debug;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{

	my $self				= shift;

	$self->{cfgDir}	= "$main::selityConfig{'CONF_DIR'}/awstats";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";
	$self->{tplDir}	= "$self->{cfgDir}/parts";

	0;
}

sub factory{ return Addons::awstats->new(); }

sub preinstall{

	use Addons::awstats::installer;

	my $self	= shift;
	my $rs		= 0;

	$rs |= Addons::awstats::installer->new()->registerHooks();

	$rs;
}

sub install{

	use Addons::awstats::installer;

	my $self	= shift;
	my $rs		= 0;
	Addons::awstats::installer->new()->install();

	$rs;
}

sub preaddDmn{

	use Servers::httpd;

	my $self	= shift;
	my $rs		= 0;
	my $httpd = Servers::httpd->factory();

	my $rs |= $httpd->registerPreHook(
		'buildConf', sub { return $self->awstatsSection(@_); }
	) if $httpd->can('registerPreHook');

	$rs;
}

sub preaddSub{

	use Servers::httpd;

	my $self	= shift;
	my $rs		= 0;
	my $httpd = Servers::httpd->factory();

	$rs |= $httpd->registerPreHook(
		'buildConf', sub { return $self->delAwstatsSection(@_); }
	) if $httpd->can('registerPreHook');

	$rs;
}

sub delAwstatsSection{

	use Selity::Templator;
	use Servers::httpd;

	my $self = shift;
	my $data = shift;
	my $filename = shift;

	if($filename =~ /domain-.*.tpl/){
		my $bTag = "# SECTION awstats support BEGIN.\n";
		my $eTag = "# SECTION awstats support END.\n";
		$data = replaceBloc($bTag, $eTag, '', $data, undef);

	}

	#register again for next file
	my $httpd = Servers::httpd->factory();
	my $rs = $httpd->registerPreHook(
		'buildConf', sub { return $self->delAwstatsSection(@_); }
	) if $httpd->can('registerPreHook');

	$data;
}

sub awstatsSection{

	use Selity::Templator;
	use Servers::httpd;

	my $self = shift;
	my $data = shift;
	my $filename = shift;


	if($filename =~ /domain-.*.tpl/){
		my ($bTag, $eTag);
		if($main::selityConfig{AWSTATS_ACTIVE} ne 'yes'){
			$bTag = "# SECTION awstats support BEGIN.\n";
			$eTag = "# SECTION awstats support END.\n";
		} elsif($main::selityConfig{AWSTATS_MODE} ne '1'){
			$bTag = "# SECTION awstats static BEGIN.\n";
			$eTag = "# SECTION awstats static END.\n";
		} else {
			$bTag = "# SECTION awstats dynamic BEGIN.\n";
			$eTag = "# SECTION awstats dynamic END.\n";
		}
		$data = replaceBloc($bTag, $eTag, '', $data, undef);
		my $tags = {
			AWSTATS_CACHE_DIR	=> $main::selityConfig{AWSTATS_CACHE_DIR},
			AWSTATS_CONFIG_DIR	=> $main::selityConfig{AWSTATS_CONFIG_DIR},
			AWSTATS_ENGINE_DIR	=> $main::selityConfig{AWSTATS_ENGINE_DIR},
			AWSTATS_WEB_DIR		=> $main::selityConfig{AWSTATS_WEB_DIR},
			AWSTATS_ROOT_DIR	=> $main::selityConfig{AWSTATS_ROOT_DIR},
			AWSTATS_GROUP_AUTH	=> $main::selityConfig{AWSTATS_GROUP_AUTH}
		};
		$data = process($tags, $data);

	}
	my $httpd = Servers::httpd->factory();
	my $rs = $httpd->registerPreHook(
		'buildConf', sub { return $self->awstatsSection(@_); }
	) if $httpd->can('registerPreHook');

	$data;
}

sub addDmn{

	my $self	= shift;
	my $data	= shift;
	my $rs		= 0;

	local $Data::Dumper::Terse = 1;
	debug("Data: ". (Dumper $data));

	my $errmsg = {
		'DMN_NAME'	=> 'You must supply domain name!',
		'HOME_DIR'	=> 'You must supply user home path!',
		'USER'		=> 'You must supply user name!',
		'GROUP'		=> 'You must supply group name!',
	};

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	$rs |= Selity::Dir->new(
		dirname => "/$data->{HOME_DIR}/statistics"
	)->make({
			mode	=> 0755,
			user	=> $data->{USER},
			group	=> $data->{GROUP}
	}) if $main::selityConfig{AWSTATS_MODE};

	if($main::selityConfig{AWSTATS_ACTIVE} =~ m/yes/i){
		$rs |= $self->addAwstatsCfg($data);
		$rs |= $self->addAwstatsCron($data) if $main::selityConfig{AWSTATS_MODE};
	}
	$rs;
}

sub addAwstatsCfg{

	use Selity::File;
	use Selity::Templator;
	use Servers::httpd;

	my $self	= shift;
	my $data	= shift;
	my $rs		= 0;

	my $cfgFileName	= "awstats.$data->{DMN_NAME}.conf";

	my $cfgFile	= "$main::selityConfig{AWSTATS_CONFIG_DIR}/$cfgFileName";
	my $tplFile	= "$self->{tplDir}/awstats.selity_tpl.conf";
	my $wrkFile	= "$self->{wrkDir}/$cfgFileName";

	my $cfgFileContent	= Selity::File->new(filename => $tplFile)->get();

	#Saving the current production file if it exists
	$rs |=	Selity::File->new(
				filename => $cfgFile
			)->copyFile(
				"$self->{bkpDir}/$cfgFileName." . time
			) if(-f $cfgFile);

	# Load template file
	if(!$cfgFileContent){
		error("Can not load $tplFile");
		return 1;
	}

	my $tags = {
		DOMAIN_NAME			=> $data->{DMN_NAME},
		CMD_CAT				=> $main::selityConfig{CMD_CAT},
		AWSTATS_CACHE_DIR	=> $main::selityConfig{AWSTATS_CACHE_DIR},
		AWSTATS_ENGINE_DIR	=> $main::selityConfig{AWSTATS_ENGINE_DIR},
		AWSTATS_WEB_DIR		=> $main::selityConfig{AWSTATS_WEB_DIR}
	};
	$cfgFileContent = process($tags, $cfgFileContent);

	my $httpd = Servers::httpd->factory();
	$cfgFileContent = $httpd->buildConf($cfgFileContent);

	if(!$cfgFileContent){
		error("Error while building $cfgFile");
		return 1;
	}

	## Store and install
	# Store the file in the working directory
	my $file = Selity::File->new(filename => $wrkFile);
	$rs |= $file->set($cfgFileContent);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::selityConfig{'ROOT_USER'}, $main::selityConfig{'ROOT_GROUP'});

	# Install the file in the production directory
	$rs |= $file->copyFile($main::selityConfig{AWSTATS_CONFIG_DIR});

	$rs;
}

sub addAwstatsCron{

	use Selity::File;
	use Selity::Templator;
	use Servers::cron;

	my $self	= shift;
	my $data	= shift;
	my $rs		= 0;

	my $cron = Servers::cron->factory();
	$rs = $cron->addTask({
		MINUTE	=> int(rand(61)),	#random number between 0..60
		HOUR	=> int(rand(6)),	#random number between 0..5
		DAY		=> '*',
		MONTH	=> '*',
		DWEEK	=> '*',
		USER	=> $data->{USER},
		C0MMAND	=>	"perl $main::selityConfig{AWSTATS_ROOT_DIR}/awstats_buildstaticpages.pl ".
					"-config=$data->{DMN_NAME} -update ".
					"-awstatsprog=$main::selityConfig{AWSTATS_ENGINE_DIR}/awstats.pl ".
					"-dir=$data->{HOME_DIR}/statistics/",
		TASKID	=> "AWSTATS:$data->{DMN_NAME}"
	});

	$rs;
}

sub delDmn{

	my $self	= shift;
	my $data	= shift;
	my $rs		= 0;

	local $Data::Dumper::Terse = 1;
	debug("Data: ". (Dumper $data));

	my $errmsg = {
		'DMN_NAME'	=> 'You must supply domain name!',
		'HOME_DIR'	=> 'You must supply user home path!',
		'USER'		=> 'You must supply user name!',
	};

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	my $cfgFileName = "$main::selityConfig{AWSTATS_CONFIG_DIR}/awstats.$data->{DMN_NAME}.conf";
	my $wrkFileName = "$self->{wrkDir}/awstats.$data->{DMN_NAME}.conf";
	$rs |= Selity::File->new(filename => $cfgFileName)->delFile() if -f $cfgFileName;
	$rs |= Selity::File->new(filename => $wrkFileName)->delFile() if -f $wrkFileName;
	$rs |= $self->delAwstatsCron($data);

	$rs;
}

sub delAwstatsCron{

	use Servers::cron;

	my $self	= shift;
	my $data	= shift;
	my $rs		= 0;

	my $cron = Servers::cron->factory();
	$rs = $cron->delTask({
		TASKID	=> "AWSTATS:$data->{DMN_NAME}"
	});

	$rs;
}


1;
