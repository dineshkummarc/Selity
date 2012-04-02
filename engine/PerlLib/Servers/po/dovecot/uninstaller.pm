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

package Servers::po::dovecot::uninstaller;

use strict;
use warnings;
use Selity::Debug;
use Selity::File;
use Selity::Execute;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{

	my $self		= shift;
	$self->{cfgDir}	= "$main::selityConfig{'CONF_DIR'}/dovecot";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	my $conf		= "$self->{cfgDir}/dovecot.data";

	tie %self::dovecotConfig, 'Selity::Config','fileName' => $conf;

	0;
}

sub uninstall{

	my $self	= shift;
	my $rs		= 0;

	$rs |= $self->restoreConfFile();
	$rs |= $self->removeSQL();

	$rs;
}

sub restoreConfFile{

	my $self	= shift;
	my $rs		= 0;
	my $file;

	for ((
		'dovecot.conf',
		'dovecot-sql.conf'
	)) {
		$rs	|=	Selity::File->new(
					filename => "$self->{bkpDir}/$_.system"
				)->copyFile(
					"$self::dovecotConfig{'DOVECOT_CONF_DIR'}/$_"
				)
				if -f "$self->{bkpDir}/$_.system"
		;
	}

	use Servers::mta;
	my $mta	= Servers::mta->factory();

	for ('dovecot-sql.conf', 'dovecot-dict-sql.conf') {
		$file = Selity::File->new(filename => "$self::dovecotConfig{'DOVECOT_CONF_DIR'}/$_");
		$rs |= $file->mode(0640);
		$rs |= $file->owner($main::selityConfig{'ROOT_USER'}, $mta->{'MTA_MAILBOX_GID_NAME'});
	}

	$file	= Selity::File->new(filename => "$self::dovecotConfig{'DOVECOT_CONF_DIR'}/dovecot.conf");
	$rs |= $file->mode(0644);

	$rs;
}

sub removeSQL{

	my $self	= shift;
	my $rs		= 0;

	if($self::dovecotConfig{'DATABASE_USER'}) {

		my $database = Selity::Database->new()->factory();

		$database->doQuery( 'delete', "DROP USER ?@?", $self::dovecotConfig{'DATABASE_USER'}, 'localhost');
		$database->doQuery( 'delete', "DROP USER ?@?", $self::dovecotConfig{'DATABASE_USER'}, '%');
		$database->doQuery('dummy', 'FLUSH PRIVILEGES');

	}

	0;
}

1;
