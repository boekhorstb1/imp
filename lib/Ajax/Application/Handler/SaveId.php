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
        global $injector, $session;

        $id = $this->vars->param;

        // save to imp, identity, id
        try {
            $session->set('imp', 'identity', $id);
        } catch (\Throwable $th) {
            throw $th;
        }

        \Horde::debug($session->get('imp', 'identity'), '/dev/shm/backend', false);
    }
}
