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

// Include needed libraries
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

// Check for login
check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/server_status.tpl',
		'page_message' => 'layout',
		'service_status' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity Admin / General Information / Server Status'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->assign(
	array(
		'TR_HOST' => tr('Host'),
		'TR_SERVICE' => tr('Service'),
		'TR_STATUS' => tr('Status'),
		'TR_SERVER_STATUS' => tr('Server status')));

// Services status string
$running = tr('UP');
$down = tr('DOWN');

$services = new iMSCP_Services();

foreach($services as $service) {
	$services->setService($services->key($services), false);

	if($services->isVisible()) {
		$serviceState = $services->isRunning();

		$tpl->assign(
			array(
				'HOST' =>  $services->getIp(),
				'PORT' => $services->getPort(),
				'SERVICE' => $services->getName(),
				'STATUS' => $serviceState ? "<b>$running</b>" : $down,
				'CLASS' => $serviceState ? 'up' : 'down'));

		$tpl->parse('SERVICE_STATUS', '.service_status');
	}
}

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
