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

/** @See iMSCP_Debug_Bar_Plugin_Interface */
require_once 'iMSCP/Debug/Bar/Plugin/Interface.php';

/**
 * Base class for Debug Bar component's plugins.
 *
 * @package		iMSCP
 * @package		iMSCP_Debug
 * @subpackage	Bar
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.2
 */
abstract class iMSCP_Debug_Bar_Plugin implements iMSCP_Debug_Bar_Plugin_Interface
{
	/**
	 * Transforms data into human readable format.
	 *
	 * Note: Method that come from the ZFDebug project hosted at:
	 * http://code.google.com/p/zfdebug/
	 *
	 * @param array $values Values to humanize
	 * @return string
	 */
	protected function _humanize($values)
	{
		if (is_array($values)) {
			ksort($values);
		}

		$retVal = '<div class="pre">';

		foreach ($values as $key => $value) {
			$key = htmlspecialchars($key);

			if (is_numeric($value)) {
				$retVal .= $key . ' => ' . $value . '<br />';
			} elseif (is_string($value)) {
				$retVal .= $key . ' => \'' . htmlspecialchars($value) . '\'<br />';
			} elseif (is_array($value)) {
				$retVal .= $key . ' => ' . $this->_humanize($value);
			} elseif (is_object($value)) {
				$retVal .= $key . ' => ' . get_class($value) . ' Object()<br />';
			} elseif (is_null($value)) {
				$retVal .= $key . ' => NULL<br />';
			}
		}

		return $retVal . '</div>';
	}
}
