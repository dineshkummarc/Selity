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

/***********************************************************************************************************************
 * Script short description:
 *
 * This script allows PhpMyAdmin authentication from i-MSCP
 */

/*******************************************************************************
 * Script functions
 */

/**
 * Get PhpMyadmin login credentials.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @param  int $dbUserId Database user unique identifier
 * @return mixed Array that contains login credentials, FALSE otherwise
 */
function _client_pmaGetLoginCredentials($dbUserId)
{
	$query = "
		SELECT
			`sqlu_name`, `sqlu_pass`
		FROM
			`sql_user` `t1`
		INNER JOIN
			`domain` `t2` ON(`t2`.`domain_admin_id` = ?)
		INNER JOIN
			`sql_database` `t3` ON(`t3`.`domain_id` = `t2`.`domain_id`)
		WHERE
			`t1`.`sqld_id` = `t3`.`sqld_id`
		AND
			`t1`.`sqlu_id` = ?
	";
	$stmt = exec_query($query, array((int)$_SESSION['user_id'], $dbUserId));

	return $stmt->fetchRow(PDO::FETCH_NUM);
}

/**
 * Creates all cookies for PhpMyAdmin.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @param  array $cookies Array that contains cookies definitions for PhpMyadmin
 * @return void
 */
function _client_pmaCreateCookies($cookies)
{
	foreach ($cookies as $cookie) {
		header("Set-Cookie: $cookie", false);
	}
}

/**
 * Set PhpMyAdmin language according language set in panel.
 *
 * Note: If panel language doesn't match any language available for PMA, language
 * is set to English (en).
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @param string $location PMA URI location
 * @return string PMA URI location
 */
function _client_pmaSetLanguage($location)
{
	$uriComponents = parse_url($location);
	parse_str($uriComponents['query'], $queryParts);
	$queryParts['lang'] = substr($_SESSION['user_def_lang'], 0, 2);
	$uriComponents['query'] = http_build_query($queryParts);

	return http_build_url($location, $uriComponents);
}

/**
 * PhpMyAdmin authentication.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param  int $dbUserId Database user unique identifier
 * @return bool FALSE on faillure
 */
function client_pmaAuth($dbUserId)
{
	$credentials = _client_pmaGetLoginCredentials($dbUserId);

	if ($credentials) {
		$httpQuery = http_build_query(
			array(
				'pma_username' => $credentials[0],
				'pma_password' => stripcslashes($credentials[1])));
	} else {
		set_page_message(tr('Wrong SQL user identifier.'), 'error');
		return false;
	}

	// Prepares PhpMyadmin absolute Uri to use
	if (!empty($_SERVER['HTTPS'])) {
		$port = ($_SERVER['SERVER_PORT'] != '443') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$pmaUri = "https://{$_SERVER['SERVER_NAME']}$port/pma/";
	} else {
		$port = ($_SERVER['SERVER_PORT'] != '80') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$pmaUri = "http://{$_SERVER['SERVER_NAME']}$port/pma/";
	}

	// Set stream context (http) options
	stream_context_set_default(
		array(
			'http' => array(
				'method' => 'POST',
				'header' => "Host: {$_SERVER['SERVER_NAME']}$port\r\n" .
					"Content-Type: application/x-www-form-urlencoded\r\n" .
					'Content-Length: ' . strlen($httpQuery) . "\r\n" .
					"Connection: close\r\n\r\n",
				'content' => $httpQuery,
				'max_redirects' => 1)));

	// Gets the headers from PhpMyAdmin
	$headers = get_headers($pmaUri, true);

	if ($headers && isset($headers['Location'])) {
		_client_pmaCreateCookies($headers['Set-Cookie']);
		redirectTo(_client_pmaSetLanguage($headers['Location']));
	}

	set_page_message(tr('An error occurred while the authentication attempt.'), 'error');
	return false;
}

/***********************************************************************************************************************
 * Main program
 */

// Include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

// Check for login
check_login(__FILE__);

/**
 *  Dispatches the request
 */
if (!customerHasFeature('sql')) {
	redirectTo('index.php');
} elseif (!isset($_GET['id']) || !client_pmaAuth((int)$_GET['id'])) {
	redirectTo('sql_manage.php');
}
