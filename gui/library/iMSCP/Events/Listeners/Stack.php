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
 * Listeners Stack class.
 *
 * Objects of this class represent a listeners stack that belong to a particular
 * event.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Events_Listeners
 * @author		Laurent Declercq <l.declercq@i-mscp.net>
 * @version		0.0.1
 */
class iMSCP_Events_Listeners_Stack implements IteratorAggregate
{
	/**
	 * Listeners stack.
	 *
	 * @var array
	 */
	protected $_listeners = array();

	/**
	 * Adds a listener to the stack.
	 *
	 * @param string|object $listener Fonction name or Listener objet
	 * @param int $stackIndex OPTIONAL Stack index Listener priority
	 * @return iMSCP_Events_Listeners_Stack Provides fluent interface, returns self
	 */
	public function addListener($listener, $stackIndex = 1)
	{
		if (false === array_search($listener, $this->_listeners, true)) {
			$stackIndex = (int)$stackIndex;

			if (!isset($this->_listeners[$stackIndex])) {
				$this->_listeners[$stackIndex] = $listener;
			} else {
				while (isset($this->_listeners[$stackIndex])) {
					++$stackIndex;
				}

				$this->_listeners[$stackIndex] = $listener;
			}

			ksort($this->_listeners);
		}

		return $this;
	}

	/**
	 * Remove a listener from the stack.
	 *
	 * @param string|int|object $listener Listener object or class name
	 * @return bool TRUE if listener has been removed from the stack, FALSE otherwise
	 */
	public function removeListener($listener)
	{
		$retVal = false;

		if (is_object($listener)) { // Remove by object
			if($key = array_search($listener, $this->_listeners, true)) {
				$retVal = true;
				unset($this->_listeners[$key]);
			}

		} elseif (is_string($listener)) { // Remove by className
			$retVal = false;

			foreach ($this->_listeners as $index => $_listener) {
				if (is_object($_listener)) {
					$classname = get_class($_listener);

					if ($listener == $classname) {
						$retVal = true;
						unset($this->_listeners[$index]);
					}
				}
			}
		}

		return $retVal;
	}

	/**
	 * Implements IteratorAggregate interface.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->_listeners);
	}

	/**
	 * Return all listeners from the stack.
	 *
	 * @return array
	 */
	public function getListeners() {
		return $this->_listeners;
	}
}
