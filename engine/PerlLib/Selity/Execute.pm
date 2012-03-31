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

package Selity::Execute;

use strict;
use warnings;

use vars qw/@ISA @EXPORT/;
use Exporter;

use Selity::Debug;
use Selity::STDCapture;

@ISA = ('Exporter');
@EXPORT = qw/execute/;

sub execute{
	my ($code, $output, $error) = @_;
	my $rv;
	if (ref $output && ref $error){
		$rv = _execCaptureBoth($code, $output, $error);
	} elsif(ref $output){
		$rv = _execCaptureOutput($code, $output);
	} elsif(ref $error){
		$rv = _execCaptureError($code, $error);
	} else {
		$rv = _execCode($code);
	}
	$rv;
}

sub _execCaptureBoth {
	my ($code, $output, $error) = @_;
	my $out = new Selity::STDCapture('STDOUT', $output);
	my $err = new Selity::STDCapture('STDERR', $error);
	debug("Execute $code");
	system($code);
	return _getExitCode($?);
}

sub _execCaptureOutput {
	my ($code, $output) = @_;
	my $out = new Selity::STDCapture('STDOUT', $output);
	debug("Execute $code");
	system($code);
	return _getExitCode($?);
}

sub _execCaptureError {
	my ($code, $error) = @_;
	my $err = new Selity::STDCapture('STDERR', $error);
	debug("Execute $code");
	system($code);
	return _getExitCode($?);
}

sub _execCode {
	my $code = shift;
	debug("Execute $code");
	system($code);
	return _getExitCode($?);
}

sub _getExitCode {
	my $exitValue = shift;
	if ($exitValue == -1) {
		error("Failed to execute external command: $!");
	} elsif ($exitValue & 127) {
		error(''.
			(
				sprintf "External command died with signal %d, %s coredump",
				($exitValue & 127), ($? & 128) ? 'with' : 'without'
			)
		);
	} else {
		$exitValue = $exitValue >> 8;
		debug("External command exited with value $exitValue");
	}
	$exitValue;
}

1;
