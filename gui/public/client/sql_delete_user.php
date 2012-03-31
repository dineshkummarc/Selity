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
if (!customerHasFeature('sql')) {
	redirectTo('index.php');
} elseif (isset($_GET['id'])) {
	$sqlUserId = intval($_GET['id']);

	try {
		iMSCP_Database::getInstance()->beginTransaction();

		if (!sql_delete_user(get_user_domain_id($_SESSION['user_id']), $sqlUserId)) {
			throw new iMSCP_Exception(sprintf('SQL user with ID %d no found in iMSCP database or not owned by customer with ID %d.', $_SESSION['user_id'], $sqlUserId));
		}

		iMSCP_Database::getInstance()->commit();

		set_page_message(tr('SQL user successfully deleted.'), 'success');
		write_log(sprintf("{$_SESSION['user_logged']} deleted SQL user with ID %d", $sqlUserId), E_USER_NOTICE);
	} catch (iMSCP_Exception $e) {
		iMSCP_Database::getInstance()->rollBack();

		set_page_message(tr('System was unable to remove the SQL user.'), 'error');
		write_log(sprintf("System was unable to delete SQL user with ID %d. Message was: %s", $sqlUserId, $e->getMessage()), E_USER_ERROR);
	}
}

redirectTo('sql_manage.php');
