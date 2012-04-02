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

 /** @see Zend_Validate_Abstract */
require_once 'Zend/Validate/Abstract.php';

/** @See Zend_Uri */
require_once 'Zend/Uri.php';

/**
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Validate
 * @author		Laurent Declercq <l.declercq@i-mscp.net>
 * @version		0.0.1
 */
class iMSCP_Validate_Uri extends Zend_Validate_Abstract
{
	const INVALID_URI = 'invalidURI';

	protected $_messageTemplates = array(
		self::INVALID_URI => "'%value%' is not a valid URI.",
	);

	/**
	 * Returns true if the $uri is valid
	 *
	 * If $uri is not a valid URI, then this method returns false, and
	 * getMessages() will return an array of messages that explain why the
	 * validation failed.
	 *
	 * @throws Zend_Validate_Exception If validation of $value is impossible
	 * @param  string $uri URI to be validated
	 * @return boolean
	 */
	public function isValid($uri)
	{
		$uri = (string) $uri;
		$this->_setValue($uri);

		try {
			Zend_Uri::factory($uri, 'iMSCP_Uri_Redirect');
		} catch(Exception $e) {
			$this->_error(self::INVALID_URI);
			return false;
		}

		return true;
	}
}
