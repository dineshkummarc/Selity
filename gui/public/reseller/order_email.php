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

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

/************************************************************************************
 * Main script
 */
check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/order_email.tpl',
		'page_message' => 'layout'));

$user_id = $_SESSION['user_id'];
$data = get_order_email($user_id);

if (isset($_POST['uaction']) && $_POST['uaction'] == 'order_email') {
	$data['subject'] = clean_input($_POST['auto_subject']);
	$data['message'] = clean_input($_POST['auto_message']);

	if ($data['subject'] == '') {
		set_page_message(tr('You must specify a subject.'), 'error');
	} else if ($data['message'] == '') {
		set_page_message(tr('You must specify a message.'), 'error');
	} else {
		set_order_email($user_id, $data);
		set_page_message(tr('Template for Auto email successfully updated.'), 'success');
	}
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - Reseller/Order email setup'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

generateNavigation($tpl);

$tpl->assign(
	array(
		'TR_EMAIL_SETUP' => tr('Email setup'),
		'TR_MANAGE_ORDERS' => tr('Manage orders'),
		'TR_MESSAGE_TEMPLATE_INFO' => tr('Message template info'),
		'TR_USER_LOGIN_NAME' => tr('User login (system) name'),
		'TR_USER_DOMAIN' => tr('Domain name'),
		'TR_USER_REAL_NAME' => tr('User (first and last) name'),
		'TR_ACTIVATION_LINK' => tr('Activation Link'),
		'TR_MESSAGE_TEMPLATE' => tr('Message template'),
		'TR_SUBJECT' => tr('Subject'),
		'TR_MESSAGE' => tr('Message'),
		'TR_SENDER_EMAIL' => tr('Senders email'),
		'TR_SENDER_NAME' => tr('Senders name'),
		'TR_APPLY_CHANGES' => tr('Apply changes'),
		'SUBJECT_VALUE' => tohtml($data['subject']),
		'MESSAGE_VALUE' => tohtml($data['message']),
		'SENDER_EMAIL_VALUE' => tohtml($data['sender_email']),
		'SENDER_NAME_VALUE' => tohtml(!empty($data['sender_name'])
			? $data['sender_name'] : tr('Unknown'))));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
