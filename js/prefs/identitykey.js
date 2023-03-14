/**
 * Managing Keys for identities in the preferences UI.
 *
 * @author     Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpHtmlIdentitykeyPrefs = {

    // This is called when: HordeIdentitySelect:change is called 

    notifyID: function()
    {
        let identity = $('identity');
        let id = Number($F(identity));
        console.log(id);
    }

};

document.observe('HordeIdentitySelect:change', ImpHtmlIdentitykeyPrefs.notifyID.bindAsEventListener(ImpHtmlIdentitykeyPrefs));
