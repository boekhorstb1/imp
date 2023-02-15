<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Attach the passphrase dialog to the page.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Imple_PassphraseDialog extends Horde_Core_Ajax_Imple
{
    /**
     * @param array $params  Configuration parameters.
     *   - onload: (boolean) [OPTIONAL] If set, will trigger action on page
     *             load.
     *   - params: (array) [OPTIONAL] Any additional parameters to pass to
     *             AJAX action.
     *   - type: (string) The dialog type.
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);
    }

    /**
     */
    protected function _attach($init)
    {
        global $page_output;

        if ($init) {
            $page_output->addScriptPackage('Horde_Core_Script_Package_Dialog');
            $page_output->addScriptFile('passphrase.js', 'imp');
        }
        \Horde::debug('test0', '/dev/shm/imple', false);

        $params = $this->_params['params']
            ?? [];
        if (isset($params['reload'])) {
            $params['reload'] = strval($params['reload']);
        }

        \Horde::debug('test2', '/dev/shm/imple', false);
        switch ($this->_params['type']) {
            case 'pgpPersonal':
                $text = _('Enter your personal PGP passphrase.');
                break;

            case 'pgpSymmetric':
                $text = _('Enter the passphrase used to encrypt this message.');
                break;

            case 'smimePersonal':
                \Horde::debug('hapenninnnng', '/dev/shm/imple', false);
                $text = _('Enter your personal S/MIME passphrase.');
                break;
        }
        \Horde::debug('test3', '/dev/shm/imple', false);
        $js_params = [
            'hidden' => array_merge($params, ['type' => $this->_params['type']]),
            'text' => $text,
        ];

        $js = 'ImpPassphraseDialog.display(' . Horde::escapeJson($js_params, ['nodelimit' => true]) . ')';
        \Horde::debug('test4', '/dev/shm/imple', false);
        \Horde::debug($this->_params['onload'], '/dev/shm/imple', false);

        if (!empty($this->_params['onload'])) {
            $page_output->addInlineScript([$js], true);
            return false;
        }
        \Horde::debug('test5', '/dev/shm/imple', false);
        return $js;
    }

    /**
     */
    protected function _handle(Horde_Variables $vars)
    {
        return false;
    }
}
