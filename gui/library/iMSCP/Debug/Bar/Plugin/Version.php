<?php
# Selity - When virtual hosting becomes scalable
#
# The contents of this file are subject to the Mozilla Public License
# Version 1.1 (the "License"); you may not use this file except in
# compliance with the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS"
# basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
# License for the specific language governing rights and limitations
# under the License.
#
# The Original Code is "ispCP ω (OMEGA) a Virtual Hosting Control Panel".
#
# The Initial Developer of the Original Code is ispCP Team.
# Portions created by Initial Developer are Copyright (C) 2006-2010 by
# isp Control Panel. All Rights Reserved.
#
# Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
# internet Multi Server Control Panel. All Rights Reserved.
#
# Portions created by the Selity Team are Copyright (C) 2012 by Selity.
# All Rights Reserved.
#
# The Selity Home Page is:
#
#    http://selity.net
#
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
# Copyright (C) 2010-2012 by internet Multi Server Control Panel - http://i-mscp.net
# Copyright (C) 2012 by Selity - http://selity.net

/**
 * Version plugin for the i-MSCP Debug Bar component.
 *
 * Provides version information about i-MSCP and also information about all PHP
 * extensions loaded.
 *
 * @package		iMSCP
 * @package		iMSCP_Debug
 * @subpackage	Bar_Plugin
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.3
 */
class iMSCP_Debug_Bar_Plugin_Version extends iMSCP_Debug_Bar_Plugin
{
	/**
	 * @var string Plugin unique identifier
	 */
	const IDENTIFIER = 'Version';

	/**
	 * Returns plugin unique identifier.
	 *
	 * @return string Plugin unique identifier.
	 */
	public function getIdentifier()
	{
		return self::IDENTIFIER;
	}

	/**
	 * Gets menu tab for the Debugbar.
	 *
	 * @return string
	 */
	public function getTab()
	{
		$version = iMSCP_Registry::get('config')->Version;
		return ' ' . $version . ' / PHP ' . phpversion();
	}

	/**
	 * Gets content panel for the Debugbar.
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$version = iMSCP_Registry::get('config')->Version;
		$panel = '<h4>i-MSCP DebugBar v0.0.2</h4>' .
			'<p>©2010-2012 <a href="http://www.i-mscp.net">i-MSCP Team</a><br />' .
			'Includes images from the <a href="http://www.famfamfam.com/lab/icons/silk/">Silk Icon set</a> by Mark James<br />
                 Based upon project hosted at <a href="http://code.google.com/p/zfdebug">ZFDebug</a></p>';
		$panel .= '<h4>i-MSCP ' . $version . ' / PHP ' . phpversion() . ' with extensions:</h4>';
		$extensions = get_loaded_extensions();
		natcasesort($extensions);
		$panel .= "<pre>\t" . implode(PHP_EOL . "\t", $extensions) . '</pre>';
		return $panel;
	}

	/**
	 * Returns plugin icon.
	 *
	 * @return string
	 */
	public function getIcon()
	{
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAAXNSR0IArs4c6QAAAAZiS0dEAP8A/wD/oL2nkwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB9sGCQYUBoUl8rQAAAOdSURBVDjLTZNZTBx1HMe//5k9ZnfpLgnlbkvTCqXWULTabiB4FDWsoYEiEOMRfTAxJprW2tY0JsYmNQFsY2wo8UWi1bSxpqCpLaXGNirL1XB0IRwLyLGwJ+wxuzN7DfPzwcTwef983j4MWyAiVl9fV67XC+84Z2eblE01N5lMkihGVgVB+Hl5efkHAI6tDgOAs2fPoLW1TWu1Wn87Wv1S9c6dhfz8whIKd+2GJMWQlESEwyGYTKZ0W2vr3dwcS4M/ICpE9F9FkkWNzVYz9tXlDvKJKfVY3XFSiOj8xQ76ovVLIiKqqKykWCKtnm+5QgD7S6PheADgiAh1r9TcWqAD5UMjY1TzQgWrb2wGDyDTbITRaAAAnPj4DBobjrPFhRkqPtBctbmp3gQA1txQe/BP5+bD4vrTmvYTR9l3befQa3+EDDWETLMF4XAI+QWFSHECkqk0Pr10FW+/1kI+x7W0SvOHNP5w6k2uoFKbYFrcGHChf2wK7T/dRpExiRudHYjGYvjg9CcIq3rUVllx6ddJlFbsZ9pElW5lZuV1jTcgvqotzYHWYMLM1AQeLy1F5Q4GQECmxQLGOGRn6NE5DBx5thr52nmYKsuwLRVGwNXTzPNa3UWdIYNTYh5E5oeQw4KQxCgmxscx6XBgbmYaalLG731j8K66sDFtR1G2Hnd7/4Yqu7ZpTAKDyiloekLA8IAXUVlAMBgCGLDmXoOqpHF9JISATAj6/Qj6IhjvGkVxroBHzgRpLJbMpfb2zx+LMzN27d2H8eF+nDp1EgBgMZsRi4mIVpzEwjrwT3cLdEVHAHMJzHEPtmfZlziv19stiRt4ercJxj3P4Nub9/DZgzh8YQmSJEGMiKjNj6PEksLm8iCEnP1IuVfhsA9DliJdfCwa9Sq8/t3nbTbu/kSITQ7bsT47iGud3+BB/xDmphzo7foRivM+ggkOUSpBwjlNzx2k9MjDPz7kicjfeMx2OKu4fJ/WM0rO0X7mT+Ug9+VzsO414HBZCS63X4EcT+L2L91wOwYpS6dhy4s9d4LB6NccYwwA6i+8/8bgmqJlTe9dIPdAF3ZEZpGhighsBDG/4oZ9oA8v1n1E242FbGP9Xv/i4loDAPAAIAgC8cn49329t57y+0J7pI04f+hJAwwZFohyHOFIGAsuD8wmTlma67nj8fhsjDHl/xu37AzGWFl2XsFb6US8KTsvr1CWZUon4iu8Tt8dcLuuKiomtjr/Arjqw+qpHX1qAAAAAElFTkSuQmCC';
	}

	/**
	 * Returns list of events that the plugin listens on.
	 *
	 * @return array
	 */
	public function getListenedEvents()
	{
		return array();
	}
}
