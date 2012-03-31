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
 * Generate page and return software unique identifier.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $customerId Customer unique identifier
 * @return int software unique identifier
 */
function client_generatePage($tpl, $customerId)
{
	if (!isset($_GET['id']) || $_GET['id'] === '' || !is_numeric($_GET['id'])) {
		set_page_message(tr('Wrong request'), 'error');
		redirectTo('software.php');
		exit; // Useless but avoid IDE warning about possible undefined variable
	} else {
		$software_id = intval($_GET['id']);
	}

	$domainProperties = get_domain_default_props($customerId, true);

	get_software_props (
		$tpl, $domainProperties['domain_id'], $software_id, $domainProperties['domain_created_id'],
		$domainProperties['domain_sqld_limit']);

	return $software_id;
}

/************************************************************************************
 * Main program
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
		'page' => 'client/software_view.tpl',
		'page_message' => 'layout',
		'software_message' => 'page',
		'software_install' => 'page',
		'installed_software_info' => 'page',
		'software_item' => 'page',
		'no_software' => 'page'));

$software_id = client_generatePage($tpl, $_SESSION['user_id']);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - Software details'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'SOFTWARE_ID' => $software_id,
		'TR_VIEW_SOFTWARE' => tr('Software details'),
		'TR_NAME' => tr('Software'),
		'TR_VERSION' => tr('Version'),
		'TR_LANGUAGE' => tr('Language'),
		'TR_TYPE' => tr('Type'),
		'TR_DB' => tr('Database required'),
		'TR_LINK' => tr('Homepage'),
		'TR_DESC' => tr('Description'),
		'TR_BACK' => tr('Back'),
		'TR_INSTALL' => tr('Install'),
		'TR_SOFTWARE_MENU' => tr('Software installation')));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
