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

/**********************************************************************
 * Script functions
 *
 */

/**
 * Updates htaccess user.
 *
 * @param int $dmn_id Domain unique identifier
 * @param int $uuser_id Htaccess user unique identifier
 * @return
 */
function client_updateHtaccessUser(&$dmn_id, &$uuser_id)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($_POST['uaction']) && $_POST['uaction'] == 'modify_user') {
		// we have to add the user
		if (isset($_POST['pass']) && isset($_POST['pass_rep'])) {
			if (!chk_password($_POST['pass'])) {
				if ($cfg->PASSWD_STRONG) {
					set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), $cfg->PASSWD_CHARS), 'error');
				} else {
					set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs.'), $cfg->PASSWD_CHARS), 'error');
				}

				return;
			}

			if ($_POST['pass'] !== $_POST['pass_rep']) {
				set_page_message(tr("Passwords doesn't matches."), 'error');
				return;
			}

			$nadmin_password = crypt_user_pass_with_salt($_POST['pass']);

			$change_status = $cfg->ITEM_CHANGE_STATUS;

			$query = "
				UPDATE
					`htaccess_users`
				SET
					`upass` = ?, `status` = ?
				WHERE
					`dmn_id` = ?
				AND
					`id` = ?
			";
			exec_query($query, array($nadmin_password, $change_status, $dmn_id, $uuser_id,));

			send_request();

			$query = "
				SELECT
					`uname`
				FROM
					`htaccess_users`
				WHERE
					`dmn_id` = ?
				AND
					`id` = ?
			";
			$rs = exec_query($query, array($dmn_id, $uuser_id));
			$uname = $rs->fields['uname'];

			$admin_login = $_SESSION['user_logged'];
			write_log("$admin_login: updated htaccess user ID: $uname", E_USER_NOTICE);
			redirectTo('protected_user_manage.php');
		}
	} else {
		return;
	}
}

/**
 * @param $get_input
 * @return int
 */
function check_get(&$get_input)
{
	if (!is_numeric($get_input)) {
		return 0;
	} else {
		return 1;
	}
}

/*************************************************************
 * Main script
 */

// Include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('protected_areas')) {
	redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/puser_edit.tpl',
		'page_message' => 'layout',
		'usr_msg' => 'page',
		'grp_msg' => 'page',
		'pusres' => 'page',
		'pgroups' => 'page'));

$dmn_id = get_user_domain_id($_SESSION['user_id']);

if (isset($_GET['uname']) && $_GET['uname'] !== '' && is_numeric($_GET['uname'])) {
	$uuser_id = $_GET['uname'];

	$query = "
		SELECT
			`uname`
		FROM
			`htaccess_users`
		WHERE
			`dmn_id` = ?
		AND
			`id` = ?
	";
	$rs = exec_query($query, array((int)$dmn_id, (int)$uuser_id));

	if ($rs->rowCount() == 0) {
		redirectTo('protected_user_manage.php');
	} else {
		$tpl->assign(
			array(
				'UNAME' => tohtml($rs->fields['uname']),
				'UID' => $uuser_id));
	}
} elseif (isset($_POST['nadmin_name']) && !empty($_POST['nadmin_name']) && is_numeric($_POST['nadmin_name'])) {
	$uuser_id = clean_input($_POST['nadmin_name']);

	$query = "
		SELECT
			`uname`
		FROM
			`htaccess_users`
		WHERE
			`dmn_id` = ?
		AND
			`id` = ?
	";
	$rs = exec_query($query, array((int)$dmn_id, (int)$uuser_id));

	if ($rs->rowCount() == 0) {
		redirectTo('protected_user_manage.php');
	} else {
		$tpl->assign(
			array(
				'UNAME' => tohtml($rs->fields['uname']),
				'UID' => $uuser_id));

		client_updateHtaccessUser($dmn_id, $uuser_id);
	}
} else {
	redirectTo('protected_user_manage.php');
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - Client / Webtools / Protected areas / Edit Htaccess user'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_HTACCESS_USER' => tr('Htaccess user'),
		'TR_USERS' => tr('User'),
		'TR_USERNAME' => tr('Username'),
		'TR_PASSWORD' => tr('Password'),
		'TR_PASSWORD_REPEAT' => tr('Repeat password'),
		'TR_UPDATE' => tr('Update'),
		'TR_CANCEL' => tr('Cancel')));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
