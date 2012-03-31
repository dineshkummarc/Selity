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

// Include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (!isset($_GET['domain_id'])) {
	redirectTo('manage_users.php');
}

if (!is_numeric($_GET['domain_id'])) {
	redirectTo('manage_users.php');
}

$domain_id = $_GET['domain_id'];

$query = "
	SELECT
		`domain_name`,
		`domain_status`
	FROM
		`domain`
	WHERE
		`domain_id` = ?
";

$rs = exec_query($query, $domain_id);

$location = 'admin';

if ($rs->fields['domain_status'] == $cfg->ITEM_OK_STATUS) {
	$action = "disable";
	change_domain_status($domain_id, $rs->fields['domain_name'], $action, $location);
} else if ($rs->fields['domain_status'] == $cfg->ITEM_DISABLED_STATUS) {
	$action = "enable";
	change_domain_status($domain_id, $rs->fields['domain_name'], $action, $location);
} else {
	redirectTo('manage_users.php');
}
