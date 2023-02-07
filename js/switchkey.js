/**
 * Handling of the SwitchKey dialog.
 *
 * @author     Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpSwitchKeyDialog = {

    display: function(data)
    {
        HordeCore.doAction(
            'listKeysToHTML', // method name
            {},
            {
                callback: (response) => {
                    if (response == false){
                        HordeDialog.display(Object.extend(data, {                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 
                            form_id: 'imp_switchkey',
                            form: '<div id="imp_listkeys class="imp_listkeys" > You have not other keys set'
                        }));
                    }
                    else {
                        HordeDialog.display(Object.extend(data, {                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 
                            form_id: 'imp_switchkey',
                            form: '<div id="imp_listkeys class="imp_listkeys" >' + response
                        }));
                    }
                }
            }
        );
    },

    onClick: function(e)
    {
        // add an action handled by Horde to find the necessary keys to switch
        switch (e.element().identify()) {
        case 'imp_switchkey':
            HordeCore.doAction(
                'checkKeys',
                e.findElement('FORM').serialize(true),
                { callback: this.callback.bind(this) }
            );
            break;
        }
    },

    callback: function(r)
    {
        if (r) {
            $('imp_switchkey').fire('ImpSwitchKeyDialog:success');
            HordeDialog.close();
        }
    }

};

document.observe('HordeDialog:onClick', ImpSwitchKeyDialog.onClick.bindAsEventListener(ImpSwitchKeyDialog));
