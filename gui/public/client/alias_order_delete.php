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
if (!customerHasFeature('domain_aliases')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_GET['del_id']) && !empty($_GET['del_id'])) {
	$domainAliasId = (int)$_GET['del_id'];
} else {
	set_page_message(tr('Wrong request.'), 'error');
	redirectTo('domains_manage.php');
}

$domainId = get_user_domain_id($_SESSION['user_id']);

$query = 'DELETE FROM `domain_aliasses` WHERE `alias_id` = ? AND `domain_id` = ? AND `alias_status` = ?';
$stmt = exec_query($query, array($domainAliasId, $domainId, $cfg->ITEM_ORDERED_STATUS));

if($stmt->rowCount()) {
	set_page_message(tr('Order for domain alias deleted.'), 'success');
} else {
	set_page_message(tr('Order not found. Nothing been deleted.'), 'error');
}

redirectTo('domains_manage.php');
