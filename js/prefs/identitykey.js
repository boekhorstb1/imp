/**
 * Managing Keys for identities in the preferences UI.
 *
 * @author     Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpHtmlIdentitykeyPrefs = {

    // This is called when: HordeIdentitySelect:change is called 

    notifyID: function () {
        // get the selected ID
        let identity = $('identity');
        let id = Number($F(identity));

        // get the address
        //let addr = $('from_addr').value;


        // send the selected ID to PHP... to the session to vars?
        HordeCore.doAction('saveId', { param: id }, { });
    }
};

    document.observe('HordeIdentitySelect:change', ImpHtmlIdentitykeyPrefs.notifyID.bindAsEventListener(ImpHtmlIdentitykeyPrefs));
