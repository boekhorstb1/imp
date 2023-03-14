/**
 * Managing Keys for identities in the preferences UI.
 *
 * @author     Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpHtmlIdentitykeyPrefs = {

    // This is called when: HordeIdentitySelect:change is called 

    saveId: function () {
        // get the selected ID
        let identity = $('identity');
        let id = Number($F(identity));

        // send the selected ID to PHP... to the session to vars?
        HordeCore.doAction('saveId',
            { param: id },
            { }
            );
    },

    callback: function(r)
    {
        if (r) {
            // height 450
            // width 750
            // 'urlencode' => true
            // 'identityID' => $identityID
            // 'actionID' => 'import_extra_identity_certs', 'reload' => base64_encode($ui->selfUrl()->setRaw(true))], 
            //let identity = $('identity'); let id = Number($F(identity)); let params = {}; params.identityID = id; params.actionID: 'import_extra_identity_certs'; HordePopup.popup({height: 450, menu: 'no', name: 'identity', noalert: true, onload: this.reload, params }, width: 750, url: $url);
            

           
            // params.identityID = id;
            // params.actionID: 'import_extra_identity_certs';

            // HordePopup.popup({height: 450, menu: 'no', name: 'identity', noalert: true, onload: this.reload, params }, width: 750, url: );
        }
    },

    reload: function(){
        location.window.reload();
    }
};

document.observe('HordeIdentitySelect:change', ImpHtmlIdentitykeyPrefs.saveId.bindAsEventListener(ImpHtmlIdentitykeyPrefs));
