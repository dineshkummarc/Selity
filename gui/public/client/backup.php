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

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('backup')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/backup.tpl');
$tpl->define_dynamic('page_message', 'layout');

/**
 * Schedule backup.
 *
 * @param int $user_id
 * @return void
 */
function send_backup_restore_request($user_id) {
	if (isset($_POST['uaction']) && $_POST['uaction'] === 'bk_restore') {

		$query = "
			UPDATE
				`domain`
			SET
				`domain_status` = 'restore'
			WHERE
				`domain_admin_id` = ?
		";

		exec_query($query, $user_id);

		send_request();
		write_log($_SESSION['user_logged'] . ": restore backup files.", E_USER_NOTICE);
		set_page_message(tr('Backup archive scheduled for restoring.'), 'success');
	}
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - Client/Daily Backup'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

send_backup_restore_request($_SESSION['user_id']);
generateNavigation($tpl);

if ($cfg->ZIP == "gzip") {
	$name = "backup_YYYY_MM_DD.tar.gz";
} else if ($cfg->ZIP == "bzip2") {
	$name = "backup_YYYY_MM_DD.tar.bz2";
} else { // Config::getInstance()->get('ZIP') == "lzma"
	$name = "backup_YYYY_MM_DD.tar.lzma";
}

$tpl->assign(
	array(
		'TR_BACKUP' => tr('Backup'),
		'TR_DAILY_BACKUP' => tr('Daily backup'),
		'TR_DOWNLOAD_DIRECTION' => tr("Instructions to download today's backup"),
		'TR_FTP_LOG_ON' => tr('Login with your FTP account'),
		'TR_SWITCH_TO_BACKUP' => tr('Switch to backups/ directory'),
		'TR_DOWNLOAD_FILE' => tr('Download the files stored in this directory'),
		'TR_USUALY_NAMED' => tr('(usually named') . ' ' . $name . ')',
		'TR_RESTORE_BACKUP' => tr('Restore backup'),
		'TR_RESTORE_DIRECTIONS' => tr('Click the Restore button and the system will restore the last daily backup'),
		'TR_RESTORE' => tr('Restore'),
		'TR_CONFIRM_MESSAGE' => tr('Are you sure you want to restore the backup?')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
