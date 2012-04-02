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
 * Authentication class.
 *
 * @category	iMSCP
 * @package		Authentication
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.4
 */
class iMSCP_Authentication
{
	/**
	 * Singleton instance
	 *
	 * @var iMSCP_Authentication
	 */
	protected static $_instance = null;

	/**
	 * @var iMSCP_Events_Manager_Interface
	 */
	protected $_events;

	/**
	 * @var string Username to match against
	 */
	protected $_username;

	/**
	 * @var string Clear text password to match against
	 */
	protected $_password;

	/**
	 * Singleton pattern implementation -  makes "new" unavailable.
	 */
	protected function __construct()
	{

	}

	/**
	 * Singleton pattern implementation -  makes "clone" unavailable.
	 *
	 * @return void
	 */
	protected function __clone()
	{

	}

	/**
	 * Implements singleton design pattern.
	 *
	 * @return iMSCP_Authentication Provides a fluent interface, returns self
	 */
	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Return an iMSCP_Events_Manager instance.
	 *
	 * @param iMSCP_Events_Manager_Interface $events
	 * @return iMSCP_Events_Manager_Interface
	 */
	public function events(iMSCP_Events_Manager_Interface $events = null)
	{
		if (null !== $events) {
			$this->_events = $events;
		} elseif (null === $this->_events) {
			$this->_events = iMSCP_Events_Manager::getInstance();
		}

		return $this->_events;
	}

	/**
	 * Process authentication.
	 *
	 * @return iMSCP_Authentication_Result
	 * @return void
	 */
	public function authenticate()
	{
		// If propagation is stopped, expects a message to explain the cause
		$responseCollection = $this->events()->dispatch(iMSCP_Events::onBeforeAuthentication, array('context' => $this));

		if ($responseCollection->isStopped()) {
			$result = new iMSCP_Authentication_Result(
				iMSCP_Authentication_Result::FAILURE_UNCATEGORIZED, null, array($responseCollection->last())
			);
		} else {
			$query = "
				SELECT
					`admin_id`, `admin_name`, `admin_pass`, `admin_type`, `email`, `created_by`
				FROM
					`admin`
				WHERE
					`admin_name` = ?
			";
			$stmt = exec_query($query, $this->getUsername());

			if (!$stmt->rowCount()) {
				$result = new iMSCP_Authentication_Result(
					iMSCP_Authentication_Result::FAILURE_IDENTITY_NOT_FOUND,
					$stmt->fetchRow(PDO::FETCH_OBJ),
					array(tr('Unknown username.'))
				);
			} else {
				$resultRow = $stmt->fetchRow(PDO::FETCH_OBJ);
				$password = $this->getPassword();
				$dbPassword = $resultRow->admin_pass;

				if (crypt($password, $dbPassword) != $dbPassword && $dbPassword != md5($password)) {
					$result = new iMSCP_Authentication_Result(
						iMSCP_Authentication_Result::FAILURE_CREDENTIAL_INVALID,
						$resultRow,
						array(tr('Wrong password.'))
					);
				} else {
					$result = new iMSCP_Authentication_Result(iMSCP_Authentication_Result::SUCCESS, $resultRow);
				}
			}

			// Prevent multiple succesive calls from storing inconsistent results
			$this->unsetIdentity();

			if ($result->isValid()) {
				$this->setIdentity($result->getIdentity());
			}

			$this->events()->dispatch(
				iMSCP_Events::onAfterAuthentication, array('context' => $this, 'authResult' => $result)
			);
		}

		return $result;
	}

	/**
	 * Return username.
	 *
	 * @return string
	 */
	public function getUsername()
	{
		return $this->_username;
	}

	/**
	 * Sets username to match against.
	 *
	 * @param string $username Password
	 * @return iMSCP_Authentication Provides fluent interface, returns self
	 */
	public function setUsername($username)
	{
		$this->_username = (string)$username;

		return $this;
	}

	/**
	 * Return password.
	 *
	 * @return string
	 */
	public function getPassword()
	{
		return $this->_password;
	}

	/**
	 * Set password to match against.
	 *
	 * @param string $password Password
	 * @return iMSCP_Authentication Provides fluent interface, returns self
	 */
	public function setPassword($password)
	{
		$this->_password = (string)$password;

		return $this;
	}

	/**
	 * Returns true if and only if an identity is available from storage.
	 *
	 * @return boolean
	 */
	public function hasIdentity()
	{
		return isset($_SESSION['user_id']);
	}

	/**
	 * Returns the identity from storage or null if no identity is available.
	 *
	 * @return stdClass|null
	 */
	public function getIdentity()
	{
		$identity = null;

		if ($this->hasIdentity()) {
			$identity = new stdClass();
			$identity->admin_id = $_SESSION['user_id'];
			$identity->admin_name = $_SESSION['user_logged'];
			$identity->admin_type = $_SESSION['user_type'];
			$identity->email = $_SESSION['user_email'];
			$identity->created_by = $_SESSION['user_created_by'];
		}

		return $identity;
	}

	/**
	 * Set the given identity.
	 *
	 * @param stdClass $identity Identity data
	 */
	public function setIdentity($identity)
	{
		$this->events()->dispatch(iMSCP_Events::onBeforeSetIdentity, array('context' => $this, 'identity' => $identity));

		// We wil change permission level so we regenerate the session identifier to enforce security
		session_regenerate_id();

		$lastAccess = time();

		$query = "
			REPLACE INTO `login` (
				`session_id`, `ipaddr`, `lastaccess`, `user_name`
			) VALUES (
				?, ?, ?, ?
			)
		";
		exec_query($query, array(session_id(), $_SERVER['REMOTE_ADDR'], $lastAccess, $identity->admin_name));

		$_SESSION['user_logged'] = $identity->admin_name;
		$_SESSION['user_type'] = $identity->admin_type;
		$_SESSION['user_id'] = $identity->admin_id;
		$_SESSION['user_email'] = $identity->email;
		$_SESSION['user_created_by'] = $identity->created_by;
		$_SESSION['user_login_time'] = $lastAccess;

		$this->events()->dispatch(iMSCP_Events::onAfterSetIdentity, array('context' => $this));
	}

	/**
	 * Unset the current identity.
	 *
	 * @return void
	 */
	public function unsetIdentity()
	{
		if ($this->hasIdentity()) {
			$this->events()->dispatch(iMSCP_Events::onBeforeUnsetIdentity, array('context' => $this));

			$query = "DELETE FROM `login` WHERE `session_id` = ?";
			exec_query($query, session_id());

			$preserveList = array(
				'user_def_lang', 'user_theme', 'user_theme_color', 'show_main_menu_labels', 'pageMessages'
			);

			foreach (array_keys($_SESSION) as $sessionVariable) {
				if (!in_array($sessionVariable, $preserveList)) {
					unset($_SESSION[$sessionVariable]);
				}
			}

			$this->events()->dispatch(iMSCP_Events::onAfterUnsetIdentity, array('context' => $this));
		}
	}
}
