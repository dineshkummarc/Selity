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
 * Note: Most part of this code was borrowed to Zend Framework 2.
 *
 * @category	iMSCP
 * @package		Events
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.1
 */
interface iMSCP_Events_Description
{
	/**
	 * Returns event name.
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns parameters passed to the event.
	 *
	 * @return array|ArrayAccess
	 */
	public function getParams();

	/**
	 * Returns a single parameter by name.
	 *
	 * @param  string $name
	 * @param  mixed $default Default value to return if parameter does not exist
	 * @return mixed
	 */
	public function getParam($name, $default = null);

	/**
	 * Set the event name.
	 *
	 * @param  string $name Event name
	 * @return iMSCP_Events_Description Provides fluent interface, return self
	 */
	public function setName($name);

	/**
	 * Set event parameters.
	 *
	 * @param  string $params
	 * @return iMSCP_Events_Description Provides fluent interface, return self
	 */
	public function setParams($params);

	/**
	 * Set a single parameter by name.
	 *
	 * @param  string $name Parameter name
	 * @param  mixed $value Parameter value
	 * @return iMSCP_Events_Description Provides fluent interface, return self
	 */
	public function setParam($name, $value);

	/**
	 * Indicate whether or not the parent iMSCP_Events_Manager_Interface should stop propagating events
	 *
	 * @param  bool $flag
	 * @return void
	 */
	public function stopPropagation($flag = true);

	/**
	 * Has this event indicated event propagation should stop?
	 *
	 * @return bool
	 */
	public function propagationIsStopped();

}
