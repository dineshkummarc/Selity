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

package Common::SetterClass;

use strict;
use warnings;

sub prop{
	my ($self, $var) = (shift, undef);
	if (@_) {
		$var = shift;
		if(ref $var eq 'HASH'){
			for (keys %{$var}){
				$self->prop($_, $var->{$_});
			}
		}elsif (@_) { $self->{$var} = shift; }
	}
	return ( defined( $var ) && defined( $self->{$var} ) ) ? $self->{$var} : undef;
}


1;
