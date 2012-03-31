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

/******************************************************************
 * Script functions
 */

/**
 * Adds Htaccess group.
 *
 * @param int $domainId Domain unique identifier
 * @return
 */
function client_addHtaccessGroup($domainId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($_POST['uaction']) && $_POST['uaction'] == 'add_group') {
		// we have to add the group
		if (isset($_POST['groupname'])) {
			if (!validates_username($_POST['groupname'])) {
				set_page_message(tr('Invalid group name!'), 'error');
				return;
			}

			$groupname = $_POST['groupname'];

			$query = "
				SELECT
					`id`
				FROM
					`htaccess_groups`
				WHERE
					`ugroup` = ?
				AND
					`dmn_id` = ?
			";
			$rs = exec_query($query, array($groupname, $domainId));

			if ($rs->rowCount() == 0) {
				$change_status = $cfg->ITEM_ADD_STATUS;

				$query = "
					INSERT INTO `htaccess_groups` (
					    `dmn_id`, `ugroup`, `status`
					) VALUES (
					    ?, ?, ?
					)
				";
				exec_query($query, array($domainId, $groupname, $change_status));

				send_request();
				set_page_message(tr('Htaccess group successfully scheduled for addition.'), 'success');

				$admin_login = $_SESSION['user_logged'];
				write_log("$admin_login: added htaccess group: $groupname", E_USER_NOTICE);
				redirectTo('protected_user_manage.php');
			} else {
				set_page_message(tr('This htaccess group already exists.'), 'error');
				return;
			}
		} else {
			set_page_message(tr('Invalid htaccess group name.'), 'error');
			return;
		}
	} else {
		return;
	}
}

/************************************************************************
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
		'page' => 'client/puser_gadd.tpl',
		'page_message' => 'layout',
		'usr_msg' => 'page',
		'grp_msg' => 'page',
		'pusres' => 'page',
		'pgroups' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - Client / Webtools Protected areas / Add Htaccess group'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_HTACCESS_GROUP' => tr('Htaccess group'),
		'TR_GROUPNAME' => tr('Group name'),
		'TR_ADD_GROUP' => tr('Add'),
		'TR_CANCEL' => tr('Cancel')));

generateNavigation($tpl);
client_addHtaccessGroup(get_user_domain_id($_SESSION['user_id']));
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
