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
 * Base class to parse gettex files (*.po, *.mo)
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @version 0.0.1
 */
abstract class iMSCP_I18n_Parser
{
	/**
	 * Headers.
	 *
	 * @var int
	 */
	const HEADERS = 1;

	/**
	 * Translation table.
	 *
	 * @var int
	 */
	const TRANSLATION_TABLE = 2;

	/**
	 * File handle.
	 *
	 * @var resource
	 */
	protected $_fh;

	/**
	 * Path to the gettext file.
	 *
	 * @var string
	 */
	protected $_filePath;

	/**
	 * Headers from gettext file.
	 *
	 * A string that contains headers, each of them separated by EOL.
	 *
	 * @var string
	 */
	protected $_headers = '';

	/**
	 * Translation table.
	 *
	 * An array of pairs key/value where the keys are the original strings (msgid)
	 * and the values, the translated strings (msgstr).
	 *
	 * @var array
	 */
	protected $_translationTable = array();

	/**
	 * Constructor.
	 *
	 * @throws iMSCP_i18n_Exception When file is not readable
	 * @param $filePath Path to gettext file
	 */
	public function __construct($filePath)
	{
		$filePath = (string)$filePath;

		if (!is_readable($filePath)) {
			require_once 'iMSCP/I18n/Parser/Exception.php';
			throw new iMSCP_i18n_Parser_Exception("$filePath is not readable");
		}

		$this->_filePath = $filePath;
	}

	/**
	 * Returns headers.
	 *
	 * @return string A string that contains gettext file headers, each separed by EOL
	 */
	public function getHeaders()
	{
		if (empty($this->_headers)) {
			$this->_headers = $this->_parse(self::HEADERS);
		}

		return $this->_headers;
	}

	/**
	 * Returns translation table.
	 *
	 * @return array An array of pairs key/value where the keys are the original
	 *               strings (msgid) and the values, the translated strings (msgstr)
	 */
	public function getTranslationTable()
	{
		if (empty($this->_translationTable)) {
			$this->_translationTable = $this->_parse(self::TRANSLATION_TABLE);
		}

		return $this->_translationTable;
	}

	/**
	 * Retruns project id version header value.
	 *
	 * @return string Project id version header value
	 */
	public function getProjectIdVersion()
	{
		return $this->_getHeaderValue('Project-Id-Version:');
	}

	/**
	 * Returns report msgid bugs value header value.
	 *
	 * @return string R eport msgid bugs header value
	 */
	public function getReportMsgidBugs()
	{
		return $this->_getHeaderValue('Report-Msgid-Bugs-To:');
	}

	/**
	 * Returns pot creation date header value.
	 *
	 * @return string POT creation date header value
	 */
	public function getPotCreationDate()
	{
		return $this->_getHeaderValue('POT-Creation-Date:');
	}

	/**
	 * Returns po creation date header value.
	 *
	 * @return string PO creation date header value
	 */
	public function getPoRevisionDate()
	{
		return $this->_getHeaderValue('PO-Revision-Date:');
	}

	/**
	 * Returns last translator header value.
	 *
	 * @return string Last translator header value
	 */
	public function getLastTranslator()
	{
		return $this->_getHeaderValue('Last-Translator:');
	}

	/**
	 * Returns language team header value.
	 *
	 * @return string language team header value
	 */
	public function getLanguageTeam()
	{
		return $this->_getHeaderValue('Language-Team:');
	}

	/**
	 * Returns mime version header value.
	 *
	 * @return string Mime version header value
	 */
	public function getMimeVersion()
	{
		return $this->_getHeaderValue('MIME-Version:');
	}

	/**
	 * Returns content type header value.
	 *
	 * @return string Content type header value
	 */
	public function getContentType()
	{
		return $this->_getHeaderValue('Content-Type:');
	}

	/**
	 * Returns content transfer encoding header value.
	 *
	 * @return string Content transfer encoding header value
	 */
	public function getContentTransferEncoding()
	{
		return $this->_getHeaderValue('Content-Transfer-Encoding:');
	}

	/**
	 * Returns language header value.
	 *
	 * @return string Language header value
	 */
	public function getLanguage()
	{
		return $this->_getHeaderValue('Language:');
	}

	/**
	 * Returns plural forms header value.
	 *
	 * @return string Plural forms header value
	 */
	public function getPluralForms()
	{
		return $this->_getHeaderValue('Plural-Forms:');
	}

	/**
	 * Returns number of translated strings.
	 *
	 * @abstract
	 * @return int Number of translated strings
	 */
	abstract public function getNumberOfTranslatedStrings();

	/**
	 * Parse file.
	 *
	 * @abstract
	 * @param int $part Part file to parse {@link self::HEADER} or
	 *                  {@link self::TRANSLATION_TABLE}
	 * @return array|string An array of pairs key/value where the keys are the
	 *                      original strings (msgid) and the values, the translated
	 *                      strings (msgstr) or a string that contains headers, each
	 * 						of them separated by EOL.
	 */
	abstract protected function _parse($part);

	/**
	 * Returns given header value.
	 *
	 * @param string $header header name
	 * @return string header value
	 */
	protected function _getHeaderValue($header)
	{
		$headers = $this->getHeaders();
		$header = str_replace(chr(13), '',substr($headers, strpos($headers, $header)));

		$header =  substr($header, ($start = strpos($header, ':') + 2),
			(strpos($header, chr(10)) - $start));

		return (!empty($header)) ? $header : '';
	}

	/**
	 * Destructor.
	 */
	public function __destruct()
	{
		if($this->_fh !== null) {
			fclose($this->_fh);
		}
	}
}
