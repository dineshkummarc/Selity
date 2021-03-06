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

// include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$userId = $_SESSION['user_id'];

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('support')) {
	redirectTo('index.php');
}

if (isset($_GET['ticket_id']) && !empty($_GET['ticket_id'])) {
    $userId = $_SESSION['user_id'];
    $ticketId = (int) $_GET['ticket_id'];
	$status = getTicketStatus($ticketId);
	$ticketLevel = getUserLevel($ticketId);

	if (getTicketStatus($ticketId) == 2) {
		changeTicketStatus($ticketId, 3);
	}

    if (isset($_POST['uaction'])) {
        if ($_POST['uaction'] == 'close') {
            closeTicket($ticketId);
        } elseif(isset($_POST['user_message'])) {
            if(empty($_POST['user_message'])) {
                set_page_message(tr('Please type your message.'), 'error');
            } else {
                updateTicket($ticketId, $userId, $_POST['urgency'], $_POST['subject'],
                             $_POST['user_message'], 1, 1);
            }
        }

        redirectTo('ticket_system.php');
    }
} else {
    set_page_message(tr('Ticket not found.'), 'error');
    redirectTo('ticket_system.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic(
	array(
		 'page' => 'client/ticket_view.tpl',
		 'page_message' => 'layout',
		 'tickets_list' => 'page',
		 'tickets_item' => 'tickets_list'));

$tpl->assign(
	array(
		 'THEME_CHARSET' => tr('encoding'),
		 'TR_PAGE_TITLE' => tr('Selity - Client / Support Ticket System / View Ticket'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_SUPPORT_SYSTEM' => tr('Support Ticket System'),
		 'TR_OPEN_TICKETS' => tr('Open tickets'),
		 'TR_CLOSED_TICKETS' => tr('Closed tickets'),
		 'TR_VIEW_SUPPORT_TICKET' => tr('View Support Ticket'),
		 'TR_TICKET_INFO' => tr('Ticket information'),
		 'TR_TICKET_URGENCY' => tr('Priority'),
		 'TR_TICKET_SUBJECT' => tr('Subject'),
		 'TR_TICKET_MESSAGES' => tr('Messages'),
		 'TR_TICKET_FROM' => tr('From'),
		 'TR_TICKET_DATE' => tr('Date'),
		 'TR_TICKET_CONTENT' => tr('Message'),
		 'TR_TICKET_NEW_REPLY' => tr('Send new reply'),
		 'TR_TICKET_REPLY' => tr('Send reply')));


generateNavigation($tpl);
showTicketContent($tpl, $ticketId, $userId);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
