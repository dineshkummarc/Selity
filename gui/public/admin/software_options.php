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
		'page' => 'admin/software_options.tpl',
		'page_message' => 'layout'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - Software installer options'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

if(isset($_POST['uaction']) && $_POST['uaction'] == 'apply') {
    $error = "";

    $webdepot_xml_url = encode_idna(strtolower(clean_input($_POST['webdepot_xml_url'])));
    (strlen($webdepot_xml_url) > 0) ? $use_webdepot = $_POST['use_webdepot'] : $use_webdepot = '0';

    if(strlen($webdepot_xml_url) > 0 && $use_webdepot === '1') {
        $xml_file = @file_get_contents($webdepot_xml_url);
        if (!strpos($xml_file, 'i-MSCP web software repositories list')) {
            set_page_message(tr("Unable to read xml file for web softwares."), 'error');
            $error = 1;
        }
    }
    if(!$error){
        $query = "
            UPDATE
                `web_software_options`
            SET
                `use_webdepot` = '".$use_webdepot."',
                `webdepot_xml_url` = '".$webdepot_xml_url."'
        ";
        execute_query($query);
        set_page_message(tr("Software installer options successfully updated."), 'info');
    }
}

$query = "SELECT * FROM `web_software_options`";
$rs = execute_query($query);

$tpl->assign(
	array(
		'TR_OPTIONS_SOFTWARE' => tr('Software installer options'),
		'TR_MAIN_OPTIONS' => tr('Software installer options'),
		'TR_USE_WEBDEPOT' => tr('Remote Web software repository'),
		'TR_WEBDEPOT_XML_URL' => tr('XML file URL for the Web software repository'),
		'TR_WEBDEPOT_LAST_UPDATE' => tr('Last Web software repository update'),
		'USE_WEBDEPOT_SELECTED_OFF' => (($rs->fields['use_webdepot'] == "0") ? $cfg->HTML_SELECTED : ''),
		'USE_WEBDEPOT_SELECTED_ON' => (($rs->fields['use_webdepot'] == "1") ? $cfg->HTML_SELECTED : ''),
		'WEBDEPOT_XML_URL_VALUE' => $rs->fields['webdepot_xml_url'],
		'WEBDEPOT_LAST_UPDATE_VALUE' => ($rs->fields['webdepot_last_update'] == "0000-00-00 00:00:00") ? tr('not available') : $rs->fields['webdepot_last_update'],
		'TR_APPLY_CHANGES' => tr('Apply changes'),
		'TR_ENABLED' => tr('Enabled'),
		'TR_DISABLED' => tr('Disabled')));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
