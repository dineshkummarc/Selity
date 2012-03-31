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

/*******************************************************************************
 * Script functions
 */

/**
 * Generate page
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function admin_generateLanguagesList($tpl)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');
    $htmlChecked = $cfg->HTML_CHECKED;

    $defaultLanguage = $cfg->USER_INITIAL_LANG;
    $availableLanguages = i18n_getAvailableLanguages();

    if (!empty($availableLanguages)) {
        foreach ($availableLanguages as $languageDefinition) {
            $tpl->assign(
                array(
                    'LANGUAGE_NAME' => tohtml($languageDefinition['language']),
                    'NUMBER_TRANSLATED_STRINGS' => tr('%d strings translated', $languageDefinition['translatedStrings']),
                    'LANGUAGE_REVISION' => $languageDefinition['revision'],
                    'LAST_TRANSLATOR' => preg_replace('/\s<.*>/', '', $languageDefinition['lastTranslator']),
                    'LOCALE_CHECKED' => ($languageDefinition['locale'] == $defaultLanguage) ? $htmlChecked : '',
                    'LOCALE' => $languageDefinition['locale']));

            $tpl->parse('LANGUAGE_BLOCK', '.language_block');
        }
    } else {
        $tpl->assign('LANGUAGES_BLOCK', '');
    }
}

/*******************************************************************************
 * Main script
 */

// Include needed libraries
require_once 'selity-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

// Check for login
check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

// Dispatches the request
if (isset($_POST['uaction'])) {
    if ($_POST['uaction'] == 'uploadLanguage') {
        if (i18n_importMachineObjectFile()) {
            set_page_message(tr('Language file successfully installed.'), 'success');
        }
    } elseif ($_POST['uaction'] == 'changeLanguage') {
        if (i18n_changeDefaultLanguage()) {
            set_page_message(tr('Default language successfully updated.'), 'success');
            // Force change on next load
            redirectTo('multilanguage.php');
        } else {
            set_page_message(tr('Unknown language name.'), 'error');
        }
    } elseif ($_POST['uaction'] == 'rebuildIndex') {
        i18n_buildLanguageIndex();
        set_page_message(tr('Languages index was successfully re-built.'), 'success');
    }
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
    array(
		'layout' => 'shared/layouts/ui.tpl',
        'page' => 'admin/multilanguage.tpl',
        'page_message' => 'layout',
        'languages_block' => 'page',
        'language_block' => 'languages_block'));

$tpl->assign(
    array(
        'TR_PAGE_TITLE' => tr('Selity - Admin / Internationalisation'),
        'THEME_CHARSET' => tr('encoding'),
        'ISP_LOGO' => layout_getUserLogo(),
        'TR_MULTILANGUAGE' => tr('Internationalization'),
        'TR_LANGUAGE_NAME' => tr('Language'),
        'TR_NUMBER_TRANSLATED_STRINGS' => tr('Translated strings'),
        'TR_LANGUAGE_REVISION' => tr('Revision date'),
        'TR_LAST_TRANSLATOR' => tr('Last translator'),
        'TR_DEFAULT_LANGUAGE' => tr('Default language'),
        'TR_SAVE' => tr('Save'),
        'TR_INSTALL_NEW_LANGUAGE' => tr('Install'),
        'TR_LANGUAGE_FILE' => tr('Language file'),
        'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
        'TR_REBUILD_INDEX' => tr('Rebuild languages index'),
        'TR_UPLOAD_HELP' => tr('Only gettext Machine Object files (MO files) are accepted.'),
        'TR_HELP' => tr('Help'),
        'TR_INSTALL' => tr('Install'),
        'TR_CANCEL' => tr('Cancel')));

generateNavigation($tpl);
admin_generateLanguagesList($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
