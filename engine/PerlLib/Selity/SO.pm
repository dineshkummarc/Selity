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
use Selity::Execute;

use vars qw/@ISA/;
@ISA = ("Common::SingletonClass");
use Common::SingletonClass;

sub _init{

	my $self = shift;

	$self->{release} = {
		'/etc/annvix-release'		=> 'Annvix',
		'/etc/arch-release'			=> 'Arch Linux',
		'/etc/arklinux-release'		=> 'Arklinux',
		'/etc/aurox-release'		=> 'Aurox Linux',
		'/etc/blackcat-release'		=> 'BlackCat',
		'/etc/cobalt-release'		=> 'Cobalt',
		'/etc/conectiva-release'	=> 'Conectiva',
		'/etc/debian_version'		=> {
											'fallback'			=> 'Debian',
											'other'	=> (
												'/etc/lsb-release'
											)
										},
		'/etc/debian_release'		=> 'Debian',
		'/etc/fedora-release'		=> 'Fedora Core',
		'/etc/gentoo-release'		=> 'Gentoo Linux',
		'/etc/immunix-release'		=> 'Immunix',
		'knoppix_version'			=> 'Knoppix',
		'/etc/lfs-release'			=> 'Linux-From-Scratch',
		'/etc/linuxppc-release'		=> 'Linux-PPC',
		'/etc/mandrake-release'		=> 'Mandrake',
		'/etc/mandrake-release'		=> 'Mandrake Linux',
		'/etc/mandakelinux-release'	=> 'Mandrake Linux',
		'/etc/mandriva-release'		=> 'Mandriva/',
		'/etc/mklinux-release'		=> 'MkLinux',
		'/etc/nld-release'			=> 'Novell Linux Desktop',
		'/etc/pld-release'			=> 'PLD Linux',
		'/etc/redhat-release'		=> 'Red Hat',
		'/etc/redhat_version'		=> 'Red Hat',
		'/etc/slackware-version'	=> 'Slackware',
		'/etc/slackware-release'	=> 'Slackware',
		'/etc/e-smith-release'		=> 'SME Server',
		'/etc/release'				=> 'Solaris SPARC',
		'/etc/sun-release'			=> 'Sun JDS',
		'/etc/SuSE-release'			=> 'SUSE Linux',
		'/etc/novell-release'		=> 'SUSE Linux',
		'/etc/sles-release'			=> 'SUSE Linux ES9',
		'/etc/tinysofa-release'		=> 'Tiny Sofa',
		'/etc/turbolinux-release'	=> 'TurboLinux',
		'/etc/lsb-release'			=> 'Ubuntu Linux',
		'/etc/ultrapenguin-release'	=> 'UltraPenguin',
		'/etc/UnitedLinux-release'	=> 'UnitedLinux',
		'/etc/va-release'			=> 'VA-Linux/RH-VALE',
		'/etc/yellowdog-release'	=> 'Yellow Dog'
	};

	$self->{lsb} = {
		Debian	=> 'apt-get -y install lsb-release lsb',
		Ubuntu	=> 'apt-get -y install lsb-release lsb'
	};

	$self->{Distribution}	= '';
	$self->{Version}		= '';
	$self->{CodeName}		= '';

}

sub getSO{

	my $self = shift;
	my $rs = 1;

	$self->{Distribution} = lc $^O;

	if($self->{Distribution} !~ /bsd$|linux/){

		error ("$^O is not supported");

	} elsif ($self->{Distribution} eq 'linux'){

		foreach(qw/_getByLSB _getByIssue/){

			last unless $rs = $self->$_ ;

		}

	}

	$rs;
}

sub _getByIssue{

	my $self = shift;

	foreach(keys %{$self->{release}}){

		if(-f $_){

			my $file = $_;

			if(ref $self->{release}->{$_} eq 'HASH'){

				debug(
					"Found $file assuming $self->{release}->{$file}->{fallback}. ".
					"Testing derivates"
				);
				$self->{Distribution} = $self->{release}->{$file}->{fallback};

				foreach ($self->{release}->{$file}->{other}){

					debug("Testing $_");

					if(-f "$_"){

						$self->{Distribution} = $self->{release}->{$_};
						debug("Found $self->{release}->{$_}");
						last;

					}
				}

			}

			last;

		}

	}

	if(!$self->{lsb}->{$self->{Distribution}}) {

		error("Can not use lsb_release! Install and try again");
		return 1;

	}

	my($rs, $stdout, $stderr);
	$rs = execute($self->{lsb}->{$self->{Distribution}}, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;
	return $rs if $rs;

	return $self->_getByLSB();
}

sub _getByLSB{

	my $self = shift;
	my ($rs, $stdout, $stderr);

	#test lsb-release
	$rs = execute("which lsb_release", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;
	return $rs if $rs;

	if($self->{Distribution} eq 'linux'){

		$rs = execute('lsb_release -sirc', \$stdout, \$stderr);
		debug("Distribution is $stdout") if $stdout;
		error("Can not guess operating system: $stderr") if $stderr;
		return $rs if $rs;

		($self->{Distribution}, $self->{Version}, $self->{CodeName}) = split "\n", $stdout;
		return 1 unless $self->{Distribution} && $self->{Version} && $self->{CodeName};

		debug("Found $self->{Distribution} $self->{Version} $self->{CodeName}");

	}

	0;
}
1;
__END__


=head1 NAME

Selity::So - Perl class to detect on which OS we are running.

=head1 SYNOPSIS

	use Selity::SO;

	my $SO = Selity::SO->new();
	my $rs = $SO->getSO();
	if($rs) {
		print "you are running $SO->{Distribution}".
		($SO->{Version} ? " $SO->{Version}" : '').
		($SO->{CodeName} ? " $SO->{CodeName}" : '')."\n";
	} else {
		print "unknown operating system\n";
	}

=head1 DESCRIPTION

This is a simple class that tries to guess on what perating system.

=head2 EXPORT

None by default.

=head1 TODO

?

=head1 AUTHORS

Daniel Andreca E<lt>sci2tech@gmail.comE<gt>, L<http://4isp.ro>

=head1 COPYRIGHT AND LICENSE

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later version.

=cut

