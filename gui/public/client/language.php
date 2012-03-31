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
 * Main script
 */

// Include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic(
	array(
		 'page' => 'client/language.tpl',
		 'page_message' => 'layout',
		 'def_language' => 'page'));

// Getting current customer language
if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {
	list($customerCurrentLanguage) = get_user_gui_props($_SESSION['user_id']);
} else {
	$customerCurrentLanguage = $_SESSION['user_def_lang'];
}

if (!empty($_POST)) {
	$customerId = $_SESSION['user_id'];
	$customerNewLanguage = clean_input($_POST['def_language']);

	if ($customerCurrentLanguage != $customerNewLanguage) {

		$query = "UPDATE `user_gui_props` SET `lang` = ? WHERE `user_id` = ?";
		exec_query($query, array($customerNewLanguage, $_SESSION['user_id']));

		if (!isset($_SESSION['logged_from_id'])) {
			unset($_SESSION['user_def_lang']);
			$_SESSION['user_def_lang'] = $customerNewLanguage;
		}

		set_page_message(tr('Language successfully updated.'), 'success');
	} else {
		set_page_message(tr("Nothing's been changed."), 'info');
	}

	// Force update on next load
	redirectTo('index.php');
}

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Selity - Client / Change Language'),
		 'TR_TITLE_CHANGE_LANGUAGE' => tr('Change language'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_GENERAL_INFO' => tr('General information'),
		 'TR_LANGUAGE' => tr('Language'),
		 'TR_CHOOSE_LANGUAGE' => tr('Choose your language'),
		 'TR_UPDATE' => tr('Update')));

generateNavigation($tpl);
gen_def_language($tpl, $customerCurrentLanguage);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
