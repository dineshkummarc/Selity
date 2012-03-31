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
 * Events Manager interface.
 *
 * The Events Manager interface is the central point of i-MSCP's event listener
 * system. The listeners are registered on the manager, and events are dispatched through
 * the manager.
 *
 * A listener is an object or a callback function that listen on a particular event. The events are defined in many
 * places in the core code or components. When a event is dispatched, the listener methods of all the listeners that
 * listens this event are executed.
 *
 * @category	iMSCP
 * @package		iMSCP_Events
 * @subpackage	Manager
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.4
 */
interface iMSCP_Events_Manager_Interface
{
	/**
	 * Dispatches an event to all registered listeners.
	 *
	 * @throws iMSCP_Events_Manager_Exception	When an listener is an object that do not implement the listener method or
	 * 											when the listener is not a valid PHP callback
	 * @param string $eventName					The name of the event to dispatch.
	 * @param mixed $arguments OPTIONAL			The data to pass to the event listener method.
	 *
	 * @return iMSCP_Events_Listeners_ResponseCollection
	 */
	public function dispatch($eventName, $arguments = array());

	/**
	 * Registers an event listener that listens on the specified events.
	 *
	 * @abstract
	 * @param  string|array $eventNames		The event(s) to listen on.
	 * @param  callback|object $listener	Listener callback or listener object.
	 * @param  int $priority				The higher this value, the earlier an event listener will be triggered in
	 * 										the chain.
	 * @return iMSCP_Events_Manager_Interface Provide fluent interface, returns self
	 */
	public function registerListener($eventNames, $listener, $priority = 1);

	/**
	 * Unregister an event listener from the given event.
	 *
	 * @abstract
	 * @param  string $eventName The event to remove a listener from.
	 * @param  callback|object $listener The listener callback or object to remove.
	 * @return bool TRUE if $listener is found and unregistered, FALSE otherwise
	 */
	public function unregisterListener($eventName, $listener);

	/**
	 * Returns the listeners for the given event or all listeners.
	 *
	 * @abstract
	 * @param  string|null $eventName The name of the event.
	 * @return array The event listeners for the specified event, or all event listeners by event name if $event is NULL.
	 */
	public function getListeners($eventName = null);

	/**
	 * Checks whether an event has any registered listeners.
	 *
	 * @abstract
	 * @param string $eventName The name of the event.
	 * @return bool TRUE if the specified event has any listeners, FALSE otherwise.
	 */
	public function hasListener($eventName);
}
