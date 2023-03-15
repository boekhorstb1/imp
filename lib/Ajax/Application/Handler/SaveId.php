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

class IMP_Ajax_Application_Handler_SaveId extends Horde_Core_Ajax_Application_Handler
{
    /**
     * Ajax action: return the reload Url as generated by UI class of Smime
     */
    public function getReload()
    {
        global $vars;
        $ui = new Horde_Core_Prefs_Ui($vars);
        $url = base64_encode($ui->selfUrl()->setRaw(true));
        return $url;
    }

    /**
     * Ajax action: get Identity keys
     */
    public function getIdentityKeys()
    {
        //listAllKeys($prefName = 'smime_private_key', $identityID); // TODO: what about singkeys?
    }
}
