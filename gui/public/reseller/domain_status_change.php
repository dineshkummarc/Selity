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

/************************************************************************************
 * Main script
 */

// Include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (!isset($_GET['domain_id']) || !is_numeric($_GET['domain_id'])) {
    set_page_message(tr('Wrong domain ID.'), 'error');
    redirectTo('users.php?psi=last');
}

// so we have domain id and let's disable or enable it
$domain_id = $_GET['domain_id'];

// check status to know if have to disable or enable it
$query = "
	SELECT
		`domain_name`, `domain_status`, `domain_created_id`
	FROM
		`domain`
	WHERE
		`domain_id` = ?
";
$stmt = exec_query($query, $domain_id);

// let's check if this reseller has rights to disable/enable this domain
// If we are logged as admin and we have created reseller account, we can also
// suspend the domain
if ($stmt->fields['domain_created_id'] != $_SESSION['user_id'] &&
    $_SESSION['logged_from_id'] != $_SESSION['user_created_by']
) {
    set_page_message(tr('You are not allowed to perform this operation.'), 'error');
    redirectTo('users.php?psi=last');
}

if ($stmt->fields['domain_status'] == $cfg->ITEM_OK_STATUS) {
    set_page_message(tr('Domain account scheduled for suspension.'), 'success');
    change_domain_status($domain_id, $stmt->fields['domain_name'], 'disable', 'reseller');
} elseif ($stmt->fields['domain_status'] == $cfg->ITEM_DISABLED_STATUS) {
    set_page_message(tr('Domain account scheduled for activation.'), 'success');
    change_domain_status($domain_id, $stmt->fields['domain_name'], 'enable', 'reseller');
} else {
    redirectTo('users.php?psi=last');
}
