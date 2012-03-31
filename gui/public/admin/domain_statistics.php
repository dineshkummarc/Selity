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

/*********************************************************************************
 * Script functions
 */

/**
 * Returns traffic information for the given domain and period.
 *
 * @access private
 * @param int $domainId domain unique identifier
 * @param int $beginTime An UNIX timestamp representing a begin time period
 * @param int $endTime An UNIX timestamp representing an end time period
 * @return array
 */
function _admin_getDomainTraffic($domainId, $beginTime, $endTime)
{
	$query = "
		SELECT
			IFNULL(SUM(`dtraff_web`), 0) `web_dr`, IFNULL(SUM(`dtraff_ftp`), 0) `ftp_dr`,
			IFNULL(SUM(`dtraff_mail`), 0) `mail_dr`, IFNULL(SUM(`dtraff_pop`), 0) `pop_dr`
		FROM
			`domain_traffic`
		WHERE
			`domain_id` = ? AND `dtraff_time` >= ? AND `dtraff_time` <= ?
	";
	$stmt = exec_query($query, array($domainId, $beginTime, $endTime));

	if (!$stmt->rowCount()) {
		return array(0, 0, 0, 0);
	} else {
		return array(
			$stmt->fields['web_dr'], $stmt->fields['ftp_dr'], $stmt->fields['mail_dr'], $stmt->fields['pop_dr']
		);
	}
}

/**
 * Generate domain statistics for the given period.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $domainId Domain unique identifier
 * @param int $month Month of the period for which statistics are requested
 * @param int $year Year of the period for which statistics are requested
 * @return void
 */
function admin_generatePage($tpl, $domainId, $month, $year)
{
	// Let see if the domain exists
	$stmt = exec_query('SELECT `domain_id` FROM `domain` WHERE `domain_id` = ?', $domainId);

	if(!$stmt->rowCount()) {
		set_page_message(tr('Domain not found.'), 'error');
		redirectTo('reseller_statistics.php');
	}

	// Let see if we have any statistics available for the given periode
	$query = "SELECT `domain_id` FROM `domain_traffic` WHERE `dtraff_time` > ? AND `dtraff_time` < ? LIMIT 1";
	$stmt = exec_query($query, array(getFirstDayOfMonth($month, $year), getLastDayOfMonth($month, $year)));

	$tpl->assign('DOMAIN_ID', $domainId);

	if ($stmt->rowCount()) {
		$requestedPeriod = getLastDayOfMonth($month, $year);
		$toDay = ($requestedPeriod < time()) ? date('j', $requestedPeriod) : date('j');
		$all = array_fill(0, 8, 0);

		$dateFormat = iMSCP_Registry::get('config')->DATE_FORMAT;

		for ($fromDay = 1; $fromDay <= $toDay; $fromDay++) {
			$beginTime = mktime(0, 0, 0, $month, $fromDay, $year);
			$endTime = mktime(23, 59, 59, $month, $fromDay, $year);

			list(
				$webTraffic, $ftpTraffic, $smtpTraffic, $popTraffic
			) = _admin_getDomainTraffic($domainId, $beginTime, $endTime);

			$tpl->assign(
				array(
					'DATE' => date($dateFormat, strtotime($year . '-' . $month . '-' . $fromDay)),
					'WEB_TRAFFIC' => bytesHuman($webTraffic),
					'FTP_TRAFFIC' => bytesHuman($ftpTraffic),
					'SMTP_TRAFFIC' => bytesHuman($smtpTraffic),
					'POP3_TRAFFIC' => bytesHuman($popTraffic),
					'ALL_TRAFFIC' => bytesHuman($webTraffic + $ftpTraffic + $smtpTraffic + $popTraffic),
				)
			);

			$all[0] = $all[0] + $webTraffic;
			$all[1] = $all[1] + $ftpTraffic;
			$all[2] = $all[2] + $smtpTraffic;
			$all[3] = $all[3] + $popTraffic;

			$tpl->parse('TRAFFIC_TABLE_ITEM', '.traffic_table_item');
		}

		$tpl->assign(
			array(
				'MONTH' => $month,
				'YEAR' => $year,
				'DOMAIN_ID' => $domainId,
				'ALL_WEB_TRAFFIC' => bytesHuman($all[0]),
				'ALL_FTP_TRAFFIC' => bytesHuman($all[1]),
				'ALL_SMTP_TRAFFIC' => bytesHuman($all[2]),
				'ALL_POP3_TRAFFIC' => bytesHuman($all[3]),
				'ALL_ALL_TRAFFIC' => bytesHuman($all[0] + $all[1] + $all[2] + $all[3]),
			)
		);
	} else {
		set_page_message(tr('No statistics found for the given period. Try another period.'), 'info');
		$tpl->assign('DOMAIN_STATISTICS_BLOCK', '');
	}
}

/******************************************************************************
 * Main script
 */

// Include core library
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

if (isset($_POST['domain_id'])) {
	$domainId = $_POST['domain_id'];
} elseif (isset($_GET['domain_id'])) {
	$domainId = $_GET['domain_id'];
} else {
	set_page_message(tr('Wrong request.'), 'error');
	redirectTo('reseller_statistics.php');
}

if (isset($_POST['month']) && isset($_POST['year'])) {
	$year = intval($_POST['year']);
	$month = intval($_POST['month']);
} else if (isset($_GET['month']) && isset($_GET['year'])) {
	$month = intval($_GET['month']);
	$year = intval($_GET['year']);
} else {
	$month = date('m');
	$year = date('y');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/domain_statistics.tpl',
		'page_message' => 'layout',
		'month_list' => 'page',
		'year_list' => 'page',
		'domain_statistics_block' => 'page',
		'traffic_table_item' => 'domain_statistics_block'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - Admin / Statistics / Reseller\'s statistics / Customer statistics / Domain_statistics'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_DOMAIN_STATISTICS' => tr('Domain statistics'),
		'TR_RESELLER_USER_STATISTICS' => tr('Reseller users table'),
		'TR_MONTH' => tr('Month'),
		'TR_YEAR' => tr('Year'),
		'TR_SHOW' => tr('Show'),
		'TR_WEB_TRAFFIC' => tr('Web traffic'),
		'TR_FTP_TRAFFIC' => tr('FTP traffic'),
		'TR_SMTP_TRAFFIC' => tr('SMTP traffic'),
		'TR_POP3_TRAFFIC' => tr('POP3/IMAP traffic'),
		'TR_ALL_TRAFFIC' => tr('All traffic'),
		'TR_ALL' => tr('All'),
		'TR_DAY' => tr('Day')));

generateNavigation($tpl);
generateSelectListForMonthsAndYears($tpl, $month, $year);
admin_generatePage($tpl, $domainId, $month, $year);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
