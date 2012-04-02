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

/** @see Zend_Uri_Http */
require_once 'Zend/Uri/Http.php';

/**
 * Redirect URI handler (Like supported in i-MSCP engine)
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Uri
 * @author		Laurent Declercq <l.declercq@i-mscp.net>
 * @version		0.0.1
 */
class iMSCP_Uri_Redirect extends Zend_Uri_Http
{
	/**
	 * Creates a Zend_Uri_Http from the given string
	 *
	 * @param  string $uri String to create URI from, must start with
	 *					   'http://' or 'https://' or 'ftp://'
	 * @throws InvalidArgumentException  When the given $uri is not a string or
	 *								   does not start with http:// or https://
	 * @throws iMSCP_Uri_Exception	   When the given $uri is invalid
	 * @return iMSCP_Uri_Redirect
	 */
	public static function fromString($uri)
	{
		if (is_string($uri) === false) {
			require_once 'Zend/Uri/Exception.php';
			throw new Zend_Uri_Exception('$uri is not a string');
		}

		$uri = explode(':', $uri, 2);
		$scheme = strtolower($uri[0]);
		$schemeSpecific = isset($uri[1]) === true ? $uri[1] : '';

		if (in_array($scheme, array('http', 'https', 'ftp')) === false) {
			require_once 'iMSCP/Uri/Exception.php';
			throw new iMSCP_Uri_Exception("Invalid scheme: '$scheme'");
		}

		$schemeHandler = new iMSCP_Uri_Redirect($scheme, $schemeSpecific);
		return $schemeHandler;
	}

	/**
	 * Returns true if and only if the host string passes validation. If no host is passed,
	 * then the host contained in the instance variable is used.
	 *
	 * @param  string $host The HTTP host
	 * @return boolean
	 * @uses   Zend_Filter
	 */
	public function validateHost($host = null)
	{
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		if ($host === null) {
			$host = $this->_host;
		}

		// If the host is empty, then it is considered invalid
		if (strlen($host) === 0) {
			return false;
		}

		// Check the host against the allowed values; delegated to Zend_Filter.
		$validate = new Zend_Validate_Hostname(
			Zend_Validate_Hostname::ALLOW_DNS, true, (bool) $cfg->TLD_STRICT_VALIDATION);

		return $validate->isValid($host);
	}
}
