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
if (!customerHasFeature('custom_error_pages')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/error_edit.tpl');
$tpl->define_dynamic('page_message', 'layout');

/**
 * @param $tpl
 * @param $user_id
 * @param $eid
 * @return
 */
function gen_error_page_data($tpl, $user_id, $eid) {

	$domain = $_SESSION['user_logged'];

	// Check if we already have an error page
	$vfs = new iMSCP_VirtualFileSystem($domain);
	$error = $vfs->get('/errors/' . $eid . '.html');

	if (false !== $error) {
		// We already have an error page, return it
		$tpl->assign(array('ERROR' => tohtml($error)));
		return;
	}
	// No error page
	$tpl->assign(array('ERROR' => ''));
}


$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - Client/Manage Error Custom Pages'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

if (!isset($_GET['eid'])) {
	set_page_message(tr('Wrong request.'), 'error');
	redirectTo('error_pages.php');
} else {
	$eid = intval($_GET['eid']);
}

if ($eid == 401 || $eid == 403 || $eid == 404 || $eid == 500 || $eid == 503) {
	gen_error_page_data($tpl, $_SESSION['user_id'], $_GET['eid']);
} else {
	set_page_message(tr('Error page not found.'), 'error');
	redirectTo('error_pages.php');
}

generateNavigation($tpl);

$tpl->assign(
	array(
		'TR_ERROR_EDIT_PAGE' => tr('Edit error page'),
		'TR_SAVE' => tr('Save'),
		'TR_CANCEL' => tr('Cancel'),
		'EID' => $eid));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
