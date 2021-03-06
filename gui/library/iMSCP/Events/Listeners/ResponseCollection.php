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
 * Responses class.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Events_Listeners
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.1
 */
class iMSCP_Events_Listeners_ResponseCollection extends SplStack
{
	/**
	 * @var bool
	 */
	protected $isStopped = false;

	/**
	 * Did the last response provided trigger a short circuit of the stack?
	 *
	 * @return bool
	 */
	public function isStopped()
	{
		return $this->isStopped;
	}

	/**
	 * Mark the collection as stopped (or its opposite)
	 *
	 * @param  bool $flag
	 * @return iMSCP_Events_Listeners_ResponseCollection
	 */
	public function setStopped($flag)
	{
		$this->isStopped = (bool)$flag;
		return $this;
	}

	/**
	 * Convenient access to the first listener method return value.
	 *
	 * @return mixed The first handler return value
	 */
	public function first()
	{
		return parent::bottom();
	}

	/**
	 * Convenient access to the last listener method return value.
	 *
	 * If the collection is empty, returns null. Otherwise, returns value
	 * returned by last handler.
	 *
	 * @return mixed The last handler return value
	 */
	public function last()
	{
		if (count($this) === 0) {
			return null;
		}

		return parent::top();
	}

	/**
	 * Check if any of the responses match the given value.
	 *
	 * @param  mixed $value The value to look for among responses
	 * @return bool
	 */
	public function contains($value)
	{
		foreach ($this as $response) {
			if ($response === $value) {
				return true;
			}
		}

		return false;
	}
}
