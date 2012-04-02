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
 * Representation of an event
 *
 * Encapsulates the parameters passed, and provides some behavior for interacting with the events manager.
 *
 * Note: Most part of this code was borrowed to Zend Framework 2.
 *
 * @category	iMSCP
 * @package		iMSCP_Events
 * @author		Laurent Declercq <l.declercq@i-mscp.net>
 * @version		0.0.2
 */
class iMSCP_Events_Event implements iMSCP_Events_Description
{
	/**
	 * @var string Event name
	 */
	protected $name;

	/**
	 * @var array|ArrayAccess|object The event parameters
	 */
	protected $params = array();

	/**
	 * @var bool Whether or not to stop propagation
	 */
	protected $stopPropagation = false;

	/**
	 * Constructor.
	 *
	 * @param string $name Event name
	 * @param array|ArrayAccess $params
	 */
	public function __construct($name = null, $params = null)
	{
		if (null !== $name) {
			$this->setName($name);
		}

		if (null !== $params) {
			$this->setParams($params);
		}
	}

	/**
	 * Returns event name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set parameters.
	 *
	 * Overwrites parameters
	 *
	 * @param  array|ArrayAccess|object $params
	 * @return iMSCP_Events_Event Provides fluent interface, returns self
	 */
	public function setParams($params)
	{
		if (!is_array($params) && !is_object($params)) {
			throw new iMSCP_Events_Exception('Event parameters must be an array or object');
		}

		$this->params = $params;

		return $this;
	}

	/**
	 * Returns all parameters.
	 *
	 * @return array|object|ArrayAccess
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * Return an individual parameter.
	 *
	 * If the parameter does not exist, the $default value will be returned.
	 *
	 * @param  string|int $name Parameter name
	 * @param  mixed $default Default value to be returned if $name doesn't exists
	 * @return mixed
	 */
	public function getParam($name, $default = null)
	{
		// Check in params that are arrays or implement array access
		if (is_array($this->params) || $this->params instanceof ArrayAccess) {
			if (!isset($this->params[$name])) {
				return $default;
			}

			return $this->params[$name];
		}

		// Check in normal objects
		if (!isset($this->params->{$name})) {
			return $default;
		}

		return $this->params->{$name};
	}

	/**
	 * Set the event name.
	 *
	 * @param  string $name Event Name
	 * @return iMSCP_Events_Event Provides fluent interface, returns self
	 */
	public function setName($name)
	{
		$this->name = (string)$name;

		return $this;
	}

	/**
	 * Set an individual parameter to a value.
	 *
	 * @param string|int $name Parameter name
	 * @param mixed $value Parameter value
	 * @return iMSCP_Events_Event
	 */
	public function setParam($name, $value)
	{
		if (is_array($this->params) || $this->params instanceof ArrayAccess) {
			// Arrays or objects implementing array access
			$this->params[$name] = $value;
		} else {
			// Objects
			$this->params->{$name} = $value;
		}

		return $this;
	}

	/**
	 * Stop further event propagation.
	 *
	 * @param  bool $flag TRUE to stop propagation, FALSE otherwise
	 * @return void
	 */
	public function stopPropagation($flag = true)
	{
		$this->stopPropagation = (bool)$flag;
	}

	/**
	 * Is propagation stopped?
	 *
	 * @return bool TRUE if propagation is stopped, FALSE otherwise
	 */
	public function propagationIsStopped()
	{
		return $this->stopPropagation;
	}
}
