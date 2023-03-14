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
     * AJAX action: SaveId.
     */
    public function saveId()
    {
        global $injector, $session, $page_output, $vars;

        $id = $this->vars->param;

        //\Horde::debug($id, '/dev/shm/ids', false);
        // save to imp, identity, id
        try {
            $session->set('imp', 'identity', $id);
            // $ui = new Horde_Core_Prefs_Ui($vars);
            // $smime_url = IMP_Basic_Smime::url();
            // $page_output->addInlineScript([
            //     'if ($("import_extra_smime_identity") != undefined) $("import_extra_smime_identity").observe("click", function(e) { ' . Horde::popupJs($smime_url, ['params' => ['identityID' => $id, 'actionID' => 'import_extra_identity_certs', 'reload' => base64_encode($ui->selfUrl()->setRaw(true))], 'height' => 450, 'width' => 750, 'urlencode' => true]) . '; e.stop(); })',
            // ], true);

        } catch (\Throwable $th) {
            throw $th;
        }

        return true;
        //\Horde::debug($session->get('imp', 'identity'), '/dev/shm/backend', false);
    }


}
