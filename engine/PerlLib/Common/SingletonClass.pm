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

package Common::SingletonClass;

use strict;
use warnings;
use Symbol;

my $_instance = undef;

sub new {
	my $proto	= shift;
	my $class	= ref($proto) || $proto;
	my $x= qualify_to_ref('_instance', $class);

	return ${*$x} if defined ${*$x};

	my $self = {
		'errors'	=> [],
		'args'		=> {@_} || {}
	};

	bless($self, $class);

	${*$x} = $self;

	if($self->can('_init')){ $self->_init();}

	return($self);
}

1;
