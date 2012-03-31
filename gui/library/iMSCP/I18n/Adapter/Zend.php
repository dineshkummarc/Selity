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
/** @see Zend_Translate_Adapter */
require_once 'Zend/Translate/Adapter.php';

/**
 * Transitional adapter class for Zend.
 *
 * This adapter is coded in a dirty style. It provides an ugly way to translate
 * validation messages from Zend_validator by using the i-MSCP translation system.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	I18n
 * @copyright	2010-2012 by i-MSCP team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_I18n_Adapter_Zend extends Zend_Translate_Adapter
{
	/**
	 * Constructor.
	 *
	 * We do no want use Zend translation feature. This constructor is only intended
	 * to discard the Zend_Translate_Adapter::_constructor() call.
	 */
	public function __construct()
	{

	}

	/**
	 * Pure compatibility issue - Always return FALSE.
	 *
	 * @param $messageId
	 * @param bool $original
	 * @param null $locale
	 * @return bool
	 */
	public function isTranslated($messageId, $original = false, $locale = null)
	{
		return false;
	}

	/**
	 * Translates the given string by using i-MSCP translation system.
	 *
	 * @param $messageId $messageId Translation string
	 * @param null $locale UNUSED HERE
	 * @return string
	 */
	public function translate($messageId, $locale = null)
	{
		return tr($messageId);
	}

	/**
	 * Returns the adapter name
	 *
	 * @return string
	 */
	public function toString()
	{
		// TODO: Implement toString() method.
	}

	/**
	 * Load translation data
	 *
	 * @param  mixed			  $data
	 * @param  string|Zend_Locale $locale
	 * @param  array			  $options (optional)
	 * @return array
	 */
	protected function _loadTranslationData($data, $locale, array $options = array())
	{
		// TODO: Implement _loadTranslationData() method.
	}
}
