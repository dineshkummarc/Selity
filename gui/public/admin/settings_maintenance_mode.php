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
		'page' => 'admin/settings_maintenance_mode.tpl',
		'page_message' => 'layout'));

if (isset($_POST['uaction']) and $_POST['uaction'] == 'apply') {
	$maintenancemode = $_POST['maintenancemode'];
	$maintenancemode_message = clean_input($_POST['maintenancemode_message']);

	$db_cfg = iMSCP_Registry::get('dbConfig');
	$db_cfg->MAINTENANCEMODE = $maintenancemode;
	$db_cfg->MAINTENANCEMODE_MESSAGE = $maintenancemode_message;

	$cfg->replaceWith($db_cfg);

	set_page_message(tr('Settings saved.'), 'success');
}

$selected_on = '';
$selected_off = '';

if ($cfg->MAINTENANCEMODE) {
	$selected_on = $cfg->HTML_SELECTED;
	set_page_message(tr('Maintenance mode is activated. Under this mode, only administrators can login.', 'info'));
} else {
	$selected_off = $cfg->HTML_SELECTED;
	set_page_message(tr('Under maintenance mode, only administrators can login.', 'info'));
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - Admin/Maintenance mode'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_MAINTENANCEMODE' => tr('Maintenance mode'),
		'TR_MESSAGE' => tr('Message'),
		'MESSAGE_VALUE' => $cfg->MAINTENANCEMODE_MESSAGE,
		'SELECTED_ON' => $selected_on,
		'SELECTED_OFF' => $selected_off,
		'TR_ENABLED' => tr('Enabled'),
		'TR_DISABLED' => tr('Disabled'),
		'TR_CHANGES' => tr('Changes'),
		'TR_MAINTENANCE_MESSAGE' => tr('Maintenance message')));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
