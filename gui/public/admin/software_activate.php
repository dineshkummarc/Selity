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

if (isset($_GET['id']) AND is_numeric($_GET['id'])) {
	$query="
		SELECT
			`software_id`,
			`software_archive`,
			`reseller_id`
		FROM
			`web_software`
		WHERE
			`software_id` = ?
		AND
			`software_active` = 0
	";
	$rs = exec_query($query, $_GET['id']);
	if ($rs->recordCount() != 1) {
		set_page_message(tr('Wrong software id.'), 'error');
		redirectTo('software_manage.php');
	} else {
		$update="
			UPDATE
				`web_software`
			SET
				`software_active` = 1
			WHERE
				`software_id` = ?
		";
		$res = exec_query($update, $_GET['id']);
		$query="
			SELECT
				`software_id`,
				`software_name`,
				`software_version`,
				`software_language`,
				`reseller_id`,
				`software_archive`
			FROM
				`web_software`
			WHERE
				`software_id` = ?
		";
		$res = exec_query($query, $_GET['id']);
		
		send_activated_sw (
			$res->fields['reseller_id'],
			$res->fields['software_archive'].".tar.gz",
			$res->fields['software_id']
		);
		
		set_page_message(tr('Software was activated.'), 'success');
		redirectTo('software_manage.php');
	}
} else {
	set_page_message(tr('Wrong software id.'), 'error');
	redirectTo('software_manage.php');
}
