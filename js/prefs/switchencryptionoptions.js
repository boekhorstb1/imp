/**
 * Managing Keys for identities in the preferences UI.
 *
 * @author     Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var switchEncryption = {

    // checks wheather the smime checkbox is clicked and shows/hides
    checkSMIMOption: function () {
        // get checkbox info
        if ($('smimeselect').checked === true){
            $$('.prefsSmimeContainer').invoke('hide');
        }else{
            $$('.prefsSmimeContainer').invoke('show');
        }
        
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
                // // check if array alraedy exists and remove it if so
                // if (document.body.contains($('keylist'))) {
                //     $('keylist').remove();
                // }

                // // load the response and parse the json to display the keys as a list
                // let data = response.evalJSON();

                // const ul = document.createElement('ul');
                // ul.setAttribute("id", "keylist");

                // let importbutton = $('import_extra_smime_identity');
                // let container = importbutton.next();

                // // if standardidentity is used, relink user to smime prefs page
                // if (data.hasOwnProperty('relink')) {
                //     console.log('test');
                //     const li = document.createElement('li');
                //     console.log(data.relink);
                //     li.innerHTML = data.relink.trim();
                //     ul.append(li);
                //     importbutton.hide();
                //     container.hide();
                // } 
                // else {
                //     importbutton.show();
                //     container.show();
                //     // this currently loads the personal keys from pref: need to load the keys from extratable for the specific identities
                //     for(const [key, value] of Object.entries(data)) {
                //         let li = document.createElement('li');
                //         li.innerHTML = value;
                //         ul.append(li); 
                //     };
                // }
                // console.log(ul);
                // ul.setAttribute("style", "list-style-type:none;");
                // container.after(ul);
            }}
        );

    }
};

// on change of identity load new keys
document.observe('HordeIdentitySelect:change', switchEncryption.checkSMIMOption.bindAsEventListener(switchEncryption));
document.observe('dom:loaded', switchEncryption.checkSMIMOption.bindAsEventListener(switchEncryption));

if ($("smimeselect") != undefined) {
    $("smimeselect").observe("click",    switchEncryption.checkSMIMOption.bindAsEventListener(switchEncryption));
}

// loading this as a file instead of directly inline (compaire SmimePrivatekey.php)
// if ($("import_extra_smime_identity") != undefined) {
//     $("import_extra_smime_identity").observe("click",    switchEncryption.loadPopup.bindAsEventListener(switchEncryption));
// }



