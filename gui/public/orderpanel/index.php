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
 * functions
 */

/**
 * Generate package list.
 *
 * @throws iMSCP_Exception_Production
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @param  int $user_id User unique identifier
 * @return void
 */
function gen_packages_list($tpl, $user_id)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
		$query = "
			SELECT
				`t1`.*, `t2`.`admin_id`, `t2`.`admin_type`
			FROM
				`hosting_plans` `t1`, `admin` `t2`
			WHERE
				`t2`.`admin_type` = ?
			AND
				`t1`.`reseller_id` = `t2`.`admin_id`
			AND
				`t1`.`status` = ?
			ORDER BY
				`t1`.`id`
		";
		$stmt = exec_query($query, array('admin', 1));
	} else {
		$query = "SELECT * FROM `hosting_plans` WHERE `reseller_id` = ? AND `status` = '1'";
		$stmt = exec_query($query, $user_id);
	}

	if (!$stmt->rowCount()) {
		throw new iMSCP_Exception_Production(tr('No available hosting packages.'));
	} else {
		while (!$stmt->EOF) {
			$description = $stmt->fields['description'];

			$price = $stmt->fields['price'];
			if ($price == 0 || $price == '') {
				$price = "/ " . tr('free of charge');
			} else {
				$price = "/ " . $price . " " . tohtml($stmt->fields['value']) . " " .
					tohtml($stmt->fields['payment']);
			}

			$tpl->assign(array(
				'PACK_NAME' => tohtml($stmt->fields['name']),
				'PACK_ID' => $stmt->fields['id'],
				'USER_ID' => $user_id,
				'PURCHASE' => tr('Purchase'),
				'PACK_INFO' => tohtml($description),
				'PRICE' => $price));

			$tpl->parse('PURCHASE_LIST', '.purchase_list');
			$stmt->moveNext();
		}
	}
}

/************************************************************************************
 * Main script
 */

// Include needed libraries
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$coid = isset($cfg->CUSTOM_ORDERPANEL_ID) ? $cfg->CUSTOM_ORDERPANEL_ID : '';
$bcoid = (empty($coid) || (isset($_GET['coid']) && $_GET['coid'] == $coid));

if (isset($_GET['user_id']) && is_numeric($_GET['user_id']) && $bcoid) {
	$user_id = $_GET['user_id'];
	$_SESSION['order_panel_user_id'] = $user_id;
} elseif (isset($_SESSION['order_panel_user_id'])) {
	$user_id = $_SESSION['order_panel_user_id'];
} else {
	throw new iMSCP_Exception_Production(tr('You do not have permission to access this interface.'));
}

unset($_SESSION['order_panel_plan_id']);

$tpl = new iMSCP_pTemplate();
$tpl->define_no_file('layout', implode('', gen_purchase_haf($user_id)));
$tpl->define_dynamic(
	array(
		'page' => 'orderpanel/index.tpl',
		'page_message' => 'page', // Must be in page here
		'purchase_list' => 'page'
	)
);

gen_packages_list($tpl, $user_id);
generatePageMessage($tpl);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Order Panel / Choose hosting plan'),
		'THEME_CHARSET' => tr('encoding')));

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
