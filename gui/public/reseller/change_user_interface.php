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

// Include needed libraries
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

// Check for login
check_login(__FILE__);

// Switch back to admin
if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id']) && isset($_GET['action']) &&
	$_GET['action'] == 'go_back'
) {
	change_user_interface($_SESSION['user_id'], $_SESSION['logged_from_id']);
} elseif (isset($_SESSION['user_id']) && isset($_GET['to_id'])) { // Switch to customer
	$toUserId = intval($_GET['to_id']);

	// Admin logged as reseller:
	if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {
		$fromUserId = $_SESSION['logged_from_id'];
	} else { // reseller to customer
		$fromUserId = $_SESSION['user_id'];

		if (who_owns_this($toUserId, 'client') != $fromUserId) {
			set_page_message(tr('Wrong request.'), 'error');
			redirectTo('users.php');
		}
	}

	change_user_interface($fromUserId, $toUserId);
} else {
	set_page_message(tr('Wrong request.'), 'error');
	redirectTo('index.php');
}
