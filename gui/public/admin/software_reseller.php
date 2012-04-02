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
		'page' => 'admin/software_reseller.tpl',
		'page_message' => 'layout',
		'list_software' => 'page',
		'no_software_list' => 'page',
		'list_softwaredepot' => 'page',
		'no_softwaredepot_list' => 'page',
		'no_reseller_list' => 'page',
		'list_reseller' => 'page',
		'software_is_in_softwaredepot' => 'page',
		'software_is_not_in_softwaredepot' => 'page'));

if (isset($_GET['id'])){
	if (isset($_GET['id']) && is_numeric($_GET['id'])) {
		$reseller_id = $_GET['id'];
	} else {
		set_page_message(tr('Wrong reseller id.'), 'error');
		redirectTo('software_manage.php');
	}

} else {
	set_page_message(tr('Wrong reseller id.'), 'error');
	redirectTo('software_manage.php');
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - Application Management'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

$software_cnt = get_installed_res_software($tpl, $_GET['id']);
$res_cnt = get_reseller_software($tpl, $_GET['id']);

$tpl->assign(
	array(
		'RESELLER_ID' => $reseller_id,
		'TR_SOFTWARE_INSTALLED' => tr('Installed on'),
		'TR_SOFTWARE_RIGHTS' => tr('Permissions'),
		'TR_SOFTWAREDEPOT_COUNT' => tr('Total Softwares'),
		'TR_SOFTWAREDEPOT_NUM' => $software_cnt,
		'TR_AWAITING_ACTIVATION' => tr('Awaiting activation'),
		'TR_ACTIVATED_SOFTWARE' => tr('Reseller list'),
		'TR_SOFTWARE_NAME' => tr('Software name'),
		'TR_SOFTWARE_VERSION' => tr('Version'),
		'TR_SOFTWARE_LANGUAGE' => tr('Language'),
		'TR_SOFTWARE_TYPE' => tr('Type'),
		'TR_RESELLER_NAME' => tr('Reseller'),
		'TR_RESELLER_ACT_COUNT' => tr('Reseller total'),
		'TR_RESELLER_ACT_NUM' => $res_cnt,
		'TR_RESELLER_COUNT_SWDEPOT' => tr('Software repository'),
		'TR_RESELLER_COUNT_WAITING' => tr('Awaiting activation'),
		'TR_RESELLER_COUNT_ACTIVATED' => tr('Activated softwares'),
		'TR_RESELLER_SOFTWARE_IN_USE' => tr('Total installations'),
		'TR_ADMIN_SOFTWARE_PAGE_TITLE' => tr('Selity - Software Installer Management')));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
