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

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/system_info.tpl',
		'page_message' => 'layout',
		'disk_list' => 'page',
		'disk_list_item' => 'disk_list'));

$sysinfo = new iMSCP_SystemInfo();

$tpl->assign(
	array(
		'CPU_MODEL' => tohtml($sysinfo->cpu['model']),
		'CPU_COUNT' => tohtml($sysinfo->cpu['cpus']),
		'CPU_MHZ' => tohtml($sysinfo->cpu['cpuspeed']),
		'CPU_CACHE' => tohtml($sysinfo->cpu['cache']),
		'CPU_BOGOMIPS' => tohtml($sysinfo->cpu['bogomips']),
		'UPTIME' => tohtml($sysinfo->uptime),
		'KERNEL' => tohtml($sysinfo->kernel),
		'LOAD' => $sysinfo->load[0] . ' ' . $sysinfo->load[1] . ' ' . $sysinfo->load[2],
		'RAM_TOTAL' => bytesHuman($sysinfo->ram['total'] * 1024),
		'RAM_USED' => bytesHuman($sysinfo->ram['used'] * 1024),
		'RAM_FREE' => bytesHuman($sysinfo->ram['free'] * 1024),
		'SWAP_TOTAL' => bytesHuman($sysinfo->swap['total'] * 1024),
		'SWAP_USED' => bytesHuman($sysinfo->swap['used'] * 1024),
		'SWAP_FREE' => bytesHuman($sysinfo->swap['free'] * 1024)));

$mount_points = $sysinfo->filesystem;

foreach ($mount_points as $mountpoint) {
	$tpl->assign(
		array(
			'MOUNT' => tohtml($mountpoint['mount']),
			'TYPE' => tohtml($mountpoint['fstype']),
			'PARTITION' => tohtml($mountpoint['disk']),
			'PERCENT' => $mountpoint['percent'],
			'FREE' => bytesHuman($mountpoint['free'] * 1024),
			'USED' => bytesHuman($mountpoint['used'] * 1024),
			'SIZE' => bytesHuman($mountpoint['size'] * 1024)));

	$tpl->parse('DISK_LIST_ITEM', '.disk_list_item');
}

$tpl->parse('DISK_LIST', 'disk_list');

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - admin / System tools / System information'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_CPU_BOGOMIPS' => tr('CPU bogomips'),
		'TR_CPU_CACHE' => tr('CPU cache'),
		'TR_CPU_COUNT' => tr('Number of CPU Cores'),
		'TR_CPU_MHZ' => tr('CPU MHz'),
		'TR_CPU_MODEL' => tr('CPU model'),
		'TR_CPU_SYSTEM_INFO' => tr('CPU system Info'),
		'TR_FILE_SYSTEM_INFO' => tr('Filesystem system Info'),
		'TR_FREE' => tr('Free'),
		'TR_KERNEL' => tr('Kernel Version'),
		'TR_LOAD' => tr('Load (1 Min, 5 Min, 15 Min)'),
		'TR_MEMRY_SYSTEM_INFO' => tr('Memory system info'),
		'TR_MOUNT' => tr('Mount'),
		'TR_RAM' => tr('RAM'),
		'TR_PARTITION' => tr('Partition'),
		'TR_PERCENT' => tr('Percent'),
		'TR_SIZE' => tr('Size'),
		'TR_SWAP' => tr('Swap'),
		'TR_SYSTEM_INFO_TITLE' => tr('System info'),
		'TR_SYSTEM_INFO' => tr('Vital system info'),
		'TR_TOTAL' => tr('Total'),
		'TR_TYPE' => tr('Type'),
		'TR_UPTIME' => tr('Up time'),
		'TR_USED' => tr('Used')));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
