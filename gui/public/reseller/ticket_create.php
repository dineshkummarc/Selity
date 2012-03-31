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
 * Main script
 */

// Include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if(!resellerHasFeature('support')) {
	return 'index.php';
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$userId = $_SESSION['user_id'];

// Checks if support ticket system is activated and if the reseller can access to it
if (!hasTicketSystem($userId)) {
    redirectTo('index.php');
}

if (isset($_POST['uaction'])) {
    if (empty($_POST['subject'])) {
        set_page_message(tr('You must specify a subject.'), 'error');
    } elseif (empty($_POST['user_message'])) {
        set_page_message(tr('You must specify a message.'), 'error');
    } else {
        createTicket($userId, $_SESSION['user_created_by'],
                     $_POST['urgency'], $_POST['subject'], $_POST['user_message'], 2);
        redirectTo('ticket_system.php');
    }
}

$userdata = array(
    'OPT_URGENCY_1' => '',
    'OPT_URGENCY_2' => '',
    'OPT_URGENCY_3' => '',
    'OPT_URGENCY_4' => '');

if (isset($_POST['urgency'])) {
    $userdata['URGENCY'] = intval($_POST['urgency']);
} else {
    $userdata['URGENCY'] = 2;
}

switch ($userdata['URGENCY']) {
    case 1:
        $userdata['OPT_URGENCY_1'] = $cfg->HTML_SELECTED;
        break;
    case 3:
        $userdata['OPT_URGENCY_3'] = $cfg->HTML_SELECTED;
        break;
    case 4:
        $userdata['OPT_URGENCY_4'] = $cfg->HTML_SELECTED;
        break;
    default:
        $userdata['OPT_URGENCY_2'] = $cfg->HTML_SELECTED;
}

$userdata['SUBJECT'] = isset($_POST['subject']) ? clean_input($_POST['subj'], true) : '';
$userdata['USER_MESSAGE'] = isset($_POST['user_message'])
    ? clean_input($_POST['user_message'], true) : '';

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/ticket_create.tpl',
		'page_message' => 'layout'));

$tpl->assign(
	array(
		'THEME_CHARSET' => tr('encoding'),
		'TR_PAGE_TITLE' => tr('Selity - Reseller / Support Ticket System / New Ticket'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_SUPPORT_SYSTEM' => tr('Support Ticket System'),
		'TR_NEW_TICKET' => tr('New ticket'),
		'TR_LOW' => tr('Low'),
		'TR_MEDIUM' => tr('Medium'),
		'TR_HIGH' => tr('High'),
		'TR_VERY_HIGH' => tr('Very high'),
		'TR_URGENCY' => tr('Priority'),
		'TR_EMAIL' => tr('Email'),
		'TR_SUBJECT' => tr('Subject'),
		'TR_YOUR_MESSAGE' => tr('Message'),
		'TR_CREATE' => tr('Create'),
		'TR_OPEN_TICKETS' => tr('Open tickets'),
		'TR_CLOSED_TICKETS' => tr('Closed tickets')));

$tpl->assign($userdata);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
unsetMessages();
