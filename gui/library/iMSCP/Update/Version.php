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

/** @see iMSCP_Update */
require_once 'iMSCP/Update.php';

/**
 * Update version class.
 *
 * @category    iMSCP
 * @package     iMSCP_Update
 * @subpackage  Version
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class iMSCP_Update_Version extends iMSCP_Update
{
    /**
     * @var iMSCP_Update
     */
    protected static $_instance;

    /**
     * Singleton - Make new unavailable.
     */
    protected function __construct()
    {

    }

    /**
     * Singleton - Make clone unavailable.
     *
     * @return void
     */
    protected function __clone()
    {

    }

    /**
     * Implements Singleton design pattern.
     *
     * @return iMSCP_Update
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Return next update.
     *
     * @return int 0 if not update or server not reachable.
     */
    protected function _getNextUpdate()
    {
        ini_set('user_agent', 'Mozilla/5.0');

        $timeout = ini_set('default_socket_timeout', 3);
        $fh = @fopen('http://selity.net/latest.txt', 'r');

        // Restore previous timeout
        ini_set('default_socket_timeout', $timeout);

        if (!is_resource($fh)) {
            $this->_lastError = tr("Couldn't check for updates. Website not reachable.");

            return 0;
        }

        $nextUpdate = (int)fread($fh, 8);
        fclose($fh);

        return $nextUpdate;
    }

    /**
     * Check for available update.
     *
     * @return bool TRUE if an update is available, FALSE otherwise.
     */
    public function isAvailableUpdate()
    {
        if ($this->_getLastAppliedUpdate() < $this->_getNextUpdate()) {
            return true;
        }

        return false;
    }

    /**
     * Returns last applied update.
     *
     * @throws iMSCP_Update_Exception When unable to retrieve last applied update
     * @return int
     */
    protected function _getLastAppliedUpdate()
    {
        /** @var $cfg iMSCP_Config_Handler_File */
        $cfg = iMSCP_Registry::get('config');

        if (isset($cfg->BuildDate)) {
            return (int)$cfg->BuildDate;
        } else {
            require_once 'iMSCP/Update/Exception.php';
            throw new iMSCP_Update_Exception('Unable to retrieve last applied update.');
        }
    }

    /**
     * Apply all available update.
     *
     * @throws iMSCP_Update_Exception Since this method is not implemented
     * @return void
     */
    public function applyUpdates()
    {
        require_once 'iMSCP/Update/Exception.php';
        throw new iMSCP_Update_Exception('Method not implemented.');
    }
}
