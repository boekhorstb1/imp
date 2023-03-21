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

        // get the default identity
        let default_identity = $("default_identity");
        let defaultId = Number($F(default_identity));
        
        HordeCore.doAction('getIdentityKeys', // NOTE: methods from doAction cannot get any parameters! They expect $this->vars->$name
            {
                defaultId: defaultId,
                strangeId: id
            },
            {callback: function(response){
                // check if array alraedy exists and remove it if so
                if (document.body.contains($('keylist'))) {
                    $('keylist').remove();
                }

                // load the response and parse the json to display the keys as a list
                let data = response.evalJSON();
                
                const ul = document.createElement('ul');
                ul.setAttribute("id", "keylist");

                let importbutton = $('import_extra_smime_identity');
                let container = importbutton.next();

                // if standardidentity is used, relink user to smime prefs page
                if (data.hasOwnProperty('relink')) {
                    console.log('test');
                    const li = document.createElement('li');
                    console.log(data.relink);
                    li.innerHTML = data.relink.trim();
                    ul.append(li);
                    importbutton.hide();
                    container.hide();
                } 
                else {
                    importbutton.show();
                    container.show();
                    // this currently loads the personal keys from pref: need to load the keys from extratable for the specific identities
                    for(const [key, value] of Object.entries(data)) {
                        let li = document.createElement('li');
                        li.innerHTML = value;
                        ul.append(li); 
                    };
                }
                console.log(ul);
                ul.setAttribute("style", "list-style-type:none;");
                container.after(ul);
            }}
        );

    }
};

// on change of identity load new keys
document.observe('HordeIdentitySelect:change', ImpHtmlIdentitykeyPrefs.showKeys.bindAsEventListener(ImpHtmlIdentitykeyPrefs));
document.observe('dom:loaded', ImpHtmlIdentitykeyPrefs.showKeys.bindAsEventListener(ImpHtmlIdentitykeyPrefs));

// also need to trigger showKeys when the default_identity is changed
if ($("default_identity") != undefined) {
    $("default_identity").observe("click",    ImpHtmlIdentitykeyPrefs.showKeys.bindAsEventListener(ImpHtmlIdentitykeyPrefs));
}

// loading this as a file instead of directly inline (compaire SmimePrivatekey.php)
if ($("import_extra_smime_identity") != undefined) {
    $("import_extra_smime_identity").observe("click",    ImpHtmlIdentitykeyPrefs.loadPopup.bindAsEventListener(ImpHtmlIdentitykeyPrefs));
}



