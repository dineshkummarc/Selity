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
 * Class to parse gettext portable object files.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @version 0.0.1
 */
class iMSCP_I18n_Parser_Po extends iMSCP_I18n_Parser
{
	/**
	 * Returns number of translated strings.
	 *
	 * @throws iMSCP_I18n_Parser_Exception
	 * @return void
	 */
	public function getNumberOfTranslatedStrings()
	{
		require_once 'iMSCP/I18n/Parser/Exception.php';
		throw new iMSCP_I18n_Parser_Exception('Not Yet Implemented');
	}

	/**
	 * Parse a portable object file.
	 *
	 * @throws iMSCP_I18n_Parser_Exception
	 * @param int $part Part to parse (default to iMSCP_I18n_Parser::ALL)
	 * @return array|string An array of pairs key/value where the keys are the
	 *                      original strings (msgid) and the values, the translated
	 *                      strings (msgstr) or a string that contains headers, each
	 * 						of them separated by EOL.
	 */
	protected function _parse($part)
	{
		require_once 'iMSCP/I18n/Parser/Exception.php';
		throw new iMSCP_i18n_Exception('Not Yet Implemented');
	}
}
