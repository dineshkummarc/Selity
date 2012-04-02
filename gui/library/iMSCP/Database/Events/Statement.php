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

/** @see iMSCP_Database_Events_Database */
require_once 'iMSCP/Database/Events/Database.php';

/**
 * Base class for events thrown in the iMSCP_Database component.
 *
 * @category    iMSCP
 * @package     iMSCP_Database
 * @package     Events
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class iMSCP_Database_Events_Statement extends iMSCP_Database_Events_Database
{
    /**
     * Returns a PDOstatement.
     *
     * @return PDOStatement
     */
    public function getStatement()
    {
		return $this->getParam('statement');
    }

	/**
	 * Returns the query string.
	 *
	 * @return string
	 */
	public function getQueryString()
	{
		return $this->getStatement()->queryString;
	}
}
