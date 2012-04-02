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
if (!customerHasFeature('mail')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/mail_autoresponder_enable.tpl');
$tpl->define_dynamic('page_message', 'layout');

/**
 * @return void
 */
function check_email_user() {
	$dmn_name = $_SESSION['user_logged'];
	$mail_id = $_GET['id'];

	$query = "
		SELECT
			t1.*, t2.`domain_id`, t2.`domain_name`
		FROM
			`mail_users` AS t1,
			`domain` AS t2
		WHERE
			t1.`mail_id` = ?
		AND
			t2.`domain_id` = t1.`domain_id`
		AND
			t2.`domain_name` = ?
	";
	$rs = exec_query($query, array($mail_id, $dmn_name));

	if ($rs->recordCount() == 0) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface.'), 'error');
		redirectTo('mail_accounts.php');
	}
}

/**
 * @param $tpl
 * @param $mail_id
 * @return
 */
function gen_page_dynamic_data($tpl, $mail_id) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'enable_arsp') {
		if (empty($_POST['arsp_message'])) {
			$tpl->assign('ARSP_MESSAGE', '');
			set_page_message(tr('Please type your mail autorespond message.'), 'error');
			return;
		}

		$arsp_message = clean_input($_POST['arsp_message'], false);
		$item_change_status = $cfg->ITEM_CHANGE_STATUS;

		$query = "
			UPDATE
				`mail_users`
			SET
				`status` = ?, `mail_auto_respond` = 1, `mail_auto_respond_text` = ?
			WHERE
				`mail_id` = ?
		";
		exec_query($query, array($item_change_status, $arsp_message, $mail_id));

		send_request();
		$query = "
			SELECT
				`mail_type`,
				IF(`mail_type` like 'normal_%',t2.`domain_name`,
					IF(`mail_type` like 'alias_%',t3.`alias_name`,
						IF(`mail_type` like 'subdom_%',CONCAT(t4.`subdomain_name`,'.',t6.`domain_name`), CONCAT(t5.`subdomain_alias_name`,'.',t7.`alias_name`))
					)
				) AS mailbox
			FROM
				`mail_users` AS t1
			LEFT JOIN (domain AS t2) ON (t1.`domain_id` = t2.`domain_id`)
			LEFT JOIN (domain_aliasses AS t3) ON (`sub_id` = `alias_id`)
			LEFT JOIN (subdomain AS t4) ON (sub_id = subdomain_id)
			LEFT JOIN (subdomain_alias AS t5) ON (`sub_id` = `subdomain_alias_id`)
			LEFT JOIN (domain AS t6) ON (t4.`domain_id` = t6.`domain_id`)
			LEFT JOIN (domain_aliasses AS t7) ON (t5.`alias_id` = t7.`alias_id`)
			WHERE
				`mail_id` = ?
		";

		$rs = exec_query($query, $mail_id);
		$mail_name = $rs->fields['mailbox'];
		write_log($_SESSION['user_logged'] . ": add mail autoresponder: " . $mail_name, E_USER_NOTICE);
		set_page_message(tr('Mail account scheduled for update.'), 'success');
		redirectTo('mail_accounts.php');
	} else {
		// Get Message
		$query = "
			SELECT
				`mail_auto_respond_text`, `mail_acc`
			FROM
				`mail_users`
			WHERE
				`mail_id` = ?
		";

		$rs = exec_query($query, $mail_id);
		$mail_name = $rs->fields['mail_acc'];

		$tpl->assign('ARSP_MESSAGE', tohtml($rs->fields['mail_auto_respond_text']));
		return;
	}
}

if (isset($_GET['id'])) {
	$mail_id = $_GET['id'];
} else if (isset($_POST['id'])) {
	$mail_id = $_POST['id'];
} else {
	redirectTo('mail_accounts.php');
}

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no") {
	header("Location: index.php");
}

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Selity - Client/Enable Mail Auto Responder'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()));

check_email_user();
gen_page_dynamic_data($tpl, $mail_id);
generateNavigation($tpl);

$tpl->assign(
	array(
		 'TR_ENABLE_MAIL_AUTORESPONDER' => tr('Selity - Client / Manage mail / Enable autoresponder'),
		 'TR_ARSP_MESSAGE' => tr('Your message'),
		 'TR_ENABLE' => tr('Save'),
		 'TR_CANCEL' => tr('Cancel'),
		 'ID' => $mail_id));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
