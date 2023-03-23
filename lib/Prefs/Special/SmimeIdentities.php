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
 * Special prefs handling for the 'smimepublickey' preference, the list of
 * public certificates from the user's address book(s).
 *
 * @author    Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_Special_SmimeIdentities implements Horde_Core_Prefs_Ui_Special
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
        global $injector, $page_output;

        $view = new Horde_View([
            'templatePath' => IMP_TEMPLATES . '/prefs',
        ]);
        $view->addHelper('Horde_Core_View_Helper_Help');
        $view->addHelper('Text');

        $identity = $injector->getInstance('IMP_Identity');
        $identityID = $identity->getDefault();
        $view->defaultIdentity = $identity->getFullname($identityID);
        $view->defaultAdres = $identity->getEmail($identityID);
        $view->linkMailIdentity = Horde::url($GLOBALS['registry']->getServiceLink('prefs', 'imp'), true)->add('group', 'identities');

        return $view->render('smimeidentities');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification;

        //dd($ui->vars);

        if (isset($ui->vars->delete_smime_pubkey)) {
            try {
                echo 'do something here';
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }
        }

        return false;
    }
}
