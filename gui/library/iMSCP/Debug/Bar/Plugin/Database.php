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

/** @See iMSCP_Debug_Bar_Plugin */
require_once 'iMSCP/Debug/Bar/Plugin.php';

/** @see iMSCP_Events_Listeners_Interface */
require_once 'iMSCP/Events/Listeners/Interface.php';

/**
 * Database plugin for the i-MSCP Debug Bar component.
 *
 * Provide debug information about all queries made during script execution and their
 * execution time.
 *
 * @package		iMSCP
 * @package		iMSCP_Debug
 * @subpackage	Bar_Plugin
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.3
 * @Todo Replace the markers to see the parameters in queries strings
 */
class iMSCP_Debug_Bar_Plugin_Database extends iMSCP_Debug_Bar_Plugin implements iMSCP_Events_Listeners_Interface
{
	/**
	 * @var string Plugin unique identifier
	 */
	const IDENTIFIER = 'Database';

	/**
	 * @var array Listened events
	 */
	protected $_listenedEvents = array(
		iMSCP_Events::onBeforeDatabaseConnection,
		iMSCP_Events::onAfterDatabaseConnection,
		iMSCP_Events::onBeforeQueryExecute,
		iMSCP_Events::onAfterQueryExecute
	);

	/**
	 * @var int Total time elapsed
	 */
	protected $_totalTimeElapsed = 0;

	/**
	 * @var array queries and their execution time
	 */
	protected $_queries = array();

	/**
	 * @var int Query index
	 */
	protected $_queryIndex = 0;

	/**
	 * Start to compute time for database connection.
	 *
	 * @param  iMSCP_Database_Events_Database $event
	 * @return void
	 */
	public function onBeforeDatabaseConnection($event)
	{
		$this->_queries[$this->_queryIndex]['time'] = microtime(true);
		$this->_queries[$this->_queryIndex]['queryString'] = 'connection';
	}

	/**
	 * Stop to compute time for database connection.
	 *
	 * @param  iMSCP_Database_Events_Database $event
	 * @return void
	 */
	public function onAfterDatabaseConnection($event)
	{
		$time = microtime(true) - $this->_queries[$this->_queryIndex]['time'];
		$this->_queries[$this->_queryIndex]['time'] = $time;
		$this->_totalTimeElapsed += $time;
		$this->_queryIndex++;
	}

	/**
	 * @param  iMSCP_Database_Events_Database $event
	 * @return void
	 */
	public function onBeforeQueryExecute($event)
	{
		$this->_queries[$this->_queryIndex]['time'] = microtime(true);
		$this->_queries[$this->_queryIndex]['queryString'] = $event->getQueryString();
	}

	/**
	 * @param  iMSCP_Database_Events_Database $event
	 * @return void
	 */
	public function onAfterQueryExecute($event)
	{
		$this->_queries[$this->_queryIndex]['time'] = ((microtime(true)) - $this->_queries[$this->_queryIndex]['time']);
		$this->_totalTimeElapsed += $this->_queries[$this->_queryIndex]['time'];
		$this->_queryIndex++;
	}

	/**
	 * Returns plugin unique identifier.
	 *
	 * @return string
	 */
	public function getIdentifier()
	{
		return self::IDENTIFIER;
	}

	/**
	 * Returns listened events.
	 *
	 * @return array
	 */
	public function getListenedEvents()
	{
		return $this->_listenedEvents;
	}

	/**
	 * Returns plugin tab.
	 *
	 * @return string
	 */
	public function getTab()
	{
		return (count($this->_queries)) . ' queries in ' . round($this->_totalTimeElapsed * 1000, 2) . ' ms';
	}

	/**
	 * Returns the plugin panel.
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$xhtml = '<h4>Database queries and their execution time</h4><ol>';

		foreach ($this->_queries as $query) {
			$xhtml .= '<li><strong>[' . round($query['time'] * 1000, 2) . ' ms]</strong> '
				. htmlspecialchars($query['queryString']) . '</li>';
		}

		return $xhtml . '</ol>';
	}

	/**
	 * Returns plugin icon.
	 *
	 * @return string
	 */
	public function getIcon()
	{
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAEYSURBVBgZBcHPio5hGAfg6/2+R980k6wmJgsJ5U/ZOAqbSc2GnXOwUg7BESgLUeIQ1GSjLFnMwsKGGg1qxJRmPM97/1zXFAAAAEADdlfZzr26miup2svnelq7d2aYgt3rebl585wN6+K3I1/9fJe7O/uIePP2SypJkiRJ0vMhr55FLCA3zgIAOK9uQ4MS361ZOSX+OrTvkgINSjS/HIvhjxNNFGgQsbSmabohKDNoUGLohsls6BaiQIMSs2FYmnXdUsygQYmumy3Nhi6igwalDEOJEjPKP7CA2aFNK8Bkyy3fdNCg7r9/fW3jgpVJbDmy5+PB2IYp4MXFelQ7izPrhkPHB+P5/PjhD5gCgCenx+VR/dODEwD+A3T7nqbxwf1HAAAAAElFTkSuQmCC';
	}
}
