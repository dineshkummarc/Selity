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

if (isset($_GET['id']) || isset($_POST['id'])) {
	if (isset($_GET['id']) && is_numeric($_GET['id'])) {
		$software_id = $_GET['id'];
	} elseif (isset($_POST['id']) && is_numeric($_POST['id'])) {
		$software_id = $_POST['id'];
	} else {
		set_page_message(tr('Wrong software id.'), 'error');
		redirectTo('software_manage.php');
	}
	
	if(isset($_POST['change']) && $_POST['change'] == "add"){
		$reseller_id = $_POST['selected_reseller'];
		$user_id = $_SESSION['user_id'];
		$query = "
			SELECT
				* 
			FROM
				`web_software`
			WHERE
				`software_id` = ?
		";
		$rs = exec_query($query, $software_id);
		$query = "
			INSERT INTO
				`web_software`
					(
						`software_master_id`,
						`reseller_id`,
						`software_name`,
						`software_version`,
						`software_language`,
						`software_type`,
						`software_db`,
						`software_archive`,
						`software_installfile`,
						`software_prefix`,
						`software_link`,
						`software_desc`,
						`software_active`,
						`software_status`,
						`rights_add_by`,
						`software_depot`
					)
			VALUES
					(
						?, ?, ?,
						?, ?, ?,
						?, ?, ?,
						?, ?, ?,
						?, ?, ?,
						?
					)
			";
		if($reseller_id == "all"){
			$query2 = "
				SELECT 
					`reseller_id`
				FROM 
					`reseller_props`
				WHERE
					`software_allowed` = 'yes'
				AND
					`softwaredepot_allowed` = 'yes'
			";
			$rs2 = exec_query($query2, array());
			if ($rs2->recordCount() > 0){
				while(!$rs2->EOF) {
					$query3 = "
						SELECT 
							`reseller_id`
						FROM 
							`web_software`
						WHERE
							`reseller_id` = ?
						AND 
							`software_master_id` = ?
					";
					$rs3 = exec_query($query3, array($rs2->fields['reseller_id'],$software_id));
					if ($rs3->recordCount() === 0){
						exec_query(
							$query,
								array(
									$software_id, $rs2->fields['reseller_id'], $rs->fields['software_name'],
									$rs->fields['software_version'], $rs->fields['software_language'], $rs->fields['software_type'],
									$rs->fields['software_db'], $rs->fields['software_archive'], $rs->fields['software_installfile'],
									$rs->fields['software_prefix'], $rs->fields['software_link'], $rs->fields['software_desc'],
									$rs->fields['software_active'], "ok", $user_id, "yes"
								)
						);
                        /** @var $db iMSCP_Database */
                        $db = iMSCP_Registry::get('db');
						$sw_id = $db->insertId();
						update_existing_client_installations_sw_depot($sw_id, $software_id, $rs2->fields['reseller_id']);
					}
					$rs2->MoveNext();
				}
			}else{
				set_page_message(tr('No Resellers found.'), 'error');
				redirectTo('software_rights.php?id='.$software_id);
			}
		}else{
			exec_query(
				$query, 
					array(
						$software_id, $reseller_id, 
						$rs->fields['software_name'], 
						$rs->fields['software_version'],
						$rs->fields['software_language'], 
						$rs->fields['software_type'], 
						$rs->fields['software_db'],
						$rs->fields['software_archive'], 
						$rs->fields['software_installfile'], 
						$rs->fields['software_prefix'],
						$rs->fields['software_link'], 
						$rs->fields['software_desc'], 
						$rs->fields['software_active'],
						"ok", $user_id, "yes"
					)
			);
            /** @var $db iMSCP_Database */
            $db = iMSCP_Registry::get('db');
			$sw_id = $db->insertId();
			update_existing_client_installations_sw_depot($sw_id, $software_id, $reseller_id);
		}
		set_page_message(tr('Rights succesfully added.'), 'success');
		redirectTo('software_rights.php?id='.$software_id);
	} else {
		$reseller_id = $_GET['reseller_id'];
		$delete = "
			DELETE FROM
				`web_software`
			WHERE
				`software_master_id` = ?
			AND
				`reseller_id` = ?
		";
		$update = "
			UPDATE
				`web_software_inst`
			SET
				`software_res_del` = 1
			WHERE
				`software_master_id` = ?
		";
		exec_query($delete, array($software_id, $reseller_id));
		exec_query($update, $software_id);
		set_page_message(tr('Rights succesfully removed.'), 'success');
		redirectTo('software_rights.php?id='.$software_id);
	}
} else {
	set_page_message(tr('Wrong software id.'), 'error');
	redirectTo('software_manage.php');
}
