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

/** @see iMSCP_Events_Manager_Interface */
require_once 'iMSCP/Events/Manager/Interface.php';

/**
 * Events Manager class.
 *
 * The events manager is the central point of i-MSCP's event listener system.
 * Listeners are registered on the manager and events are dispatched through the manager.
 *
 * A listener can be an object that implements listener method (method named as event names) or ANY PHP callback function
 * such as user function, anonymous function, closure, functor... Again, ANY PHP callback is allowed.
 *
 * @category	iMSCP
 * @package		iMSCP_Events
 * @subpackage	Manager
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.6
 */
class iMSCP_Events_Manager implements iMSCP_Events_Manager_Interface
{
	/**
	 * @var iMSCP_Events_Manager
	 */
	protected static $_instance;

	/**
	 * @var iMSCP_Events_Listeners_Stack[] Array that contains events listeners stacks.
	 */
	protected $_events = array();

	/**
	 * Singleton object - Make new unavailable.
	 */
	protected function __construct()
	{

	}

	/**
	 * Singleton object - Make clone unavailable.
	 *
	 * @return void
	 */
	protected function __clone()
	{

	}

	/**
	 * Implements Singleton design pattern.
	 *
	 * @static
	 * @return iMSCP_Events_Manager
	 */
	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Reset instance.
	 *
	 * @static
	 * @return void
	 */
	public static function resetInstance()
	{
		self::$_instance = null;
	}

	/**
	 * Dispatches an event to all registered listeners.
	 *
	 * @throws iMSCP_Events_Manager_Exception	When an listener is an object that do not implement the listener method
	 *											or when the listener is not a valid PHP callback
	 * @param string $eventName					The name of the event to dispatch.
	 * @param array|ArrayAccess $arguments		Array of arguments (eg. an associative array)
	 * @return iMSCP_Events_Listeners_ResponseCollection
	 */
	public function dispatch($eventName, $arguments = array())
	{
		$responses = new iMSCP_Events_Listeners_ResponseCollection();

		if (!$eventName instanceof iMSCP_Events_Description) {
			$event = new iMSCP_Events_Event($eventName, $arguments);
		} else {
			$event = $eventName;
			$eventName = $event->getName();
		}

		if (isset($this->_events[$eventName])) {
			foreach ($this->_events[$eventName]->getIterator() as $listener) {
				if (is_callable($listener)) { // user function, closure, functor...
					$responses->push(call_user_func_array($listener, array($event)));
				} elseif (is_object($listener)) { // object method
					if (is_callable(array($listener, $eventName))) {
						$responses->push($listener->$eventName($event));
					} else {
						require_once 'iMSCP/Events/Exception.php';
						throw new iMSCP_Events_Manager_Exception(sprintf(
								'%s object must implement the %s() listener method or be a functor.',
								get_class($listener), $eventName)
						);
					}
				} else {
					require_once 'iMSCP/Events/Exception.php';
					throw new iMSCP_Events_Manager_Exception("Listener must be a valid callback function or an object.");
				}

				// Stop the event propagation if asked
				if ($event->propagationIsStopped()) {
					$responses->setStopped(true);
					break;
				}
			}
		}

		return $responses;
	}

	/**
	 * Registers an event listener that listens on the specified events.
	 *
	 * @param  string|array $eventNames		The event(s) to listen on.
	 * @param  callback|object $listener	Listener callback function or object.
	 * @param  int $priority				The higher this value, the earlier an event listener will be triggered in
	 *										the chain of the specified events.
	 *
	 * @return iMSCP_Events_Manager_Interface Provide fluent interface, returns self
	 */
	public function registerListener($eventNames, $listener, $priority = 1)
	{
		if (is_string($eventNames)) {
			if (!isset($this->_events[$eventNames])) {
				$this->_events[$eventNames] = new iMSCP_Events_Listeners_Stack();
			}

			$this->_events[$eventNames]->addListener($listener, $priority);
		} elseif (is_array($eventNames)) {
			foreach ($eventNames as $eventName) {
				if (!isset($this->_events[$eventName])) {
					$this->_events[$eventName] = new iMSCP_Events_Listeners_Stack();
				}

				$this->_events[$eventName]->addListener($listener, $priority);
			}
		} else {
			throw new iMSCP_Events_Exception(
				sprintf(__CLASS__ . '::' . __FUNCTION__ . ' expects an array or string, %s given.', gettype($eventNames))
			);
		}

		return $this;
	}

	/**
	 * Unregister an event listener from the given event.
	 *
	 * Note: For now, it's only possible to remove a listener implemented as object.
	 *
	 * @thrown iMSCP_Events_Exception If $eventName is not a string
	 * @param  string $eventName The event to remove a listener from.
	 * @param  object $listener The listener object to remove.
	 * @return bool TRUE if $listener is found and unregistered, FALSE otherwise
	 */
	public function unregisterListener($eventName, $listener)
	{
		if (is_string($eventName)) {
			if (isset($this->_events[$eventName])) {
				$retVal = $this->_events[$eventName]->removeListener($listener);
			} else {
				$retVal = false;
			}
		} else {
			throw new iMSCP_Events_Exception(
				sprintf(__CLASS__ . '::' . __FUNCTION__ . '() expects a string, %s given.', gettype($eventName)
				)
			);
		}

		if (!$this->hasListener($eventName)) {
			unset($this->_events[$eventName]);
		}

		return $retVal;
	}

	/**
	 * Returns the listeners of a specific event or all listeners.
	 *
	 * @param string|null $eventName The name of the event.
	 * @return array The event listeners for the specified event, or all event listeners by event name.
	 */
	public function getListeners($eventName = null)
	{
		if (null !== $eventName) {
			if (isset($this->_events[$eventName])) {
				return $this->_events[$eventName]->getListeners();
			} else {
				return array();
			}
		}

		$events = array();

		foreach ($this->_events as $eventName => $event) {
			$events[$eventName] = $event->getListeners();
		}

		return $events;
	}

	/**
	 * Checks whether an event has any registered listeners.
	 *
	 * @param string $eventName The name of the event.
	 * @return bool TRUE if the specified event has any listeners, FALSE otherwise.
	 */
	public function hasListener($eventName)
	{
		return (bool)count($this->getListeners($eventName));
	}
}
