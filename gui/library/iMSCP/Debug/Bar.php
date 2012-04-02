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

/** @see iMSCP_Events_Listeners_Interface */
require_once 'iMSCP/Events/Listeners/Interface.php';

/** @see iMSCP_Events */
require_once 'iMSCP/Events.php';

/**
 * i-MSCP DebugBar component.
 *
 * This component is a development helper that provides some debug information. The
 * component comes with a bunch of plugins where each of them provides a particular
 * set of debug information. A plugin can be or not an event listener that listens on
 * one or more events that are thrown in the application work flow.
 *
 * For now, the DebugBar component come with the followings plugins:
 *
 *  - Version : i-MSCP version, list of all PHP extensions available.
 *
 *  - Variables : Contents of $_GET, $_POST, $_COOKIE, $_FILES and $_SESSION and
 *	$_ENV variables.
 *
 *  - Timer : Timing information of current request, time spent in level script ;
 *	support custom timers. Also average, min and max time for requests.
 *
 *  - Files : Number and size of files included with complete list.
 *
 *  - Memory : Peak memory usage, memory usage of Level scripts and the whole
 *	application ; support for custom memory markers.
 *
 *  - Database : Full listing of SQL queries and the time for each.
 *
 * @package		iMSCP
 * @package		iMSCP_Debug
 * @subpackage	Bar
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.3
 */
class iMSCP_Debug_Bar implements iMSCP_Events_Listeners_Interface
{
	/**
	 * @var iMSCP_Events_Manager
	 */
	protected $_eventsManager;

	/**
	 * @var iMSCP_Events_Event
	 */
	protected $_event;

	/**
	 * @var iMSCP_Debug_Bar_Plugin_Interface
	 */
	protected $_plugins = array();

	/**
	 * @var array Listened events
	 */
	protected $_listenedEvents = array(
		iMSCP_Events::onLoginScriptEnd,
		iMSCP_Events::onLostPasswordScriptEnd,
		iMSCP_Events::onAdminScriptEnd,
		iMSCP_Events::onResellerScriptEnd,
		iMSCP_Events::onClientScriptEnd,
		iMSCP_Events::onOrderPanelScriptEnd,
		iMSCP_Events::onExceptionToBrowserEnd
	);

	/**
	 * Constructor.
	 *
	 * @throws iMSCP_Debug_Bar_Exception if a plugin doesn't implement the iMSCP_Debug_Bar_Plugin_Interface interface
	 * @param iMSCP_Events_Manager $eventsManager Events manager
	 * @param string|array $plugins Plugin(s) instance(s).
	 */
	public function __construct(iMSCP_Events_Manager $eventsManager, $plugins)
	{
		$this->_eventsManager = $eventsManager;

		// Creating i-MSCP Version Tab always shown
		$this->_plugins[] = new iMSCP_Debug_Bar_Plugin_Version();

		$priority = 998;

		foreach ((array)$plugins as $plugin) {
			if (!$plugin instanceof iMSCP_Debug_Bar_Plugin_Interface) {
				throw new iMSCP_Debug_Bar_Exception(
					'All plugins for the debug bar must implement the iMSCP_Debug_Bar_Plugin_Interface interface.');
			} elseif ($plugin instanceof iMSCP_Events_Listeners_Interface) {
				$this->registerListener($plugin, $priority);
				$priority--;
			}

			$this->_plugins[] = $plugin;
		}

		$eventsManager->registerListener($this->getListenedEvents(), $this, 999);
	}

	/**
	 * Register a plugin listener on the events manager.
	 *
	 * @param  iMSCP_Events_Listeners_Interface $plugin Plugin instance.
	 * @param  int $priority Order in which listeners methods will be executed.
	 * @return void
	 */
	protected function registerListener($plugin, $priority)
	{
		$this->_eventsManager->registerListener($plugin->getListenedEvents(), $plugin, $priority);
	}

	/**
	 * Catch all calls for listener methods of this class to avoid to declarate them
	 * since they do same job.
	 *
	 * @param string $listenerMethod Listener method
	 * @param array $arguments Enumerated array containing listener method arguments (always an iMSCP_Events_Description object)
	 */
	public function __call($listenerMethod, $arguments)
	{
		if (!in_array($listenerMethod, $this->_listenedEvents)) {
			throw new iMSCP_Debug_Bar_Exception('Unknown listener method.');
		}

		$this->_event = $arguments[0];
		$this->buildDebugBar();
	}

	/**
	 * Returns list of listeneds events.
	 *
	 * @return array
	 */
	public function getListenedEvents()
	{
		return $this->_listenedEvents;
	}

	/**
	 * Builds the Debug Bar and adds it to the repsonse.
	 *
	 * @return void
	 */
	protected function buildDebugBar()
	{
		// Doesn't act on AJAX request.
		if (is_xhr()) {
			return;
		}

		$xhtml = '<div>';

		/** @var $plugin iMSCP_Debug_Bar_Plugin_Interface */
		foreach ($this->_plugins as $plugin)
		{
			if (($tab = $plugin->getTab()) != '') {
				$xhtml .= '<span class="iMSCPdebug_span clickable" onclick="iMSCPdebugPanel(\'iMSCPdebug_' . $plugin->getIdentifier() . '\');">';
				$xhtml .= '<img src="' . $plugin->getIcon() . '" style="vertical-align:middle" alt="' . $plugin->getIdentifier() . '" title="' . $plugin->getIdentifier() . '" /> ';
				$xhtml .= $tab . '</span>';
			}

			if (($panel = $plugin->getPanel()) != '') {
				$xhtml .= '<div id="iMSCPdebug_' . $plugin->getIdentifier() . '" class="iMSCPdebug_panel">' . $panel . '</div>';
			}
		}

		$xhtml .= '<span class="iMSCPdebug_span iMSCPdebug_last clickable" id="iMSCPdebug_toggler" onclick="iMSCPdebugSlideBar()">&#171;</span>';
		$xhtml .= '</div>';

		/** @var $templateEngine iMSCP_pTemplate */
		$templateEngine = $this->_event->getParam('templateEngine');
		$response = $templateEngine->getLastParseResult();
		$response = preg_replace('@(</head>)@i', $this->_buildHeader() . PHP_EOL . '$1', $response);
		$response = str_ireplace('</body>', '<div id="iMSCPdebug_debug">' . $xhtml . '</div></body>', $response);
		$templateEngine->replaceLastParseResult($response);
	}

	/**
	 * Returns xhtml header for the Debug Bar.
	 *
	 * @return string
	 */
	protected function _buildHeader()
	{
		$collapsed = isset($_COOKIE['iMSCPdebugCollapsed']) ? $_COOKIE['iMSCPdebugCollapsed'] : 0;

		return ('
            <style type="text/css" media="screen">
            	#iMSCPdebug_debug h4 {margin:0.5em;font-weight:bold;}
            	#iMSCPdebug_debug strong {font-weight:bold;}
                #iMSCPdebug_debug { font: 1em Geneva, Arial, Helvetica, sans-serif; position:fixed; bottom:5px; left:0px; color:#fff; z-index: 255;}
                #iMSCPdebug_debug a {color:red;}
                #iMSCPdebug_debug span {color:#fff;}
                #iMSCPdebug_debug p {margin:0;}
                #iMSCPdebug_debug ol {margin:10px 0px; padding:0 25px}
                #iMSCPdebug_debug li {margin:0 0 10px 0;}
                #iMSCPdebug_debug .clickable { cursor:pointer }
                #iMSCPdebug_toggler { font-weight:bold; background:#000; }
                .iMSCPdebug_span { border: 1px solid #ccc; border-right:0px; background:#000; padding: 6px 5px; }
                .iMSCPdebug_last { border: 1px solid #ccc; }
                .iMSCPdebug_panel { text-align:left; position:absolute;bottom:21px;width:600px; max-height:400px; overflow:auto; display:none; background:#000; padding:0.5em; border: 1px solid #ccc; }
                .iMSCPdebug_panel .pre {font: 1em Geneva, Arial, Helvetica, sans-serif; margin:0 0 0 22px}
                #iMSCPdebug_exception { border:1px solid #000;display: block; }
            </style>
            <script type="text/javascript">
                if (typeof jQuery == "undefined") {
                    var scriptObj = document.createElement("script");
                    scriptObj.src = "../themes/default/js/jquery.js";
                    scriptObj.type = "text/javascript";
                    var head=document.getElementsByTagName("head")[0];
                    head.insertBefore(scriptObj,head.firstChild);
                }
                var iMSCPdebugLoad = window.onload;
                window.onload = function(){
                    if (iMSCPdebugLoad) {
                        iMSCPdebugLoad();
                    }
                    //jQuery.noConflict();
                    iMSCPdebugCollapsed();
                };

                function iMSCPdebugCollapsed() {
                    if (' . $collapsed . ' == 1) {
                        iMSCPdebugPanel();
                        jQuery("#iMSCPdebug_toggler").html("&#187;");
                        return jQuery("#iMSCPdebug_debug").css("left", "-"+parseInt(jQuery("#iMSCPdebug_debug").outerWidth()-jQuery("#iMSCPdebug_toggler").outerWidth()+1)+"px");
                    }
                }

                function iMSCPdebugPanel(name) {
                    jQuery(".iMSCPdebug_panel").each(function(i){
                        if(jQuery(this).css("display") == "block") {
                            jQuery(this).slideUp();
                        } else {
                            if (jQuery(this).attr("id") == name)
                                jQuery(this).slideDown();
                            else
                                jQuery(this).slideUp();
                        }
                    });
                }

                function iMSCPdebugSlideBar() {
                    if (jQuery("#iMSCPdebug_debug").position().left >= 0) {
                        document.cookie = "iMSCPdebugCollapsed=1;expires=;path=/";
                        iMSCPdebugPanel();
                        jQuery("#iMSCPdebug_toggler").html("&#187;");
                        return jQuery("#iMSCPdebug_debug").animate({left:"-"+parseInt(jQuery("#iMSCPdebug_debug").outerWidth()-jQuery("#iMSCPdebug_toggler").outerWidth()+1)+"px"}, "normal", "swing");
                    } else {
                        document.cookie = "iMSCPdebugCollapsed=0;expires=;path=/";
                        jQuery("#iMSCPdebug_toggler").html("&#171;");
                        return jQuery("#iMSCPdebug_debug").animate({left:"0px"}, "normal", "swing");
                    }
                }

                function iMSCPdebugToggleElement(name, whenHidden, whenVisible){
                    if(jQuery(name).css("display")=="none"){
                        jQuery(whenVisible).show();
                        jQuery(whenHidden).hide();
                    } else {
                        jQuery(whenVisible).hide();
                        jQuery(whenHidden).show();
                    }
                    jQuery(name).slideToggle();
                }
            </script>');
	}
}
