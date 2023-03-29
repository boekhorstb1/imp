/**
 * Managing Keys for identities in the preferences UI.
 *
 * @author     Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var switchEncryption = {

    // checks wheather the smime checkbox is clicked and shows/hides
    checkSMIMEOption: function () {
        // get checkbox info
        if ($('smimeselect').checked === true) {
            $$('.prefsSmimeContainer').invoke('show');
        } else if ($('smimeselect').checked === false) {
            $$('.prefsSmimeContainer').invoke('hide');
            this.showKeys();
        }

    },

    // shows the keys of the identity
    showKeys: function () {

        // get the identityID
        let identity = $("identity");
        let identityID = Number($F(identity));

        HordeCore.doAction('getIdentityPubKey', // NOTE: methods from doAction cannot get any parameters! They expect $this->vars->$name
            {
                identityID: identityID,
            },
            {
                callback: function (response) {
                    // check for keys in the adressbook for the identity and show them

                    let div = $$('div.prefsSmimeContainer')[0];

                    if (div.previous('ul#addressbookpubkey')){
                        $('addressbookpubkey').remove();
                        $('addressbookonlyinfos').remove();
                    }

                    if ($('smimeselect').checked === true){
                        $('addressbookpubkey').remove();
                        $('addressbookonlyinfos').remove();
                    }
                    
                    if ($('smimeselect').checked === false) {

                        let ul = new Element('ul', {
                            id: "addressbookpubkey"
                        });

                        for (let key in response) {
                            if (response.hasOwnProperty(key)) {

                                let li = new Element('li').setStyle({'list-style-type':'none'});
                                let a = new Element('a', {
                                    href: response[key]
                                })
                                a.update(key);
                                li.insert(a);
                                ul.insert(li);
                            }
                        }

                        div.insert({before: ul});

                        // create a text to inform the user
                        let text = "The following key from the addressbook is used for this identity if you want to use the addressbook only (without SMIME-keys):";
                        
                        let infodiv = new Element('div', {
                            id: "addressbookonlyinfos"
                        });
                        infodiv.update(text);
                        ul.insert({before: infodiv});
                


                    }
                }
            }
        );

    }
};

// on change of identity show chosen options for SMIME manament or Adressbook keys management
document.observe('HordeIdentitySelect:change', switchEncryption.checkSMIMEOption.bindAsEventListener(switchEncryption));
document.observe('dom:loaded', switchEncryption.checkSMIMEOption.bindAsEventListener(switchEncryption));
if ($("smimeselect") != undefined) {
    $("smimeselect").observe("click", switchEncryption.checkSMIMEOption.bindAsEventListener(switchEncryption));
}



