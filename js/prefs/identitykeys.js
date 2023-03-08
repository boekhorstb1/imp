/**
 * Managing Keys for identities in the preferences UI.
 *
 * @author     Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpHtmlIdentitykeyPrefs = {

    // Variables defined by other code: editor, sigs
    // I guess this code adds the signature to the new identity ...?
    // TODO: function to add a new set of keys 

    changeIdentity: function(e)
    {
        switch (e.memo.pref) {
        case 'identitykeys_html_select':
            if (this.editor) {
                this.editor.setData(this.sigs[e.memo.i]);
            } else {
                this.changeIdentity.bind(this, e).delay(0.1);
            }
            break;
        }
    },

    onDomLoad: function()
    {
       
    }

};

document.observe('dom:loaded', ImpHtmlIdentitykeyPrefs.onDomLoad.bind(ImpHtmlIdentitykeyPrefs));
document.observe('HordeIdentitySelect:change', ImpHtmlIdentitykeyPrefs.changeIdentity.bindAsEventListener(ImpHtmlIdentitykeyPrefs));
