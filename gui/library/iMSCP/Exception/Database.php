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
 * @see iMSCP_Exception
 */
require_once  'iMSCP/Exception.php';

/**
 * Exception used on production by iMSCP_Exception_Handler
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Exception
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.2
 */
class iMSCP_Exception_Database extends iMSCP_Exception
{
	/**
	 * @var string Query that failed.
	 */
	protected $_query = null;

	/**
	 * Constructor.
	 *
	 * @param string $msg Exception Message
	 * @param string $query query Last query executed
	 * @param int $code Exception code
	 * @param Exception $previous OPTIONAL Previous exception
	 */
	public function __construct($msg = '', $query = null, $code = 0, Exception $previous = null)
	{
		parent::__construct($msg, $code, $previous);
		$this->_query = (string) preg_replace("/[\t\n]+/", ' ', $query);
	}

	/**
	 * Gets query.
	 *
	 * @return string
	 */
	public function getQuery()
	{
		return $this->_query;
	}
}
