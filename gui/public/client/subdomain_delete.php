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
if (!customerHasFeature('subdomains')) {
    redirectTo('index.php');
}

if (isset($_GET['id']) && $_GET['id'] !== '') {
	$sub_id = $_GET['id'];
	$dmn_id = get_user_domain_id($_SESSION['user_id']);

	$query = "
		SELECT
			`subdomain_id`, `subdomain_name`
		FROM
			`subdomain`
		WHERE
			`domain_id` = ?
		AND
			`subdomain_id` = ?
	";

	$rs = exec_query($query, array($dmn_id, $sub_id));
	$sub_name = $rs->fields['subdomain_name'];

	if ($rs->recordCount() == 0) {
		redirectTo('domains_manage.php');
	}

	$query = "SELECT COUNT(`mail_id`) AS cnt FROM `mail_users` WHERE (`mail_type` LIKE '".MT_SUBDOM_MAIL."%' OR `mail_type` = '".MT_SUBDOM_FORWARD."') AND `sub_id` = ?";
	$rs = exec_query($query, $sub_id);

	if ($rs->fields['cnt'] > 0) {
		set_page_message(tr('Subdomain you are trying to remove has email accounts.<br>First remove them.'), 'error');
		redirectTo('domains_manage.php');
	}

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeDeleteSubdomain, array('subdomainId' => $sub_id));

	$query = "
		UPDATE
			`subdomain`
		SET
			`subdomain_status` = 'delete'
		WHERE
			`subdomain_id` = ?
	";
	$rs = exec_query($query, $sub_id);

	$query = "
		UPDATE
			`ssl_certs`
		SET
			`status` = 'delete'
		WHERE
			`id` = ?
		AND
			`type` = 'sub'
	";
	$rs = exec_query($query, $sub_id);

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterDeleteSubdomain, array('subdomainId' => $sub_id));

	update_reseller_c_props(get_reseller_id($dmn_id));
	send_request();

	write_log($_SESSION['user_logged'].": deletes subdomain: " . $sub_name, E_USER_NOTICE);
	set_page_message(tr('Subdomain scheduled for deletion.'), 'success');
	redirectTo('domains_manage.php');

} else {
	redirectTo('domains_manage.php');
}
