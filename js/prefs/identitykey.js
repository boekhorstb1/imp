/**
 * Managing Keys for identities in the preferences UI.
 *
 * @author     Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpHtmlIdentitykeyPrefs = {

    loadPopup: function (e) {

        // get the identity to create the needed popup with
        let identity = $("identity");
        let id = Number($F(identity));

        var params = {
            identityID : id,
            actionID : "import_extra_identity_certs",
            page: "smime"
        };
        
        HordePopup.popup(
            {
                height: 450,
                name: "identity keys",
                noalert: true,
                onload: this.rUrl, // TODO: currenlty not working because of async problems? This variable is not set too late
                params: params,
                width: 750,
                url: "/horde/../imp/basic.php" // TODO: hardcoded because I could not find a good way to retrieve the url directly (not asynchronously)
            }
        );
        e.stop();
    },


    reloadUrl: function () {
        //TODO: getReload currently cannot write to rUrl, for some reason it takes too long?
        HordeCore.doAction('getReload',
            {},
            {callback: function(r){
                this.rUrl = r;
            }}
        );
    },

    // shows the keys of the identity
    showKeys: function () {

        // get the identity to create the needed popup with
        let identity = $("identity");
        let id = Number($F(identity));

        console.log('o yeah');
        console.log(id);
        
        HordeCore.doAction('getIdentityKeys', // NOTE: methods from doAction cannot get any parameters! They expect $this->vars->$name
            {
                strangeId: id,
                wierd: "wierdTest"
            },
            {callback: function(response){

                // check if array alraedy exists and remove it if so
                if (document.body.contains($('keylist'))) {
                    console.log('tja');
                    $('keylist').remove();
                }

                // load the response and parse the json to display the keys as a list
                let data = response.evalJSON();
                
                const ul = document.createElement('ul');
                ul.setAttribute("id", "keylist");

                let container = $('import_extra_smime_identity').next();

                data.forEach(element => {
                    const li = document.createElement('li');
                    li.innerHTML = element.trim();
                    ul.appendChild(li);
                });

                ul.setAttribute("style", "list-style-type:none;");

                container.after(ul);

            }}
        );

    }
};

// on change of identity load new keys
document.observe('HordeIdentitySelect:change', ImpHtmlIdentitykeyPrefs.showKeys.bindAsEventListener(ImpHtmlIdentitykeyPrefs));
document.observe('dom:loaded', ImpHtmlIdentitykeyPrefs.showKeys.bindAsEventListener(ImpHtmlIdentitykeyPrefs));

// loading this as a file instead of directly inline (compaire SmimePrivatekey.php)
if ($("import_extra_smime_identity") != undefined) {
    $("import_extra_smime_identity").observe("click",    ImpHtmlIdentitykeyPrefs.loadPopup.bindAsEventListener(ImpHtmlIdentitykeyPrefs));
}



