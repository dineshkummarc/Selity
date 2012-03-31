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

/********************************************************************************
 * Main script
 */

// Include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (strtolower($cfg->HOSTING_PLANS_LEVEL) != 'admin') {
	redirectTo('index.php');
}

if (isset($_GET['hpid'])) {
	$hostingPlanId = intval($_GET['hpid']);
} else {
	set_page_message(tr('Wrong request'), 'error');
	redirectTo('hosting_plan.php');
	exit; // Useless but avoid IDE warning about possible undefined variable
}

// Check if there is no order for this plan
$stmt = exec_query("SELECT COUNT(`id`) `cnt` FROM `orders` WHERE `plan_id` = ? AND `status` = 'new'", $hostingPlanId);

if ($stmt->fields['cnt'] > 0) {
	set_page_message(tr("Hosting plan can't be deleted, there are active orders."), 'error');
	redirectTo('hosting_plan.php');
}

// Try to delete hosting plan from db
$query = 'DELETE FROM `hosting_plans` WHERE `id` = ?';
exec_query($query, $hostingPlanId);

set_page_message(tr('Hosting plan successfully deleted.'), 'success');
redirectTo('hosting_plan.php');
