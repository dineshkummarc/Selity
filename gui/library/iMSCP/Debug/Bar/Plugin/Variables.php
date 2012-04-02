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

/** @See iMSCP_Debug_Bar_Plugin */
require_once 'iMSCP/Debug/Bar/Plugin.php';

/**
 * Variables plugin for the i-MSCP Debug Bar component.
 *
 * Provides debug information about variables such as $_GET, $_POST...
 *
 * @package		iMSCP
 * @package		iMSCP_Debug
 * @subpackage	Bar_Plugin
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.2
 */
class iMSCP_Debug_Bar_Plugin_Variables extends iMSCP_Debug_Bar_Plugin
{
	/**
	 * @var string Plugin unique identifier
	 */
	const IDENTIFIER = 'Variables';

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
	 * Returns list of events that this plugin listens on.
	 *
	 * @return array
	 */
	public function getListenedEvents()
	{
		return array();
	}

	/**
	 * Returns plugin tab.
	 *
	 * @return string
	 */
	public function getTab()
	{
		return $this->getIdentifier();
	}

	/**
	 * Returns the plugin panel.
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$vars = '<h4>Variables</h4>';

		$vars .= '<h4>$_GET:</h4>'
			. '<div id="iMSCPdebug_get">' . $this->_humanize($_GET) . '</div>';

		$vars .= '<h4>$_POST:</h4>'
			. '<div id="iMSCPdebug_post">' . $this->_humanize($_POST) . '</div>';

		$vars .= '<h4>$_COOKIE:</h4>'
			. '<div id="iMSCPdebug_cookie">' . $this->_humanize($_COOKIE) . '</div>';

		$vars .= '<h4>$_FILES:</h4>'
			. '<div id="iMSCPdebug_file">' . $this->_humanize($_FILES) . '</div>';

		$vars .= '<h4>$_SESSION:</h4>'
			. '<div id="iMSCPdebug_session">' . $this->_humanize($_SESSION) . '</div>';

		$vars .= '<h4>$_SERVER:</h4>'
			. '<div id="iMSCPdebug_server">' . $this->_humanize($_SERVER) . '</div>';

		$vars .= '<h4>$_ENV:</h4>'
			. '<div id="iMSCPdebug_env">' . $this->_humanize($_ENV) . '</div>';

		return $vars;
	}

	/**
	 * Returns plugin icon.
	 *
	 * @return string
	 */
	public function getIcon()
	{
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAFWSURBVBgZBcE/SFQBAAfg792dppJeEhjZn80MChpqdQ2iscmlscGi1nBPaGkviKKhONSpvSGHcCrBiDDjEhOC0I68sjvf+/V9RQCsLHRu7k0yvtN8MTMPICJieaLVS5IkafVeTkZEFLGy0JndO6vWNGVafPJVh2p8q/lqZl60DpIkaWcpa1nLYtpJkqR1EPVLz+pX4rj47FDbD2NKJ1U+6jTeTRdL/YuNrkLdhhuAZVP6ukqbh7V0TzmtadSEDZXKhhMG7ekZl24jGDLgtwEd6+jbdWAAEY0gKsPO+KPy01+jGgqlUjTK4ZroK/UVKoeOgJ5CpRyq5e2qjhF1laAS8c+Ymk1ZrVXXt2+9+fJBYUwDpZ4RR7Wtf9u9m2tF8Hwi9zJ3/tg5pW2FHVv7eZJHd75TBPD0QuYze7n4Zdv+ch7cfg8UAcDjq7mfwTycew1AEQAAAMB/0x+5JQ3zQMYAAAAASUVORK5CYII=';
	}
}
