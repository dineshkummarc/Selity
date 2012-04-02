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
 * Script functions
 */

/**
 * Validate order key.
 *
 * @param int $orderId ID in table orders
 * @param string $key hash value to compare with
 * @return boolean true - validation correct
 */
function validateOrderKey($orderId, $key)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$result = false;

	$query = "SELECT * FROM `orders` WHERE `id` = ? AND `status` = ?";
	$stmt = exec_query($query, array($orderId, $cfg->ITEM_ORDER_UNCONFIRMED_STATUS));

	if ($stmt->recordCount() == 1) {
		$domain_name = $stmt->fields['domain_name'];
		$admin_id = $stmt->fields['user_id'];
		$coid = isset($cfg->CUSTOM_ORDERPANEL_ID) ? $cfg->CUSTOM_ORDERPANEL_ID : '';
		$ckey = sha1($orderId . '-' . $domain_name . '-' . $admin_id . '-' . $coid);

		if ($ckey == $key) {
			$result = true;
		}
	}

	return $result;
}

/**
 * Set order to confirmed so that reseller can activate this.
 *
 * @param int $orderId Order unique identifier
 */
function confirmOrder($orderId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "SELECT * FROM `orders` WHERE `id` = ?";
	$stmt = exec_query($query, $orderId);

	if ($stmt->recordCount() == 1) {
		$query = "UPDATE `orders` SET `status` = ? WHERE `id` = ?";
		exec_query($query, array($cfg->ITEM_ORDER_CONFIRMED_STATUS, $orderId));

		$resellerId = $stmt->fields['user_id'];
		$domainName = $stmt->fields['domain_name'];
		$userFirstName = $stmt->fields['fname'];
		$userLastName = $stmt->fields['lname'];
		$uemail = $stmt->fields['email'];
		$name = trim($userFirstName . ' ' . $userLastName);

		$data = get_order_email($resellerId);

		$fromName = $data['sender_name'];
		$fromEmail = $data['sender_email'];

		$search [] = '{DOMAIN}';
		$replace[] = $domainName;
		$search [] = '{MAIL}';
		$replace[] = $uemail;
		$search [] = '{NAME}';
		$replace[] = $name;

		if ($fromName) {
			$from = '"' . encode($fromName) . "\" <" . $fromEmail . ">";
		} else {
			$from = $fromEmail;
		}

		$subject = encode(tr('Selity - Service Mailer - You have a new order'));
		$message = tr('

Dear {RESELLER},

You have received a new order from {NAME} <{MAIL}> for the domain {DOMAIN}.

Please login into your i-MSCP control panel for more details.

______________________
i-MSCP Service Mailer

', true);

		$search [] = '{RESELLER}';
		$replace[] = (!empty($fromName)) ? $fromName : tr('reseller');
		$message = str_replace($search, $replace, $message);
		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

		$headers = "From: " . $from . "\n";
		$headers .= "MIME-Version: 1.0\n" . "Content-Type: text/plain; charset=utf-8\n" .
			"Content-Transfer-Encoding: 8bit\n" . "X-Mailer: i-MSCP " .
			$cfg->Version . " Service Mailer";

		mail($from, $subject, $message, $headers);
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

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['k'])) {
	throw new iMSCP_Exception_Production(tr('You do not have permission to access this interface.'));
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/simple.tpl',
		'page' => '/box.tpl',
		'page_message' => 'layout',
		'backlink_block' => 'page'
	)
);

$tpl->assign('THEME_CHARSET', tr('encoding'));

if (validateOrderKey($_GET['id'], $_GET['k'])) {
	confirmOrder($_GET['id']);
	$msg = tr('Your order has been confirmed and is being processed... You will receive a mail after verifying.');
} else {
	$msg = tr('Your order has not been found in our database. Perhaps already confirmed?');
	write_log('An order was not found in database.', E_USER_WARNING);
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Order Panel / Order confirmation'),
		'CONTEXT_CLASS' => 'box_message',
		'productLongName' => tr('multiserver hosting control panel'),
		'productLink' => 'http://selity.net',
		'productCopyright' => tr('© 2010-2012 Selity Team<br/>All Rights Reserved'),
		'BOX_MESSAGE_TITLE' => tr('Order confirmation'),
		'BOX_MESSAGE' => $msg,
		'BACKLINK_BLOCK' => ''
	)
);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
