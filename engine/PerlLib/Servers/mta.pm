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

package Servers::mta;

use strict;
use warnings;
use Selity::Debug;

use vars qw/@ISA/;
@ISA = ('Common::SimpleClass');
use Common::SimpleClass;

sub factory{
	my $self	= shift;
	my $server	= shift || $main::selityConfig{MTA_SERVER};
	my ($file, $class);

	if(lc($server) =~ /^no$/ ){
		$file	= 'Servers/noserver.pm';
		$class	= 'Servers::noserver';
	} else {
		$file	= "Servers/mta/$server.pm";
		$class	= "Servers::mta::$server";
	}

	require $file;
	return $class->new();
}

1;
