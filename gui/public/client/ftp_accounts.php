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
 * Script functions
 */

/**
 * Generates FTP accounts list.
 *
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function gen_page_ftp_list($tpl)
{
	list(,$dmn_name) = get_domain_default_props($_SESSION['user_id']);

	$query = "
		SELECT
			`gid`, `members`
		FROM
			`ftp_group`
		WHERE
			`groupname` = ?
	";
	$stmt = exec_query($query, $dmn_name);

	if ($stmt->rowCount() == 0) {
		set_page_message(tr('You do not have FTP users.'), 'info');
		$tpl->assign('FTP_ACCOUNTS', '');
	} else {
		$ftp_accs = explode(',', $stmt->fields['members']);
		sort($ftp_accs);
		reset($ftp_accs);

		for ($i = 0, $cnt_ftp_accs = count($ftp_accs); $i < $cnt_ftp_accs; $i++) {
			$ftp_accs_encode[$i] = decode_idna($ftp_accs[$i]);

			$tpl->assign(array(
							  'FTP_ACCOUNT' => tohtml($ftp_accs_encode[$i]),
							  'UID' => urlencode($ftp_accs[$i])));

			$tpl->parse('FTP_ITEM', '.ftp_item');
		}

		$tpl->assign('TOTAL_FTP_ACCOUNTS', count($ftp_accs));
	}
}

/************************************************************************************
 * Main script
 */

// Include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('ftp')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic(
	array(
		 'page' => 'client/ftp_accounts.tpl',
		 'page_message' => 'layout',
		 'ftp_message' => 'page',
		 'ftp_accounts' => 'page',
		 'ftp_item' => 'ftp_accounts'));

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Selity - Client/Manage Users'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_TOTAL_FTP_ACCOUNTS' => tr('FTPs total'),
		 'TR_FTP_USERS' => tr('FTP Users'),
		 'TR_FTP_ACCOUNT' => tr('FTP account'),
		 'TR_FTP_ACTION' => tr('Actions'),
		 'TR_LOGINAS' => tr('Login As'),
		 'TR_EDIT' => tr('Edit'),
		 'TR_DELETE' => tr('Delete'),
		 'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete the %s FTP user?', true, '%s'),
		 'FILEMANAGER_TARGET' => $cfg->FILEMANAGER_TARGET));

generateNavigation($tpl);
gen_page_ftp_list($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
