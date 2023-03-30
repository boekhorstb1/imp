<?php
/**
 * Passes Public Keys to the JS frontend concerning Identityes and Preferencess
 *
 * @author    Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @category  Horde
 * @copyright 2012-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
use Horde_Registry;
use Horde_Injector;

class IMP_Prefs_SwitchHandler
{
    private $injector;
    private $registry;

    public function __construct(Horde_Injector $injector){
        $this->injector = $injector;
        $this->registry = new Horde_Registry();
    }


    /**
     * gets the keys form the address book for the prefs identity frontend
     * 
     * @param integer $identityID: the identity from where the keys should be gotten
     * 
     * @return array returns an array containing links to the keys
     */
    public function getPublicKeysForPrefsIdentities( $identityID){

        $injector = $this->injector;
        $registry = $this->registry;
        
        $identity = $injector->getInstance('IMP_Identity');
        $email = $identity->getEmail($identityID);
        $name = $identity->getName($identityID);

        $smime_url = IMP_Basic_Smime::url();
        $self_url = Horde::url($registry->getServiceLink('prefs', 'imp'), false);

        try {
            $linksToKey = [
                'view' => Horde::link($smime_url->copy()->add(['actionID' => 'view_public_key', 'email' => $email]), sprintf(_('View %s Public Key'), $name), null, 'view_key'),
                'info' => Horde::link($smime_url->copy()->add(['actionID' => 'info_public_key', 'email' => $email]), sprintf(_('View %s Public Info'), $name), null, 'info_key'),
                ];
        } catch (\Throwable $th) {
            throw $th;
        }


        return $linksToKey;

    }
}