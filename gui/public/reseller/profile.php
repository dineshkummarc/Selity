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

/*******************************************************************************
 * Script functions
 */

/**
 * Generates page.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 */
function reseller_generatePage($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "SELECT domain_created from admin where admin_id = ?";
	$stmt = exec_query($query, (int)$_SESSION['user_id']);

	$tpl->assign(
		array(
			'TR_ACCOUNT_SUMMARY' => tr('Account summary'),
			'TR_USERNAME' => tr('Username'),
			'USERNAME' => tohtml($_SESSION['user_logged']),
			'TR_ACCOUNT_TYPE' => tr('Account type'),
			'ACCOUNT_TYPE' => $_SESSION['user_type'],
			'TR_REGISTRATION_DATE' => tr('Registration date'),
			'REGISTRATION_DATE' => ($stmt->fields['domain_created'] != 0) ? date($cfg->DATE_FORMAT, $stmt->fields['domain_created']) : tr('Unknown')
		));
}

/*******************************************************************************
 * Main script
 */

// Include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login(__FILE__);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/profile.tpl',
		'page_message' => 'layout'));

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Selity - Reseller / My Profile'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()));

generateNavigation($tpl);
reseller_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
