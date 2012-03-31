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
		'page' => 'admin/software_rights.tpl',
		'page_message' => 'layout',
		'list_reseller' => 'page',
		'no_reseller_list' => 'page',
		'no_select_reseller' => 'page',
		'select_reseller' => 'page',
		'reseller_item' => 'page'));

if (isset($_GET['id']) || isset($_POST['id'])) {
	if (isset($_GET['id']) && is_numeric($_GET['id'])) {
		$software_id = $_GET['id'];
	} elseif (isset($_POST['id']) && is_numeric($_POST['id'])) {
		$software_id = $_POST['id'];
	} else {
		set_page_message(tr('Wrong software id.'), 'error');
		redirectTo('software_manage.php');
	}

} else {
	set_page_message(tr('Wrong software id.'), 'error');
	redirectTo('software_manage.php');
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - Application Management (Permissions)'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

$res_cnt = get_reseller_rights($tpl, $software_id);
$res_list = get_reseller_list ($tpl, $software_id);

$query = "
	SELECT
		`software_name`, `software_version`, `software_language`
	FROM
		`web_software`
	WHERE
		`software_id` = ?
";
$rs = exec_query($query, $software_id);

$tpl->assign(
	array(
		'SOFTWARE_RIGHTS_ID' => $software_id,
		'TR_SOFTWARE_DEPOT' => tr('Softwaredepot'),
		'TR_SOFTWARE_NAME' => tr('%1$s - (Version: %2$s, Language: %3$s)', $rs->fields['software_name'], $rs->fields['software_version'], $rs->fields['software_language']),
		'TR_ADD_RIGHTS' => tr('Add permissions for reseller to software:'),
		'TR_RESELLER' => tr('Reseller'),
		'TR_REMOVE_RIGHTS' => tr('Remove permissions'),
		'TR_RESELLER_COUNT' => tr('Reseller with permissions total'),
		'TR_RESELLER_NUM' => $res_cnt,
		'TR_ADDED_BY' => tr('Added by'),
		'TR_ADD_RIGHTS_BUTTON' => tr('Add permissions'),
		'TR_SOFTWARE_RIGHTS' => tr('Software permissions'),
		'TR_ADMIN_SOFTWARE_PAGE_TITLE' => tr('Selity - Application Management (Permissions)')));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
