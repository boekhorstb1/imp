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
            {}
        );
    },

    //'if ($("import_extra_smime_identity") != undefined) $("import_extra_smime_identity").observe("click", function(e) { let identity = $("identity"); let id = Number($F(identity)); var params = {}; params.identityID = id; params.actionID = "import_extra_identity_certs"; HordePopup.popup({height: 450, menu: "no", name: "identity keys", noalert: true, onload: ' . base64_encode($ui->selfUrl()->setRaw(true)) . ' , params, width: 750, url: '. $smime_url .' }); e.stop(); })'

    loadPopup: function (e) {

        // get the identity to create the needed popup with
        let identity = $("identity");
        let id = Number($F(identity));

        var params = {
            identityID : id,
            actionID : "import_extra_identity_certs",
            page: "smime"
        };

        // get the smime url to create the popup for
        var smime_url = this.getSmimeUrl();
        console.log(smime_url);
        
        console.log('it is reached..');
        
        HordePopup.popup(
            {
                height: 450,
                name: "identity keys",
                noalert: true,
                onload: this.reloadUrl(),
                params: params,
                width: 750,
                url: "/horde/../imp/basic.php"
            }
        );
        e.stop();
    },

    getSmimeUrl: function() {
        HordeCore.doAction('getSmimeUrl',
            {},
            {
                callback: this.setSmimeVariable.bind(this)
            }
        );
    },

    setSmimeVariable: function (response) {
        return response;
    },

    reloadUrl: function () {
        
    }
};

document.observe('HordeIdentitySelect:change', ImpHtmlIdentitykeyPrefs.saveId.bindAsEventListener(ImpHtmlIdentitykeyPrefs));
if ($("import_extra_smime_identity") != undefined) {
    $("import_extra_smime_identity").observe("click", ImpHtmlIdentitykeyPrefs.loadPopup.bindAsEventListener(ImpHtmlIdentitykeyPrefs));
}


