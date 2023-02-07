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
 * Defines AJAX actions used in the IMP passphrase dialog.
 *
 * @author    Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @category  Horde
 * @copyright 2012-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Handler_SwitchKeys extends Horde_Core_Ajax_Application_Handler
{

    public function listKeysToHTML(){
        global $injector;

        // get only the ids
        $list = $injector->getInstance('IMP_Smime')->listPrivateKeyIds();
        
        if (empty($list)){
            return false;
        }
        else {
            // add them to a html chunk to serve
            $htmlchunk = '<form name="keyslist" action="#"><select name="selectedkey">';
            foreach ($list as $key => $value) {
                $htmlchunk = $htmlchunk."<option>".$value."</option>";
            }
            $htmlchunk = $htmlchunk.'</select><input id="selectedkey" name="selectedkey" list="certificatekeys" type="text" style="display:none;" />';

            // return the html
            return $htmlchunk;
        }
    }


    /**
     * AJAX action: Check chosen private keys to use for decryption.
     *
     * Variables required in form input:
     *   - dialog_input: (string) Input from the dialog screen.
     *   - reload: (mixed) If set, reloads page instead of returning data.
     *   - symmetricid: (string) The symmetric ID to process.
     *   - type: (string) The Key type.
     *
     * @return boolean  True on success.
     */
    public function checkKeys()
    {
        global $injector, $notification;

        $result = false;

        if (!$this->vars->dialog_input) {
            $notification->push(_("No Keys selected."), 'horde.error');
            return $result;
        }

        try {
            Horde::requireSecureConnection();

            switch ($this->vars->type) {
            case 'pgpPersonal':
                $result = $injector->getInstance('IMP_Smime')->getExtraPrivateKey('personal', $this->vars->dialog_input);
                break;

            case 'pgpSymmetric':
                $result = $injector->getInstance('IMP_Smime')->getExtraPrivateKey('symmetric', $this->vars->dialog_input);
                break;

            case 'smimePersonal':
                $result = $injector->getInstance('IMP_Smime')->getExtraPrivateKey($this->vars->dialog_input);
                break;
            }

            if ($result) {
                $notification->push(_("New key has been used for decrption."), 'horde.success');
            } else {
                $notification->push(_("Decryption with key failed, try another key."), 'horde.error');
            }
        } catch (Horde_Exception $e) {
            $notification->push($e, 'horde.error');
        }

        return ($result && $this->vars->reload)
            ? new Horde_Core_Ajax_Response_HordeCore_Reload($this->vars->reload)
            : $result;
    }

}
