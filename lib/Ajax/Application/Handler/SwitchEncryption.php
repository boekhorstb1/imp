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
 * Defines AJAX actions used in the IMP alias dialog.
 *
 * @author    Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Handler_SwitchEncryption extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Get Keys from Identity of adressbook.
     *
     * @return array  Array with keys of identity.
     */
    public function getIdentityPubKey()
    {
        global $injector, $notification;

        // get ID of identity and email
        $identityID = $this->vars->identityID;
        $identity = $injector->getInstance('IMP_Identity');
        $email = $identity->getEmail($identityID);
        $name = $identity->getName($identityID);

        $smime_url = IMP_Basic_Smime::url();
        $self_url = Horde::url($GLOBALS['registry']->getServiceLink('prefs', 'imp'), false);

        try {
            $linksToKey = [
                'view' => Horde::link($smime_url->copy()->add(['actionID' => 'view_public_key', 'email' => $email]), sprintf(_('View %s Public Key'), $name), null, 'view_key'),
                'info' => Horde::link($smime_url->copy()->add(['actionID' => 'info_public_key', 'email' => $email]), sprintf(_('View %s Public Info'), $name), null, 'info_key'),
                ];
        } catch (\Throwable $th) {
            throw $th;
        }


        return $linksToKey;
    }
}
