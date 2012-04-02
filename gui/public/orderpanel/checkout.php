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
 * Generates checkout.
 *
 * @param  int $user_id User unique identifier
 * @param int $plan_id Plan unique identifier
 * @return void
 */
function generateCheckout($user_id, $plan_id)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$date = time();
	$domain_name = $_SESSION['order_panel_domainname'];
	$fname = $_SESSION['order_panel_fname'];
	$lname = $_SESSION['order_panel_lname'];
	$gender = $_SESSION['order_panel_gender'];
	$firm = (isset($_SESSION['order_panel_firm'])) ? $_SESSION['order_panel_firm'] : '';
	$zip = $_SESSION['order_panel_zip'];
	$city = $_SESSION['order_panel_city'];
	$state = $_SESSION['order_panel_state'];
	$country = $_SESSION['order_panel_country'];
	$email = $_SESSION['order_panel_email'];
	$phone = $_SESSION['order_panel_phone'];
	$fax = (isset($_SESSION['order_panel_fax'])) ? $_SESSION['order_panel_fax'] : '';
	$street1 = $_SESSION['order_panel_street1'];
	$street2 = (isset($_SESSION['order_panel_street2'])) ? $_SESSION['order_panel_street2'] : '';

	$query = "
		INSERT INTO `orders` (
            `user_id`, `plan_id`, `date`, `domain_name`, `fname`, `lname`, `gender`,
            `firm`,`zip`, `city`, `state`, `country`, `email`, `phone`, `fax`,
            `street1`, `street2`, `status`
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
	";
	exec_query($query, array($user_id, $plan_id, $date, $domain_name, $fname, $lname,
		$gender, $firm, $zip, $city, $state, $country, $email,
		$phone, $fax, $street1, $street2,
		$cfg->ITEM_ORDER_UNCONFIRMED_STATUS));

	/** @var $db iMSCP_Database */
	$db = iMSCP_Registry::get('db');
	send_order_emails($user_id, $domain_name, $fname, $lname, $email, $db->insertId());

	// Remove useless data
	unset(
	$_SESSION['order_panel_details'], $_SESSION['order_panel_domainname'], $_SESSION['order_panel_fname'],
	$_SESSION['order_panel_lname'], $_SESSION['order_panel_gender'], $_SESSION['order_panel_email'],
	$_SESSION['order_panel_firm'], $_SESSION['order_panel_zip'], $_SESSION['order_panel_city'],
	$_SESSION['order_panel_state'], $_SESSION['order_panel_country'], $_SESSION['order_panel_street1'],
	$_SESSION['order_panel_street2'], $_SESSION['order_panel_phone'], $_SESSION['order_panel_fax'],
	$_SESSION['order_panel_plan_id'], $_SESSION['order_panel_image'], $_SESSION['order_panel_tos']);
}

/************************************************************************************
 * Main script
 */

// Include needed libraries
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptStart);


/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_SESSION['order_panel_user_id']) && isset($_SESSION['order_panel_plan_id'])) {
	$user_id = $_SESSION['order_panel_user_id'];
	$plan_id = $_SESSION['order_panel_plan_id'];
} else {
	throw new iMSCP_Exception_Production(tr('You do not have permission to access this interface.'));
}

if (!isset($_POST['capcode']) || $_POST['capcode'] != $_SESSION['order_panel_image']) {
	set_page_message(tr('Security code is incorrect.'), 'error');
	redirectTo('chart.php');
}

// If term of service field was set (not empty value)
if (isset($_SESSION['order_panel_tos']) && $_SESSION['order_panel_tos'] == true) {
	if (!isset($_POST['tosAccept']) || $_POST['tosAccept'] != 1) {
		set_page_message(tr('You have to accept the Term of Service.'), 'error');
		redirectTo('chart.php');
	}
}

if ((isset($_SESSION['order_panel_fname']) && $_SESSION['order_panel_fname'] != '')
	&& (isset($_SESSION['order_panel_lname']) && $_SESSION['order_panel_lname'] != '')
	&& (isset($_SESSION['order_panel_email']) && $_SESSION['order_panel_email'] != '')
	&& (isset($_SESSION['order_panel_zip']) && $_SESSION['order_panel_zip'] != '')
	&& (isset($_SESSION['order_panel_city']) && $_SESSION['order_panel_city'] != '')
	&& (isset($_SESSION['order_panel_country']) && $_SESSION['order_panel_country'] != '')
	&& (isset($_SESSION['order_panel_street1']) && $_SESSION['order_panel_street1'] != '')
	&& (isset($_SESSION['order_panel_phone']) && $_SESSION['order_panel_phone'] != '')
) {
	generateCheckout($user_id, $plan_id);
} else {
	redirectTo('index.php?user_id=' . $user_id);
}

$tpl = new iMSCP_pTemplate();
$tpl->define_no_file('layout', implode('', gen_purchase_haf($user_id)));
$tpl->define_dynamic(
	array(
		'page' => 'orderpanel/checkout.tpl',
		'page_message' => 'page' // Must be in page here
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Order Panel / Checkout'),
		'CHECK_OUT' => tr('Check Out'),
		'THANK_YOU_MESSAGE' => tr("<strong>Thank you for purchasing.</strong><br /><br />An email has been sent to your email address for confirmation. Do not forget to click on the link in email to validate your order."),
		'THEME_CHARSET' => tr('encoding')
	)
);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
