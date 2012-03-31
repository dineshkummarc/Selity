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

package Selity::Dialog;

use strict;
use warnings;

use vars qw/@ISA $AUTOLOAD/;
@ISA = ('Common::SingletonClass');
use Common::SingletonClass;
use AutoLoader;
use UI::Dialog;
use Data::Dumper;
use Selity::Args;
use Selity::Debug;

sub _init{
	my $self = shift;
	$self->{UI} = UI::Dialog->new(
			title => 'Selity setup', backtitle => 'Selity - When virtual hosting becomes scalable',
			order => [ 'ascii', 'cdialog', 'whiptail' ] );
}

sub AUTOLOAD {
	my $self = shift;
	my $prompt	= Selity::Args->new->get('noprompt');
	fatal('No prompt switch is on but some value need user input. Exiting') if $prompt && $prompt =~ m/^y$|^yes$/i;
	$AUTOLOAD =~ /([^:]+)$/;
	return $self->{UI}->$1(@_) if $1 && $self->{UI}->can($1);
	fatal('Method'.($1?$1:'unknown').'not implemented');
}

sub msgbox{
	my $self	= shift;
	my $prompt	= Selity::Args->new->get('noprompt');
	return $self->{UI}->msgbox(@_) unless $prompt && $prompt =~ m/^y$|^yes$/i;
}

sub DESTROY{
	my $self = shift;
	$self->{UI}->DESTROY(@_);
}

1;
