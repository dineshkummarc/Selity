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
 * Class to parse gettext machine object (MO) files.
 *
 * @see http://www.gnu.org/software/gettext/manual/gettext.html#MO-Files
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @version 0.0.1
 */
class iMSCP_I18n_Parser_Mo extends iMSCP_I18n_Parser
{
	/**
	 * Byte ordering.
	 *
	 * @var string
	 */
	protected $_order;

	/**
	 * Number of strings in the file.
	 *
	 * @var int
	 */
	protected $_nbStrings;

	/**
	 * Index table of original strings (msgid).
	 *
	 * @var array
	 */
	protected $_msgidIndexTable;

	/**
	 * Index table of translated strings (msgstr).
	 *
	 * @var array
	 */
	protected $_msgstrIndexTable;

	/**
	 * Returns number of stranslated strings.
	 *
	 * @return int Number of translated strings
	 */
	public function getNumberOfTranslatedStrings()
	{
		if(null === $this->_nbStrings) {
			$this->getHeaders();
		}

		return $this->_nbStrings - 1;
	}

	/**
	 * Parse a machine object file.
	 *
	 * @throws iMSCP_i18n_Parser_Exception When file cannot be opened
	 * @throws iMSCP_i18n_Parser_Exception When file have bad magic number
	 * @throws iMSCP_i18n_Parser_Exception When file part to parse is unknow
	 * @param int $part Part to parse - Can be either iMSCP_I18n_Parser::HEADERS or
	 *                                  iMSCP_I18n_Parser::TRANSLATION_TABLE
	 * @return array|string An array of pairs key/value where the keys are the
	 *                      original strings (msgid) and the values, the translated
	 *                      strings (msgstr) or a string that contains headers, each
	 * 						of them separated by EOL.
	 */
	protected function _parse($part)
	{
		if ($this->_fh === null) {
			if (!($this->_fh = fopen($this->_filePath, 'rb'))) {
				require_once 'iMSCP/I18n/Parser/Exception.php';
				throw new iMSCP_i18n_Parser_Exception(
						'Unable to open ' . $this->_filePath);
			}
		}

		if ($this->_order === null) {
			// Magic number
			$value = unpack('V', fread($this->_fh, 4));
			$magicNumber = array_shift($value);

			if ($magicNumber == (int)0x0950412de ||
				dechex($magicNumber) == 'ffffffff950412de'
			) {
				$this->_order = 'V'; // Little Endian
			} elseif($magicNumber == (int)0x0de120495) {
				$this->_order = 'N'; // Big endian
			} else {
				require_once 'iMSCP/I18n/Parser/Exception.php';
				throw new iMSCP_i18n_Parser_Exception(
						'Bad magic number in ' . $this->_filePath);
			}

			// Skipping the revision number
			fseek($this->_fh, 4, SEEK_CUR);

			// number of strings 											N
			$value = unpack($this->_order, fread($this->_fh, 4));
			$this->_nbStrings = array_shift($value);

			// offset of table with original strings						O
			$value = unpack($this->_order, fread($this->_fh, 4));
			$msgidtableOffset = array_shift($value);

			// offset of table with translation strings						T
			$value = unpack($this->_order, fread($this->_fh, 4));
			$msgstrTableOffset = array_shift($value);


			// each string descriptor uses two 32 bits integers, one for the string
			// length, another for the offset of the string
			$count = $this->_nbStrings * 2;

			// getting index of original strings
			fseek($this->_fh, $msgidtableOffset);
			$this->_msgidIndexTable = unpack(
				$this->_order . $count, fread($this->_fh, ($count * 4)));

			// getting index of translated strings
			fseek($this->_fh, $msgstrTableOffset);
			$this->_msgstrIndexTable = unpack(
				$this->_order . $count, fread($this->_fh, ($count * 4)));

		}

		switch ((int)$part) {
			case self::HEADERS:
				fseek($this->_fh, $this->_msgstrIndexTable[2]);
				return fread($this->_fh, $this->_msgstrIndexTable[1]);
				break;
			case self::TRANSLATION_TABLE:
				$nbString = $this->_nbStrings;
				$parseResult = array();

				for ($index = 1; $index < $nbString; $index++) {
					// Getting msgid
					fseek($this->_fh, $this->_msgidIndexTable[$index * 2 + 2]);
					$msgid = fread($this->_fh, $this->_msgidIndexTable[$index * 2 + 1]);

					// Getting msgstr
					fseek($this->_fh, $this->_msgstrIndexTable[$index * 2 + 2]);
					if (!$length = $this->_msgstrIndexTable[$index * 2 + 1]) {
						$msgstr = '';
					} else {
						$msgstr = fread($this->_fh, $length);
					}

					$parseResult[$msgid] = $msgstr;
				}

				return $parseResult;
				break;
			default:
				require_once 'iMSCP/I18n/Parser/Exception.php';
				throw new iMSCP_i18n_Parser_Exception('Unknown part type to parse');
		}
	}
}
