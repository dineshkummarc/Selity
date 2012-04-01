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
			order => [ 'cdialog', 'whiptail', 'ascii' ] );
}

sub reinit{
	my $self = shift;
	$self->_init();
}

sub AUTOLOAD {
	my $self = shift;
	my $noprompt	= Selity::Args->new->get('noprompt');
	$AUTOLOAD =~ /([^:]+)$/;
	fatal('No prompt switch is on but some value need user input. Exiting from method:'. $1) if $noprompt;
	return $self->{UI}->$1(@_) if $1 && $self->{UI}->can($1);
	fatal('Method'.($1?$1:'unknown').'not implemented');
}

sub msgbox{
	my $self	= shift;
	my $noprompt	= Selity::Args->new->get('noprompt');
	return $self->{UI}->msgbox(@_) unless $noprompt;
}

sub textbox{
	my $self	= shift;
	my $noprompt	= Selity::Args->new->get('noprompt');
	return $self->{UI}->msgbox(@_) unless $noprompt;
}

sub DESTROY{
	my $self = shift;
	$self->{UI}->DESTROY(@_);
}

1;
