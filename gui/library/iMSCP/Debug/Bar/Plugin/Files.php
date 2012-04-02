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

/**
 * Files plugin for the i-MSCP Debug Bar component.
 *
 * Provide debug information about all included files.
 *
 * @package		iMSCP
 * @package		iMSCP_Debug
 * @subpackage	Bar_Plugin
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.4
 */
class iMSCP_Debug_Bar_Plugin_Files extends iMSCP_Debug_Bar_Plugin implements iMSCP_Events_Listeners_Interface
{
	/**
	 * @var string Plugin unique identifier
	 */
	const IDENTIFIER = 'Files';

	/**
	 * @var string Listened event
	 */
	protected $_listenedEvents = iMSCP_Events::onBeforeLoadTemplateFile;

	/**
	 * Implements onLoadTemplateFile listener method.
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeLoadTemplateFile($event)
	{
		$this->_loadedTemplateFiles[] = realpath($event->getParam('templatePath'));
	}

	/**
	 * Stores included files
	 *
	 * @var
	 */
	protected $_includedFiles = array();

	/**
	 * Store loaded template files.
	 *
	 * @var array
	 */
	protected $_loadedTemplateFiles = array();

	/**
	 * Returns plugin unique identifier.
	 *
	 * @return string Plugin unique identifier.
	 */
	public function getIdentifier()
	{
		return self::IDENTIFIER;
	}

	/**
	 * Returns list of events that this plugin listens on.
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
		return count($this->_getIncludedFiles()) + count($this->_loadedTemplateFiles) . ' ' . $this->getIdentifier();
	}

	/**
	 * Returns the plugin panel.
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$includedPhpFiles = $this->_getIncludedFiles();
		$loadedTemplateFiles = $this->_getLoadedTemplateFiles();

		$xhtml = "<h4>General Information</h4><pre>\t";
		$xhtml .= count($includedPhpFiles) + count($loadedTemplateFiles) . ' Files Included/loaded' . PHP_EOL;
		$size = bytesHuman(array_sum(array_map('filesize', array_merge($includedPhpFiles, $loadedTemplateFiles))));
		$xhtml .= "\tTotal Size: $size</pre>";

		$xhtml .= "<h4>PHP Files</h4><pre>\t" . implode(PHP_EOL . "\t", $includedPhpFiles) . '</<pre>';
		$xhtml .= "<h4>Templates Files</h4><pre>\t" . implode(PHP_EOL . "\t", $loadedTemplateFiles) . '</<pre>';

		return $xhtml;
	}

	/**
	 * Returns plugin icon.
	 *
	 * @return string
	 */
	public function getIcon()
	{
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAADPSURBVCjPdZFNCsIwEEZHPYdSz1DaHsMzuPM6RRcewSO4caPQ3sBDKCK02p+08DmZtGkKlQ+GhHm8MBmiFQUU2ng0B7khClTdQqdBiX1Ma1qMgbDlxh0XnJHiit2JNq5HgAo3KEx7BFAM/PMI0CDB2KNvh1gjHZBi8OR448GnAkeNDEDvKZDh2Xl4cBcwtcKXkZdYLJBYwCCFPDRpMEjNyKcDPC4RbXuPiWKkNABPOuNhItegz0pGFkD+y3p0s48DDB43dU7+eLWes3gdn5Y/LD9Y6skuWXcAAAAASUVORK5CYII=';
	}

	/**
	 * Returns list of included files.
	 *
	 * @return array
	 */
	protected function _getIncludedFiles()
	{
		$this->_includedFiles = get_included_files();
		sort($this->_includedFiles);

		return $this->_includedFiles;
	}

	/**
	 * Returns list of loaded template files.
	 *
	 * @return array
	 */
	protected function _getLoadedTemplateFiles()
	{
		return $this->_loadedTemplateFiles;
	}
}
