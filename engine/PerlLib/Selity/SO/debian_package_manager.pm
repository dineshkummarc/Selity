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

package Selity::SO::debian_package_manager;

use strict;
use warnings;

use Selity::Debug;
use Selity::Execute;
use Selity::File;

use vars qw/@ISA/;
@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init {

	my $self	= shift;
	my $rs		= 0;

	$self->{nonfree} = 'non-free';

	$rs = $self->updateRepository;
	return $rs if $rs;

	$rs = $self->updatePackageList;

	$rs;
}

sub updateRepository {

	my $self = shift;

	my $file = Selity::File->new(filename => '/etc/apt/sources.list');
	$file->copyFile('/etc/apt/sources.list.bkp') unless( -f '/etc/apt/sources.list.bkp');

	my $content = $file->get();
	unless ($content){
		error('Unable to read /etc/apt/sources.list file');
		return 1;
	}

	my ($foundNonFree, $needUpdate, $rs, $stdout, $stderr);

	while($content =~ /^deb\s+(?<uri>(?:https?|ftp)[^\s]+)\s+(?<distrib>[^\s]+)\s+(?<components>.+)$/mg){
		my %repos = %+;

		# is non-free repository available?
		unless($repos{'components'} =~ /\s?$self->{nonfree}(\s|$)/ ){

			my $uri = "$repos{uri}/dists/$repos{distrib}/$self->{nonfree}/";
			$rs = execute("wget --spider $uri", \$stdout, \$stderr);
			debug("$stdout") if $stdout;
			debug("$stderr") if $stderr;

			unless ($rs){

				$foundNonFree = 1;
				debug("Enabling non free section on $repos{uri}");
				$content =~ s/^($&)$/$1 $self->{nonfree}/mg;
				$needUpdate = 1;

			}
		} else {

			debug("Non free section is already enabled on $repos{uri}");
			$foundNonFree = 1;

		}

	}

	unless($foundNonFree){

		error('Unable to found repository that support non-free packages');
		return 1;

	}

	if($needUpdate){

		$file->set($content);
		$file->save() and return 1;

	}

	0;
}

sub updatePackageList {

	my ($rs, $stdout, $stderr);

	$rs = execute('apt-get update', \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	error('Unable to update package index from remote repository') if $rs && !$stderr;
	return $rs if $rs;

	0;
}

sub installPackage{

	my $self	= shift;
	my $pkgList	= @_;
	my($rs, $stderr);

	$rs = execute("apt-get -f -y install $pkgList", undef, \$stderr);
	error("$stderr") if $stderr && $rs;
	error('Can not install packages.') if $rs && ! $stderr;
	return $rs if $rs;

	0;
}

1;
