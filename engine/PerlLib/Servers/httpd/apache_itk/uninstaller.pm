#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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

package Servers::httpd::apache_itk::uninstaller;

use strict;
use warnings;
use Selity::Debug;
use Data::Dumper;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{

	my $self		= shift;

	$self->{cfgDir}	= "$main::selityConfig{'CONF_DIR'}/apache";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	my $conf		= "$self->{cfgDir}/apache.data";

	tie %self::apacheConfig, 'Selity::Config','fileName' => $conf;

	0;
}

sub uninstall{

	my $self = shift;
	my $rs = 0;

	$rs |= $self->removeUsers();
	$rs |= $self->removeDirs();
	$rs |= $self->vHostConf();
	$rs |= $self->restoreConf();

	$rs;
}

sub removeUsers{

	my $self = shift;
	my $rs = 0;
	my ($panelGName, $panelUName);

	## Panel user
	use Modules::SystemUser;
	$panelUName = Modules::SystemUser->new();
	$panelUName->{force} = 'yes';
	$rs |= $panelUName->delSystemUser($main::selityConfig{'SYSTEM_USER_PREFIX'}.$main::selityConfig{'SYSTEM_USER_MIN_UID'});

	# Panel group
	use Modules::SystemGroup;
	$panelGName = Modules::SystemGroup->new();
	$rs |= $panelGName->delSystemGroup($main::selityConfig{'SYSTEM_USER_PREFIX'}.$main::selityConfig{'SYSTEM_USER_MIN_UID'});

	$rs;
}

sub removeDirs{

	use Selity::Dir;

	my $rs			= 0;
	my $self		= shift;
	my $phpdir		= $self::apacheConfig{'PHP_STARTER_DIR'};

	for (
		$self::apacheConfig{'APACHE_USERS_LOG_DIR'},
		$self::apacheConfig{'APACHE_BACKUP_LOG_DIR'},
		$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'},
		$phpdir
	) {
		$rs |= Selity::Dir->new(dirname => $_)->remove() if -d $_;
	}

	$rs;
}

sub restoreConf{

	use File::Basename;

	my $self		= shift;
	my $rs			= 0;

	for ((
		"$main::selityConfig{LOGROTATE_CONF_DIR}/apache2",
		"$main::selityConfig{LOGROTATE_CONF_DIR}/apache",
		"$self::apacheConfig{APACHE_CONF_DIR}/ports.conf"
	)) {
		my ($filename, $directories, $suffix) = fileparse($_);
		$rs	=	Selity::File->new(
					filename => "$self->{bkpDir}/$filename$suffix.system"
				)->copyFile($_)
				if(-f "$self->{bkpDir}/$filename$suffix.system");
	}

	$rs;
}

sub vHostConf {

	use Selity::File;
	use Servers::httpd::apache_itk;

	my $self	= shift;
	my $httpd	= Servers::httpd::apache_itk->new();
	my $rs		= 0;

	for("00_nameserver.conf", "00_master_ssl.conf", "00_master.conf", "00_modcband.conf", "01_awstats.conf"){

		$rs |= $httpd->disableSite($_);

		if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_") {
			$rs |= Selity::File->new(
				filename => "$self::apacheConfig{'APACHE_SITES_DIR'}/$_"
			)->delFile();
		}
	}

	$rs |= $httpd->enableSite("default");

	$rs;
}

1;
