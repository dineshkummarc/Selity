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

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

$resellerId = $_SESSION['user_id'];

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
	$orderId = $_GET['order_id'];
} else {
	set_page_message(tr('Wrong order ID.'), 'error');
	redirectTo('orders.php');
	exit; // Useless but avoid IDE warning about possible undefined variable
}

$query = "SELECT `id` FROM `orders` WHERE `id` = ? AND `user_id` = ?";
$stmt = exec_query($query, array($orderId, $resellerId));

if (!$stmt->rowCount()) {
	set_page_message(tr('Wrong request.'), 'error');
	redirectTo('orders.php');
}

// delete all FTP Accounts
$query = "DELETE FROM `orders` WHERE `id` = ?";
$stmt = exec_query($query, $orderId);

set_page_message(tr('Customer order sucessfully removed.'), 'success');

write_log($_SESSION['user_logged'] . ": deleted a customer order.", E_USER_NOTICE);
redirectTo('orders.php');
