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
 * Bruteforce detection plugin.
 *
 * This plugin provides a sublayer for the authentication process that allows to increase system security by
 * detecting any dictionary attacks and blocking them according a set of configuration parameters.
 *
 * This plugin can be used in two different ways:
 *
 * - As an action plugin that listen to some events triggered in i-MSCP core code and that doing some specific actions
 *   related to bruteforce detection
 * - As a simple object queried by hand in external components.
 *
 * @category	iMSCP
 * @package		iMSCP_Authentication
 * @subpackage	Bruteforce
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @version		0.0.3
 */
class iMSCP_Authentication_Bruteforce extends iMSCP_Plugin_Action implements iMSCP_Events_Listeners_Interface
{
	/**
	 * @var string listened events.
	 */
	protected $_listenedEvents = array(
		iMSCP_Events::onBeforeAuthentication,
		iMSCP_Events::onBeforeSetIdentity
	);

	/**
	 * @var int Tells whether or not bruteforce detection is enabled
	 */
	protected $_bruteForceEnabled = 0;

	/**
	 * @var int Tells whether or not waiting time between login|captcha attempts is enabled
	 */
	protected $_waitTimeEnabled = 0;

	/**
	 * @var int Blocking time in minutes
	 */
	protected $_blockTime = 0;

	/**
	 * @var int Waiting time in seconds between each login|captcha attempts
	 */
	protected $_waitTime = 0;

	/**
	 * @var int Max attempts before an IP address is blocked
	 */
	protected $_maxAttempts = 0;

	/**
	 * @var string IP address (The subject)
	 */
	protected $_ipAddr = '';

	/**
	 * @var string Bruteforce detection type (login|captcha)
	 */
	protected $_type = 'login';

	/**
	 * @var int Time during which an IP address is blocked
	 */
	protected $_isBlockedFor = 0;

	/**
	 * @var int Time to wait before a new login|captcha attempts is allowed
	 */
	protected $_isWaitingFor = 0;

	/**
	 * @var bool Tells whether or not a bruteforce detection record exists for $_ipAddr
	 */
	protected $_recordExists = false;

	/**
	 * @var string Session unique identifier
	 */
	protected $_sessionId = '';

	/**
	 * @var string Last message raised
	 */
	protected $_message = '';

	/**
	 * Constructor.
	 *
	 * @param string $type Bruteforce detection type (login|captcha) (defaulted to login)
	 */
	public function __construct($type = 'login')
	{
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		$this->_sessionId = session_id();
		$this->_type = $type;
		$this->_ipAddr = $_SERVER['REMOTE_ADDR'];

		if ($type == 'login') {
			$this->_maxAttempts = $cfg->BRUTEFORCE_MAX_LOGIN;
		} else {
			$this->_maxAttempts = $cfg->BRUTEFORCE_MAX_CAPTCHA;
		}

		$this->_blockTime = $cfg->BRUTEFORCE_BLOCK_TIME;
		$this->_waitTime = $cfg->BRUTEFORCE_BETWEEN_TIME;

		$this->_unblock();

		// Plugin initialization
		parent::__construct();
	}

	/**
	 * Initialization.
	 *
	 * @return void
	 */
	public function init()
	{
		$query = 'SELECT * FROM `login` WHERE `ipaddr` = ? AND `user_name` IS NULL';
		$stmt = exec_query($query, $this->_ipAddr);

		if (!$stmt->rowCount()) {
			$this->_recordExists = false;
		} else {
			$this->_recordExists = true;

			if ($stmt->fields($this->_type . '_count') >= $this->_maxAttempts) {
				$this->_isBlockedFor = $stmt->fields('lastaccess') + $this->_blockTime * 60;
				$this->_isWaitingFor = 0;
			} else {
				$this->_isBlockedFor = 0;
				$this->_isWaitingFor = $stmt->fields('lastaccess') + $this->_waitTime;
			}
		}
	}

	/**
	 * Returns plugin general information.
	 *
	 * @return array
	 */
	public function getInfo()
	{
		return array(
			'author' => 'Daniel Andreca',
			'email' => 'sci2tech@gmail.com',
			'version' => '0.0.3',
			'date' => '2012-03-20',
			'name' => 'Bruteforce',
			'desc' => 'Allow to improve system security by detecting any dictionnary attacks and blocking them according a set of configuration parameters',
			'url' => 'http://www.i-mscp.net'
		);
	}

	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $controller
	 */
	public function register(iMSCP_Events_Manager_Interface $controller)
	{
		$controller->registerListener($this->getListenedEvents(), $this);
		$this->_controller = $controller;
	}

	/**
	 * Implements the onBeforeAuthentication listener method.
	 *
	 * @param iMSCP_Events_Event $event Represent an onBeforeAuthentication event that is triggered in the
	 *									 iMSCP_Authentication component.
	 * @return null|string
	 */
	public function onBeforeAuthentication($event)
	{
		if ($this->isWaiting() || $this->isBlocked()) {
			$event->stopPropagation();
			return $this->getLastMessage();
		}

		$this->recordAttempt();

		return null;
	}

	/**
	 * Implement the onBeforeSetIdentity listener method.
	 *
	 * @param iMSCP_Events_Event $event Represent an onBeforeSetIdentity event that is triggered in the
	 *									 iMSCP_Authentication component.
	 * @return void
	 */
	public function onBeforeSetIdentity($event)
	{
		exec_query('DELETE FROM `login` WHERE `session_id` = ?', $this->_sessionId);
	}

	/**
	 * Returns listened events.
	 *
	 * @return array
	 */
	public function getListenedEvents()
	{
		return $this->_listenedEvents;
	}

	/**
	 * Is blocked IP address?
	 *
	 * @return bool TRUE if $_ipAddr is blocked, FALSE otherwise
	 */
	public function isBlocked()
	{
		if ($this->_isBlockedFor - time() > 0) {
			$this->_message = tr('Ip %s is blocked for %s minutes.', $this->_ipAddr, $this->isBlockedFor());
			return true;
		}

		return false;
	}

	/**
	 * Is waiting IP address?
	 *
	 * @return bool TRUE if $_ipAddr is waiting, FALSE otherwise
	 */
	public function isWaiting()
	{
		if ($this->_isWaitingFor - time() > 0) {
			$this->_message = tr('Ip %s is waiting %s seconds.', $this->_ipAddr, $this->isWaitingFor());
			return true;
		}

		return false;
	}

	/**
	 * Create/Update bruteforce detection record for $_ipAddr.
	 *
	 * @return void
	 */
	public function recordAttempt()
	{
		if (!$this->_recordExists) {
			$this->_createRecord();
		} else {
			$this->_updateRecord();
		}
	}

	/**
	 * Returns last message raised.
	 *
	 * @return string
	 */
	public function getLastMessage()
	{
		return $this->_message;
	}

	/**
	 * Returns human readable blocking time.
	 *
	 * @return string
	 */
	protected function isBlockedFor()
	{
		return strftime("%M:%S", ($this->_isBlockedFor - time() > 0) ? $this->_isBlockedFor - time() : 0);
	}

	/**
	 * Returns human readable waiting time.
	 *
	 * @return string
	 */
	protected function isWaitingFor()
	{
		return strftime("%M:%S", ($this->_isWaitingFor - time() > 0) ? $this->_isWaitingFor - time() : 0);
	}

	/**
	 * Increase login|captcha attempts by 1 for $_ipAddr.
	 *
	 * @return void
	 */
	protected function _updateRecord()
	{
		$query = "
			UPDATE
				`login`
			SET
				`lastaccess` = UNIX_TIMESTAMP(), `{$this->_type}_count` = `{$this->_type}_count` + 1
			WHERE
				`ipaddr`= ? AND `user_name` IS NULL
		";
		exec_query($query, ($this->_ipAddr));
	}

	/**
	 * Create bruteforce detection record.
	 *
	 * @return void
	 */
	protected function _createRecord()
	{
		$query = "
			REPLACE INTO `login` (
				`session_id`, `ipaddr`, `{$this->_type}_count`, `user_name`, `lastaccess`
				) VALUES (
					?, ?, 1, NULL, UNIX_TIMESTAMP()
			)
		";
		exec_query($query, array($this->_sessionId, $this->_ipAddr));
	}

	/**
	 * Unblock any Ip address for which blocking time is expired.
	 *
	 * @return void
	 */
	protected function _unblock()
	{
		$timeout = time() - ($this->_blockTime * 60);

		$query = "DELETE FROM `login` WHERE `lastaccess` < ? AND `{$this->_type}_count` > 0";
		exec_query($query, $timeout);
	}
}
