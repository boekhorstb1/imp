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
 * Special prefs handling for the 'smimeprivatekey' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2012-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_Special_CertificatesUpload implements Horde_Core_Prefs_Ui_Special
{
    /* Loading the base url for smime: in order to set links for the various certificates */
    private $smime_url= null;

    /* Loading url of prefs page */
    private $self_url = null;

    /**
     * Init function, first to run
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
        $this->smime_url = IMP_Basic_Smime::url();
        $this->self_url = $ui->selfUrl(['special' => true, 'token' => true]);
    }

    /**
     * Displays function of Horde_Core_Prefs_Ui, called after init()
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $page_output, $browser;

        /* Adding js to page output */
        //$page_output->addScriptFile('prefs/certificatesUploader.js');

        // load view from templat path
        $view = new Horde_View([
            'templatePath' => IMP_TEMPLATES . '/prefs',
        ]);

        // render an import button (which will save content to a string)
        // first check if uploading is possible
        if ($browser->allowFileUploads()) {
            $view->can_import = true;
            // setting inline scripts to view: containt popup.js wich adds the GET parameter 'import_identitycertificate_key'
            $page_output->addInlineScript([
                '$("import_smime_identity").observe("click", function(e) { ' . Horde::popupJs($this->smime_url, ['params' => ['actionID' => 'import_identitycertificate_key', 'reload' => base64_encode($ui->selfUrl()->setRaw(true))], 'height' => 275, 'width' => 750, 'urlencode' => true]) . '; e.stop(); })',
            ], true);
        }

        return $view->render('importcerts');
    }


    /**
     * Update Display values: this I want to do per ajax if possible
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
    }
}
