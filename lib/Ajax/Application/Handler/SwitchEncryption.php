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
use IMP_Prefs_SwitchHandler as SwitchHandler;
/**
 * Defines AJAX actions used in the IMP alias dialog.
 *
 * @author    Rafael te Boekhorst <boekhorst@b1-systems.de>
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
     * 
     * @return array  Array with keys of identity.
     */
    public function getIdentityPubKey()
    {
        // get ID of identity and email
        $identityID = $this->vars->identityID;

        $handler = new SwitchHandler($GLOBALS['injector']);
        return $handler->getPublicKeysForPrefsIdentities($identityID);
    }
}
