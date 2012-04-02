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

// TODO describe available PLACEHOLDERS

/**********************************************************************************
 * Script functions
 */

/**
 * Save custom order template.
 * @return void
 */
function reseller_updateOrderTemplate()
{
	$user_id = $_SESSION['user_id'];
	$header = $_POST['header'];
	$footer = $_POST['footer'];

	$query = "SELECT `id` FROM `orders_settings` WHERE `user_id` = ?";
	$stmt = exec_query($query, $user_id);

	if ($stmt->rowCount()) {
		// update query
		$query = "UPDATE `orders_settings` SET `header` = ?, `footer` = ? WHERE `user_id` = ?";
		exec_query($query, array($header, $footer, $user_id));
	} else {
		// create query
		$query = "
			INSERT INTO
				`orders_settings`(`user_id`, `header`, `footer`)
			VALUES
				(?, ?, ?)
		";

		exec_query($query, array($user_id, $header, $footer));
	}
}

/**
 * Reset order template.
 */
function reseller_resetOrderTemplate()
{
	$query = "DELETE FROM `orders_settings` WHERE `user_id` = ?";
	exec_query($query, $_SESSION['user_id']);
}

/**********************************************************************************
 * Main script
 */

// Include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/order_settings.tpl',
		'page_message' => 'layout',
		'purchase_header' => 'page',
		'purchase_footer' => 'page'));


if (isset($_POST['update']) && isset($_POST['header']) && $_POST['header'] !== '' && isset ($_POST['footer'])
	&& $_POST['footer'] !== ''
) {
	reseller_updateOrderTemplate();
	set_page_message(tr('Template successfully updated.'), 'success');
} elseif (isset($_POST['reset'])) {
	reseller_resetOrderTemplate();
	set_page_message(tr('Template successfully reseted.'), 'success');
}

$coid = isset($cfg->CUSTOM_ORDERPANEL_ID) ? $cfg->CUSTOM_ORDERPANEL_ID : '';

$url = $cfg->BASE_SERVER_VHOST_PREFIX . $cfg->BASE_SERVER_VHOST . '/orderpanel/index.php?';
$url .= "coid=$coid&amp;user_id={$_SESSION['user_id']}";

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - Reseller/Order settings'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_ORDER_TEMPLATE' => tr('Order template'),
		'TR_IMPLEMENT_INFO' => tr('Implementation URL'),
		'TR_HEADER' => tr('Header'),
		'TR_IMPLEMENT_URL' => $url,
		'TR_FOOTER' => tr('Footer'),
		'TR_PREVIEW' => tr('Preview'),
		'TR_UPDATE' => tr('Update'),
		'TR_RESET' => tr('Reset')));

list($header, $footer) = gen_purchase_haf($_SESSION['user_id'], true);

$tpl->assign(
	array(
		'PURCHASE_HEADER' => $header,
		'PURCHASE_FOOTER' => $footer));

set_page_message(tr('You must first update the template to preview your changes.'), 'info');

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
