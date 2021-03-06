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

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

// Checks if support ticket system is activated
if (!hasTicketSystem()) {
    redirectTo('index.php');
} elseif(isset($_GET['ticket_id']) && !empty($_GET['ticket_id'])) {
    closeTicket((int) $_GET['ticket_id']);
}

if (isset($_GET['psi'])) {
    $start = $_GET['psi'];
} else {
    $start = 0;
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/ticket_system.tpl',
		'page_message' => 'layout',
		'tickets_list' => 'page',
		'tickets_item' => 'tickets_list',
		'scroll_prev_gray' => 'page',
		'scroll_prev' => 'page',
		'scroll_next_gray' => 'page',
		'scroll_next' => 'page'));

$tpl->assign(
	array(
		'THEME_CHARSET' => tr('encoding'),
		'TR_PAGE_TITLE' => tr('Selity - Admin / Support Ticket System / Open Tickets'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_SUPPORT_SYSTEM' => tr('Support Ticket System'),
		'TR_OPEN_TICKETS' => tr('Open tickets'),
		'TR_CLOSED_TICKETS' => tr('Closed tickets'),
		'TR_TICKET_STATUS' => tr('Status'),
		'TR_TICKET_FROM' => tr('From'),
		'TR_TICKET_SUBJECT' => tr('Subject'),
		'TR_TICKET_URGENCY' => tr('Priority'),
		'TR_TICKET_LAST_ANSWER_DATE' => tr('Last reply date'),
		'TR_TICKET_ACTIONS' => tr('Actions'),
		'TR_TICKET_DELETE' => tr('Delete'),
		'TR_TICKET_CLOSE' => tr('Close'),
		'TR_TICKET_READ_LINK' => tr('Read the ticket'),
		'TR_TICKET_DELETE_LINK' => tr('Delete the ticket'),
		'TR_TICKET_CLOSE_LINK' => tr('Close the ticket'),
		'TR_TICKET_DELETE_ALL' => tr('Delete all tickets'),
		'TR_TICKETS_DELETE_MESSAGE' => tr("Are you sure you want to delete the '%s' ticket?", '%s'),
		'TR_TICKETS_DELETE_ALL_MESSAGE' => tr('Are you sure you want to delete all tickets?'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_NEXT' => tr('Next')));

generateNavigation($tpl);
generateTicketList($tpl, $_SESSION['user_id'], $start, $cfg->DOMAIN_ROWS_PER_PAGE, 'admin', 'open');
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
unsetMessages();
