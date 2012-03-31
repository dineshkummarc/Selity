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
		'page' => 'admin/ip_usage.tpl',
		'ip_row' => 'page',
		'domain_row' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Selity - Admin/IP Usage'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

/**
 * Generate List of Domains assigned to IPs
 *
 * @param  iMSCP_pTemplate $tpl
 * @return void
 */
function listIPDomains($tpl) {
	
	$query = "SELECT `ip_id`, `ip_number` FROM `server_ips`";
	$rs = execute_query($query);

    if ($rs->rowCount()) {
        while (!$rs->EOF) {
            $no_domains = false;
            $no_alias_domains = false;

            $query = "
			SELECT 
				`d`.`domain_name`, 
				`a`.`admin_name`
			FROM 
				`domain` d
			INNER JOIN
				`admin` a 
			ON
				(`a`.`admin_id` = `d`.`domain_created_id`)
			WHERE 
				`d`.`domain_ip_id` = ?
			ORDER BY 
				`d`.`domain_name`
		";

            $rs2 = exec_query($query, $rs->fields['ip_id']);
            $domain_count = $rs2->recordCount();

            if ($rs2->recordCount() == 0) {
                $no_domains = true;
            }

            while (!$rs2->EOF) {
                $tpl->assign(
                    array(
                         'DOMAIN_NAME' => $rs2->fields['domain_name'],
                         'RESELLER_NAME' => $rs2->fields['admin_name'],
                    )
                );

                $tpl->parse('DOMAIN_ROW', '.domain_row');
                $rs2->moveNext();
            }

            $query = "
			SELECT
				`da`.`alias_name`, 
				`a`.`admin_name`
			FROM 
				`domain_aliasses` da
			INNER JOIN 
				`domain` d
			ON
				(`d`.`domain_id` = `da`.`domain_id`)
			INNER JOIN 
				`admin` a 
			ON
				(`a`.`admin_id` = `d`.`domain_created_id`)
			WHERE 
				`da`.`alias_ip_id` = ?
			ORDER BY 
				`da`.`alias_name`
		";

            $rs3 = exec_query($query, $rs->fields['ip_id']);
            $alias_count = $rs3->recordCount();

            if ($rs3->recordCount() == 0) {
                $no_alias_domains = true;
            }

            while (!$rs3->EOF) {
                $tpl->assign(array(
                                  'DOMAIN_NAME' => $rs3->fields['alias_name'],
                                  'RESELLER_NAME' => $rs3->fields['admin_name']));

                $tpl->parse('DOMAIN_ROW', '.domain_row');
                $rs3->moveNext();
            }

            $tpl->assign(array(
                              'IP' => $rs->fields['ip_number'],
                              'RECORD_COUNT' => tr('Total Domains') . " : " .
                                                ($domain_count + $alias_count)));

            if ($no_domains && $no_alias_domains) {
                $tpl->assign(array(
                                  'DOMAIN_NAME' => tr("No records found"),
                                  'RESELLER_NAME' => ''));

                $tpl->parse('DOMAIN_ROW', '.domain_row');
            }

            $tpl->parse('IP_ROW', '.ip_row');
            $tpl->assign('DOMAIN_ROW', '');
            $rs->moveNext();
        } // end while
    } else {
        $tpl->assign('STATISTICS', '');
        set_page_message('No statistics available.', 'info');
    }
}

generateNavigation($tpl);

listIPDomains($tpl);

$tpl->assign(
	array(
		'TR_SERVER_STATISTICS' => tr('Server statistics'),
		'TR_IP_ADMIN_USAGE_STATISTICS' => tr('Admin/IP usage statistics'),
		'TR_DOMAIN_NAME' => tr('Domain Name'),
		'TR_RESELLER_NAME' => tr('Reseller Name')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
