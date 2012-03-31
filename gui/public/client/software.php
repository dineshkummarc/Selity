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
 *  Script functions
 */

/**
 * client_generatePageLists.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $customerId Customer unique identifier
 * @return void
 */
function client_generatePageLists($tpl, $customerId)
{
    $domainProperties = get_domain_default_props($customerId, true);
    $software_poss = gen_software_list($tpl, $domainProperties['domain_id'], $domainProperties['domain_created_id']);
    $tpl->assign('TOTAL_SOFTWARE_AVAILABLE', $software_poss);
}

/************************************************************************************
 * Main script
 */

// Include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('aps')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/software.tpl',
		'page_message' => 'layout',
		'software_message' => 'page',
		'software_item' => 'page',
		'software_action_delete' => 'page',
		'software_action_install' => 'page',
		'software_total' => 'page',
		'no_software' => 'page',
		'no_software_support' => 'page',
		'del_software_support' => 'page',
		'del_software_item' => 'page',
		't_software_support' => 'page'));


$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Selity - Client / Webtools / Softwares'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_SOFTWARE' => tr('Software'),
		 'TR_VERSION' => tr('Version'),
		 'TR_LANGUAGE' => tr('Language'),
		 'TR_TYPE' => tr('Type'),
		 'TR_NEED_DATABASE' => tr('Database'),
		 'TR_STATUS' => tr('Status'),
		 'TR_ACTION' => tr('Action'),
		 'TR_SOFTWARE_AVAILABLE' => tr('Available softwares'),
		 'TR_SOFTWARE_ASC' => 'software.php?sortby=name&order=asc',
		 'TR_SOFTWARE_DESC' => 'software.php?sortby=name&order=desc',
		 'TR_TYPE_ASC' => 'software.php?sortby=type&order=asc',
		 'TR_TYPE_DESC' => 'software.php?sortby=type&order=desc',
		 'TR_NEED_DATABASE_ASC' => 'software.php?sortby=database&order=asc',
		 'TR_NEED_DATABASE_DESC' => 'software.php?sortby=database&order=desc',
		 'TR_STATUS_ASC' => 'software.php?sortby=status&order=asc',
		 'TR_STATUS_DESC' => 'software.php?sortby=status&order=desc',
		 'TR_LANGUAGE_ASC' => 'software.php?sortby=language&order=asc',
		 'TR_LANGUAGE_DESC' => 'software.php?sortby=language&order=desc'));

generateNavigation($tpl);
client_generatePageLists($tpl, $_SESSION['user_id']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
