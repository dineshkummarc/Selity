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
 * Result class.
 *
 * @category	iMSCP
 * @package		Authentication
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.1
 */
class iMSCP_Authentication_Result
{
	/**
	 * General Failure
	 */
	const FAILURE = 0;

	/**
	 * Failure due to identity not being found
	 */
	const FAILURE_IDENTITY_NOT_FOUND = -1;

	/**
	 * Failure due to invalid credential being supplied
	 */
	const FAILURE_CREDENTIAL_INVALID = -2;

	/**
	 * Failure due to uncategorized reasons
	 */
	const FAILURE_UNCATEGORIZED = -3;

	/**
	 * Authentication success
	 */
	const SUCCESS = 1;

	/**
	 * Authentication result code
	 *
	 * @var int
	 */
	protected $_code;

	/**
	 * @var stdClass The identity used in the authentication attempt
	 */
	protected $_identity;

	/**
	 * An array of string reasons why the authentication attempt was unsuccessful
	 *
	 * If authentication was successful, this should be an empty array.
	 *
	 * @var array
	 */
	protected $_messages;

	/**
	 * Sets the result code, identity, and failure messages
	 *
	 * @param int $code
	 * @param mixed $identity
	 * @param array $messages
	 */
	public function __construct($code, $identity, array $messages = array())
	{
		$code = (int)$code;

		if ($code < self::FAILURE_UNCATEGORIZED) {
			$code = self::FAILURE;
		} elseif ($code > self::SUCCESS) {
			$code = 1;
		}

		$this->_code = $code;
		$this->_identity = $identity;
		$this->_messages = $messages;
	}

	/**
	 * Returns whether the result represents a successful authentication attempt
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		return ($this->_code > 0) ? true : false;
	}

	/**
	 * getCode() - Get the result code for this authentication attempt
	 *
	 * @return int
	 */
	public function getCode()
	{
		return $this->_code;
	}

	/**
	 * Returns the identity used in the authentication attempt
	 *
	 * @return mixed
	 */
	public function getIdentity()
	{
		return $this->_identity;
	}

	/**
	 * Returns an array of string reasons why the authentication attempt was unsuccessful
	 *
	 * If authentication was successful, this method returns an empty array.
	 *
	 * @return array
	 */
	public function getMessages()
	{
		return $this->_messages;
	}
}
