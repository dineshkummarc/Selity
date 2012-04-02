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

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/selity_updates.tpl',
		'page_message' => 'layout',
		'update_message' => 'page',
		'update_infos' => 'page',
		'table_header' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - multiserver hosting control panel'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

/**
 * @param  iMSCP_pTemplate $tpl
 * @return void
 */
function get_update_infos($tpl)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    if (!$cfg->CHECK_FOR_UPDATES) {
		$tpl->assign(
			array(
				'UPDATE_MESSAGE' => '',
				'UPDATE' => tr('Update checking is disabled!'),
				'INFOS' => tr('Enable update at') . " <a href=\"settings.php\">" . tr('Settings') . '</a>'));

		$tpl->parse('UPDATE_INFOS', 'update_infos');
        return;
    }

    if (iMSCP_Update_Version::getInstance()->isAvailableUpdate()) {
        $tpl->assign(
			array(
				'UPDATE_INFOS' => '',
				'UPDATE' => tr('New i-MSCP update is now available'),
				'TR_MESSAGE' => tr('Get it at') . " <a href=\"http://www.i-mscp.net/download\" class=\"link\" target=\"_blank\">http://www.i-mscp.net/download</a>"));

        $tpl->parse('UPDATE_MESSAGE', 'update_message');
    } elseif (iMSCP_Update_Version::getInstance()->getError() != '') {
        $tpl->assign('TR_MESSAGE', iMSCP_Update_Version::getInstance()->getError());
    }

    $tpl->assign('UPDATE_INFOS', '');
}

generateNavigation($tpl);

$tpl->assign(
	array(
		'TR_UPDATES_TITLE' => tr('Selity updates'),
		'TR_AVAILABLE_UPDATES' => tr('Available i-MSCP updates'),
		'TR_MESSAGE' => tr('No new i-MSCP updates available'),
		'TR_UPDATE' => tr('Update'),
		'UPDATE' => tr('Update details')));

generatePageMessage($tpl);
get_update_infos($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
