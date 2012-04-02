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
if (!customerHasFeature('domain_aliases')) {
    redirectTo('index.php');
}

if (isset($_GET['id']) && $_GET['id'] !== '') {
	$als_id = $_GET['id'];
	$dmn_id = get_user_domain_id($_SESSION['user_id']);

	$query = "
		SELECT
			`alias_id`
			`alias_name`
		FROM
			`domain_aliasses`
		WHERE
			`domain_id` = ?
		AND
			`alias_id` = ?
	";

	$rs = exec_query($query, array($dmn_id, $als_id));
	$alias_name = $rs->fields['alias_name'];

	if ($rs->recordCount() == 0) {
		redirectTo('domains_manage.php');
	}

	// check for subdomains
	$query = "
		SELECT
			COUNT(`subdomain_alias_id`) AS `count`
		FROM
			`subdomain_alias`
		WHERE
			`alias_id` = ?
	";

	$rs = exec_query($query, $als_id);
	if ($rs->fields['count'] > 0) {
		set_page_message(tr('Domain alias you are trying to remove has subdomains!<br>First remove them!'), 'error');
		redirectTo('domains_manage.php');
	}

	// check for mail accounts
	$query = "
		SELECT
			COUNT(`mail_id`) AS `cnt`
		FROM
			`mail_users`
		WHERE
			(`sub_id` = ?
			AND
			`mail_type` LIKE '%alias_%')
		OR
			(`sub_id` IN (SELECT `subdomain_alias_id` FROM `subdomain_alias` WHERE `alias_id` = ?)
			AND
			`mail_type` LIKE '%alssub_%')
	";

	$rs = exec_query($query, array($als_id, $als_id));

	if ($rs->fields['cnt'] > 0) {
		set_page_message(tr('Domain alias you are trying to remove has email accounts !<br>First remove them!'), 'error');
		redirectTo('domains_manage.php');
	}

	// check for ftp accounts
	$query = "
		SELECT
			COUNT(`fg`.`gid`) AS `ftpnum`
		FROM
			`ftp_group` `fg`,
			`domain` `dmn`,
			`domain_aliasses` `d`
		WHERE
			`d`.`alias_id` = ?
		AND
			`fg`.`groupname` = `dmn`.`domain_name`
		AND
			`fg`.`members` RLIKE `d`.`alias_name`
		AND
			`d`.`domain_id` = `dmn`.`domain_id`
	";

	$rs = exec_query($query, $als_id);
	if ($rs->fields['ftpnum'] > 0) {
		set_page_message(tr('Domain alias you are trying to remove has FTP accounts.<br>First remove them first.'), 'error');
		redirectTo('domains_manage.php');
	}

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeDeleteDomainAlias, array('domainAliasId' => $als_id)
	);

	$query = "
		UPDATE
			`domain_aliasses`
		SET
			`alias_status` = 'delete'
		WHERE
			`alias_id` = ?
	";

	$rs = exec_query($query, $als_id);

	$query = "
		UPDATE
			`ssl_certs`
		SET
			`status` = 'delete'
		WHERE
			`id` = ?
		AND
			`type` = 'als'
	";

	$rs = exec_query($query, $als_id);

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onAfterDeleteDomainAlias, array('domainAliasId' => $als_id)
	);

	update_reseller_c_props(get_reseller_id($dmn_id));

	send_request();
	write_log($_SESSION['user_logged'].": delete alias ".$alias_name."!", E_USER_NOTICE);
	set_page_message(tr('Alias scheduled for deletion.'), 'success');
	redirectTo('domains_manage.php');
} else {
	redirectTo('domains_manage.php');
}
