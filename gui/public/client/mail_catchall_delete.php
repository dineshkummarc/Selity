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

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('mail')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_GET['id']) && $_GET['id'] !== '') {
	$mail_id = $_GET['id'];
	$item_delete_status = $cfg->ITEM_DELETE_STATUS;
	$dmn_id = get_user_domain_id($_SESSION['user_id']);

	$query = "
		SELECT
			`mail_id`
		FROM
			`mail_users`
		WHERE
			`domain_id` = ?
		AND
			`mail_id` = ?
	";

	$rs = exec_query($query, array($dmn_id, $mail_id));

	if (!$rs->recordCount()) {
		redirectTo('mail_catchall.php');
	}

	$query = "UPDATE `mail_users` SET `status` = ? WHERE `mail_id` = ?";
	exec_query($query, array($item_delete_status, $mail_id));

	send_request();
	write_log($_SESSION['user_logged'].': deletes email catch all!', E_USER_NOTICE);
	set_page_message(tr('Catch all account scheduled for deletion.'), 'success');
	redirectTo('mail_catchall.php');

} else {
	redirectTo('mail_catchall.php');
}
