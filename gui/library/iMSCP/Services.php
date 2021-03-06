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

/**
 * Class that allows to get services properties and their status.
 *
 * @category    i-MSCP
 * @package     iMSCP_Services
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.2
 */
class iMSCP_Services implements iterator, countable
{
    /**
     * Array of services where each key is a service name and each associated value is
     * an array that contain all properties.
     *
     * @var array Services
     */
    private $_services = array();

    /**
     * Service name currently queried.
     *
     * @var string
     */
    private $_queriedService = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        /** @var $cfg iMSCP_Config_Handler_File */
        $cfg = iMSCP_Registry::get('config');

        /** @var $dbConfig iMSCP_Config_Handler_Db */
        $dbConfig = iMSCP_Registry::get('dbConfig');

		if (filter_var($cfg->BASE_SERVER_IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$baseServerIp = "[{$cfg->BASE_SERVER_IP}]";
		} else {
			$baseServerIp = $cfg->BASE_SERVER_IP;
		}

        // Retrieve all services properties
        foreach ($dbConfig as $service => $serviceProperties) {
            if (substr($service, 0, 5) == 'PORT_') {
                $this->_services[$service] = explode(';', $serviceProperties);

                if ($this->_services[$service][5] == '') {
                    $this->_services[$service][5] = $baseServerIp;
                } elseif ($this->_services[$service][5] == '127.0.0.1') {
                    $this->_services[$service][5] = 'localhost';
                }
            }
        }

        ksort($this->_services);
    }

    /**
     * Set service to be queried.
     *
     * @throws iMSCP_Exception
     * @param  string $serviceName Service name
     * @param  bool $normalize Tell whether or not the service name must be normalized
     * @return void
     */
    public function setService($serviceName, $normalize = true)
    {
        // Normalise service name (ex. 'dns' to 'PORT_DNS')
        if ($normalize) {
            $normalizedServiceName = 'PORT_' . strtoupper($serviceName);
        } else {
            $normalizedServiceName = $serviceName;
        }

        if (array_key_exists($normalizedServiceName, $this->_services)) {
            $this->_queriedService = $normalizedServiceName;
        } else {
            throw new iMSCP_Exception("Unknown Service '$serviceName'!");
        }
    }

    /**
     * Get service listening port.
     *
     * @param  string $serviceName Service name
     * @return array
     */
    public function getPort($serviceName = null)
    {
        if (!is_null($serviceName)) {
            $this->setService($serviceName);
        }

        return $this->_getProperty($this->_queriedService, 0);
    }

    /**
     * Get service protocol.
     *
     * @param  string $serviceName Service name
     * @return array
     */
    public function getProtocol($serviceName = null)
    {
        if (!is_null($serviceName)) {
            $this->setService($serviceName);
        }

        return $this->_getProperty($this->_queriedService, 1);
    }

    /**
     * Get service name.
     *
     * @param  $serviceName
     * @return mixed
     */
    public function getName($serviceName = null)
    {
        if (!is_null($serviceName)) {
            $this->setService($serviceName);
        }

        return $this->_getProperty($this->_queriedService, 2);
    }

    /**
     * Get service IP.
     *
     * @param  string $serviceName Service name
     * @return array
     */
    public function getIp($serviceName = null)
    {
        if (!is_null($serviceName)) {
            $this->setService($serviceName);
        }

        return $this->_getProperty($this->_queriedService, 5);
    }

    /**
     * Check if the service is read only.
     *
     * @param  $serviceName Service name
     * @return bool TRUE if the service is read only, FALSE otherwise
     */
    public function isReadOnly($serviceName = null)
    {
        if (!is_null($serviceName)) {
            $this->setService($serviceName);
        }

        return (bool)(!$this->_getProperty($this->_queriedService, 4));
    }

    /**
     * Check if the service is visible.
     *
     * @param  $serviceName Service name
     * @return bool TRUE if the service is visible, FALSE otherwise
     */
    public function isVisible($serviceName = null)
    {
        if (!is_null($serviceName)) {
            $this->setService($serviceName);
        }

        return (bool)$this->_getProperty($this->_queriedService, 3);
    }

    /**
     * Check if a service is running.
     *
     * @return bool return TRUE if the service is currently running, FALSE otherwise
     */
    public function isRunning()
    {
        return $this->_getStatus();
    }

    /**
     * Check if a service is down.
     *
     * @param  $serviceName Service name
     * @return bool return TRUE if the service is currently down, FALSE otherwise
     */
    public function isDown($serviceName = null)
    {

        return (!($this->_getStatus()));
    }

    /**
     * Returns the current element.
     *
     * @return mixed Returns the current element
     */
    public function current()
    {

        return current($this->_services);
    }

    /**
     * Returns the key of the current element.
     *
     * @return string Return the key of the current element or NULL on failure
     */
    public function key()
    {

        return key($this->_services);
    }

    /**
     * Moves the current position to the next element.
     *
     * @return void
     */
    public function next()
    {
        next($this->_services);
    }

    /**
     * Rewinds back to the first element of the Iterator.
     *
     * <b>Note:</b> This is the first method called when starting a foreach
     * loop. It will not be executed after foreach loops.
     *
     * @return void
     */
    public function rewind()
    {

        reset($this->_services);
    }

    /**
     * Checks if current position is valid.
     *
     * @return boolean TRUE on success or FALSE on failure
     */
    public function valid()
    {

        return array_key_exists(key($this->_services), $this->_services);
    }

    /**
     * Count number of service.
     *
     * @return int The custom count as an integer.
     */
    public function count()
    {

        return count($this->_services);
    }

    /**
     * Get a service property value.
     *
     * @throws iMSCP_Exception
     * @param  string $serviceName Service name
     * @param  int $index Service property index
     * @return mixed Service property value
     */
    private function _getProperty($serviceName, $index)
    {

        if (!is_null($this->_queriedService)) {
            return $this->_services[$this->_queriedService][$index];
        } else {
            throw new iMSCP_Exception('Service name to be queried is not set!');
        }
    }

    /**
     * Get service status.
     *
     * @return bool TRUE if the service is currently running, FALSE otherwise
     */
    private function _getStatus()
    {

        ini_set('default_socket_timeout', 3);

        if (($fp = @fsockopen($this->getProtocol() . '://' . $this->getIp(),
                              $this->getPort()))
        ) {
            fclose($fp);

            return true;
        }

        return false;
    }
}
