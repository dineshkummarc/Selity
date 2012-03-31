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

package Selity::SO;

use strict;
use warnings;

use Selity::Debug;
use Selity::Execute qw/execute/;

use vars qw/@ISA/;
@ISA = ('Common::SingletonClass');
use Common::SingletonClass;


sub _init{
	my $self = shift;
	fatal('Can not guess operating system') if ($self->getSO);
}

sub getSO{

	my $self = shift;
	my ($rs, $stdout, $stderr);

	fatal('Not a Debian like system') if(execute('which apt-get', \$stdout, \$stderr));

	if(execute('which lsb_release', \$stdout, \$stderr)){
		$rs = execute('apt-get -y install lsb-release', \$stdout, \$stderr);
		debug("$stdout") if $stdout;
		error("$stderr") if $stderr;
		return $rs if $rs;
	}

	$rs = execute('lsb_release -si', \$stdout, \$stderr);
	debug("Distribution is $stdout") if $stdout;
	error("Can not guess operating system: $stderr") if $stderr;
	return $rs if $rs;
	$self->{Distribution} = $stdout;

	$rs = execute('lsb_release -sr', \$stdout, \$stderr);
	debug("Version is $stdout") if $stdout;
	error("Can not guess operating system: $stderr") if $stderr;
	return $rs if $rs;
	$self->{Version} = $stdout;

	$rs = execute('lsb_release -sc', \$stdout, \$stderr);
	debug("Codename is $stdout") if $stdout;
	error("Can not guess operating system: $stderr") if $stderr;
	return $rs if $rs;
	$self->{CodeName} = $stdout;

	debug ("Found $self->{Distribution} $self->{Version} $self->{CodeName}");
	0;
}

1;
