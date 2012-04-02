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
 * Script short description:
 *
 * This script allows AjaxPlorer authentication from i-MSCP (onClick login)
 *
 */

/************************************************************************************
 *  Script functions
 */

/**
 * Get ftp login credentials.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @param  int $userId FTP User
 * @return array Array that contains login credentials or FALSE on failure
 */
function _getLoginCredentials($userId)
{
	$query = "
		SELECT
			`userid`, `rawpasswd`
		FROM
			`ftp_users`, `domain`
		WHERE
			`ftp_users`.`uid` = `domain`.`domain_uid`
		AND
			`ftp_users`.`userid` = ?
		AND
			`domain`.`domain_admin_id` = ?
	";
	$stmt = exec_query($query, array($userId, $_SESSION['user_id']));

	if ($stmt->rowCount()) {
		return array(
			$stmt->fields['userid'],
			$stmt->fields['rawpasswd']
		);
	} else {
		return false;
	}
}

/**
 * Creates all cookies for AjaxPlorer.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @param  array $cookies Array that contains cookies definitions for ajaxplorer
 * @return void
 */
function _ajaxplorerCreateCookies($cookies)
{
	foreach ($cookies as $cookie) {
		header("Set-Cookie: $cookie", false);
	}
}

/**
 * AjaxPlorer authentication.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param  int $userId ftp username
 * @return bool TRUE on success, FALSE otherwise
 */
function _ajaxplorerAuth($userId)
{
	$credentials = _getLoginCredentials($userId);

	if ($credentials) {
		$data = http_build_query(
			array(
				'userid' => $credentials[0],
				'password' => stripcslashes($credentials[1]),
				'get_action' => 'login',
				'login_seed' => '-1',
				'_method' => 'put',
				"remember_me" => ''
			)
		);
	} else {
		set_page_message(tr('Unknown FTP user id.'), 'error');
		return false;
	}

	// Prepares AjaxPlorer absolute Uri to use
	if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) {
		$port = ($_SERVER['SERVER_PORT'] != '443') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$ajaxplorerUri = "https://{$_SERVER['SERVER_NAME']}$port/ftp/";
	} else {
		$port = ($_SERVER['SERVER_PORT'] != '80') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$ajaxplorerUri = "http://{$_SERVER['SERVER_NAME']}$port/ftp/";
	}

	// AjaxPlorer session initialization

	stream_context_get_default(
		array(
			'http' => array(
				'method' => 'HEAD',
				'header' => "Host: {$_SERVER['SERVER_NAME']}\r\n" .
					"Connection: close\r\n\r\n",
				'user_agent' => $_SERVER["HTTP_USER_AGENT"],
			)
		)
	);

	$headers = get_headers($ajaxplorerUri, true);

	// AjaxPlorer secure token

	stream_context_get_default(
		array(
			'http' => array(
				'method' => 'GET',
				'header' => "Host: {$_SERVER['SERVER_NAME']}\r\n" .
					"Connection: close\r\n" .
					"Cookie: {$headers['Set-Cookie']}\r\n\r\n",
				'user_agent' => $_SERVER["HTTP_USER_AGENT"]
			)
		)
	);

	$secureToken = file_get_contents("{$ajaxplorerUri}/?action=get_secure_token");

	// AjaxPlorer authentication

	stream_context_get_default(
		array(
			'http' => array(
				'method' => 'POST',
				'header' => "Host: {$_SERVER['SERVER_NAME']}\r\n" .
					"Connection: close\r\n" .
					"Content-Type: application/x-www-form-urlencoded\r\n" .
					"X-Requested-With: XMLHttpRequest\r\n" .
					'Content-Length: ' . strlen($data) . "\r\n" .
					"Cookie: {$headers['Set-Cookie']}\r\n\r\n",
				'content' => $data,
				'user_agent' => $_SERVER["HTTP_USER_AGENT"],
			)
		)
	);

	$headers = get_headers("{$ajaxplorerUri}?secure_token={$secureToken}", true);

	_ajaxplorerCreateCookies($headers['Set-Cookie']);
	header("Location: {$ajaxplorerUri}");

	return true;
}

/************************************************************************************
 * Main script
 */

// Include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

// Check login
check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('ftp')) {
	redirectTo('index.php');
}

/**
 *  Dispatches the request
 */
if (isset($_GET['id'])) {
	if (!_ajaxplorerAuth($_GET['id'])) {
		redirectTo('ftp_accounts.php');
	}
} else {
	redirectTo('/index.php');
}
