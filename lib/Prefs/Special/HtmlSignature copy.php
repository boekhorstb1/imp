<?php
/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Special prefs handling for the 'signature_html_select' preference.
 *
 * @author    Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_Special_IdentityKeys implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $conf, $injector, $page_output, $prefs;

        $page_output->addScriptFile('editor.js');
        $page_output->addScriptFile('prefs/identitykeys.js');
        //$page_output->addScriptPackage('IMP_Script_Package_Editor'); // I just need to somehow upload those keys, no need for fancy editing

        $page_output->addInlineJsVars(array(
            'ImpIdentityKeysPrefs.sigs' =>
                array(-1 => $prefs->getValue('identitiykey')) +
                $injector->getInstance('IMP_Identity')->getAll('identitiykey')
        ));

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('Text');

        $view->identitiykey = $prefs->getValue('identitiykey');

        return $view->render('signaturehtml');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification;

        try {
            /* Throws exception if over image size limit. */
            new IMP_Compose_HtmlSignature($ui->vars->signature_html);
        } catch (IMP_Exception $e) {
            $notification->push($e, 'horde.error');
            return false;
        }

        return $injector->getInstance('IMP_Identity')->setValue(
            'signature_html',
            $ui->vars->signature_html
        );
    }

}
