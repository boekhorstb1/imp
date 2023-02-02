<?php
/**
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

use Horde\Util\HordeString;

/**
 * Contains code related to handling S/MIME messages within IMP.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Smime
{
    /* Name of the S/MIME public key field in addressbook. */
    public const PUBKEY_FIELD = 'smimePublicKey';

    /* Encryption type constants. */
    public const ENCRYPT = 'smime_encrypt';
    public const SIGN = 'smime_sign';
    public const SIGNENC = 'smime_signenc';

    /* Which key to use. */
    public const KEY_PRIMARY = 0;
    public const KEY_SECONDARY = 1;
    public const KEY_SECONDARY_OR_PRIMARY = 2;

    /**
     * S/MIME object.
     *
     * @var Horde_Crypt_Smime
     */
    protected $_smime;

    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Return whether PGP support is current enabled in IMP.
     *
     * @return boolean  True if PGP support is enabled.
     */
    public static function enabled()
    {
        global $conf, $prefs;

        return (!empty($conf['openssl']['path']) &&
                $prefs->getValue('use_smime') &&
                Horde_Util::extensionExists('openssl'));
    }

    /**
     * Constructor.
     *
     * @param Horde_Crypt_Smime $pgp  S/MIME object.
     */
    public function __construct(Horde_Crypt_Smime $smime, $db)
    {
        $this->_smime = $smime;
        $this->_db = $db;
    }

    /**
     * Returns the list of available encryption options for composing.
     *
     * @return array  Keys are encryption type constants, values are gettext
     *                strings describing the encryption type.
     */
    public function encryptList()
    {
        global $injector, $registry;

        $ret = array();

        if ($registry->hasMethod('contacts/getField') ||
            $injector->getInstance('Horde_Core_Hooks')->hookExists('smime_key', 'imp')) {
            $ret += array(
                self::ENCRYPT => _("S/MIME Encrypt Message")
            );
        }

        if ($this->getPersonalPrivateKey()) {
            $ret += array(
                self::SIGN => _("S/MIME Sign Message"),
                self::SIGNENC => _("S/MIME Sign/Encrypt Message")
            );
        }

        return $ret;
    }

    /**
     * Adds the personal public key to the prefs.
     *
     * @param string|array $key  The public key to add.
     * @param boolean $signkey   Is this the secondary key for signing?
     */
    public function addPersonalPublicKey($key, $signkey = false)
    {
        $prefName = $signkey ? 'smime_public_sign_key' : 'smime_public_key';
        $val = is_array($key) ? implode('', $key) : $key;
        try {
            $val = HordeString::convertToUtf8($val);
        } catch (Exception $ex) {
        }
        $GLOBALS['prefs']->setValue($prefName, $val);
    }

    /**
     * Adds the personal private key to the prefs.
     *
     * @param string|array $key  The private key to add.
     * @param boolean $signkey   Is this the secondary key for signing?
     */
    public function addPersonalPrivateKey($key, $signkey = false)
    {
        global $prefs;

        $prefName = $signkey ? 'smime_private_sign_key' : 'smime_private_key';
        $val = is_array($key) ? implode('', $key) : $key;
        try {
            $val = HordeString::convertToUtf8($val);
        } catch (Exception $ex) {
        }

        // check if a private key already exists
        $check  = $prefs->getValue('smime_private_key');
        // if there is no private key, give it the default $prefName, else add an unique number to the name?
        if (empty($check)) {
            $GLOBALS['prefs']->setValue($prefName, $val);
        } else {
            // setValue in a way that it is retrievable by id:
            // added an extra table for that, because horde_prefs has a combined pk which is not autoincrementing and need a way to identify each key easily:
            // - see: migrations/4_imp_smime.php
            //
            // Now: check if database table and entries allready exist for user
            // NOTE:one can only create unique users in Horde, so it has a unique value!
            $this->addExtraPersonalPrivateKey($prefName, $val);
        }
    }

    /**
     * Adds am extra personal public key to the extra keys table.
     *
     * @param string|array $key  The public key to add.
     * @param int $private_key_id   PrivateKeyId to add the Public key to.
     */
    public function addExtraPersonalPublicKey($pref_name = 'smime_private_key', $public_key, $private_key_id)
    {
        /* Build the SQL query. */
        $query = 'INSERT INTO imp_smime_extrakeys (pref_name, public_key) VALUES (?, ?) WHERE private_key_id = ?';
        $values = array(
           $pref_name, $public_key, $private_key_id
       );

        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            dd($e);
            return $e;
        }
    }

    /**
     * Adds am extra personal private key to the extra keys table.
     *
     * @param string $pref_name To be removed... TODO.
     * @param string|array $key  The private key to add.
     * @param string|array $key  The public key to add (optional).
     */
    public function addExtraPersonalPrivateKey($pref_name = 'smime_private_key', $private_key, $public_key = null)
    {
        /* Get the user_name  */
        // TODO: is there a way to only use prefs?
        $user_name = $GLOBALS['registry']->getAuth();

        if ($public_key !== null) {
            /* Build the SQL query. */
            $query = 'INSERT INTO imp_smime_extrakeys (pref_name, user_name, private_key, public_key) VALUES (?, ?, ?, ?)';
            $values = [$pref_name, $user_name, $private_key, $public_key];
        } else {
            /* Build the SQL query. */
            $query = 'INSERT INTO imp_smime_extrakeys (pref_name, user_name, private_key) VALUES (?, ?, ?)';
            $values = [$pref_name, $user_name, $private_key];
        }

        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            dd($e);
            return $e;
        }
    }

    /**
     * Adds a list of additional certs to the prefs.
     *
     * @param string|array $key  The additional certifcate(s) to add.
     * @param boolean $signkey   Is this the secondary key for signing?
     */
    public function addAdditionalCert($key, $signkey = false)
    {
        $prefName = $signkey ? 'smime_additional_sign_cert' : 'smime_additional_cert';
        $val = is_array($key) ? implode('', $key) : $key;
        try {
            $val = HordeString::convertToUtf8($val);
        } catch (Exception $ex) {
        }
        $GLOBALS['prefs']->setValue($prefName, $val);
    }

    /**
     * Returns the personal public key from the prefs.
     *
     * @param integer $signkey  One of the IMP_Sime::KEY_* constants.
     *
     * @return string  The personal S/MIME public key.
     */
    public function getPersonalPublicKey($signkey = self::KEY_PRIMARY)
    {
        global $prefs;

        $key = $prefs->getValue(
            $signkey ? 'smime_public_sign_key' : 'smime_public_key'
        );
        if (!$key && $signkey == self::KEY_SECONDARY_OR_PRIMARY) {
            $key = $prefs->getValue('smime_public_key');
        }

        return $key;
    }

    /**
     * Returns the personal private key from the prefs.
     *
     * @param integer $signkey  One of the IMP_Sime::KEY_* constants.
     *
     * @return string  The personal S/MIME private key.
     */
    public function getPersonalPrivateKey($signkey = self::KEY_PRIMARY)
    {
        global $prefs;

        $prefName = $signkey ? 'smime_private_sign_key' : 'smime_private_key';
        $key = $prefs->getValue(
            $prefName
        );

        if (!$key && $signkey == self::KEY_SECONDARY_OR_PRIMARY) {
            $key = $prefs->getValue('smime_private_key');
        }

        return $key;
    }

    /**
     * Retrieves a specific public key from the extrakeys table.
     *
     * @return string  Specific S/MIME private key.
     * @throws Horde_Db_Exception
     *
     * TODO: need to remove the $prefName thingie for extrakeys table, makes no sense
     */
    public function getExtraPublicKey($prefName = 'smime_private_key', $privateKeyId)
    {
        /* Get the user_name  */
        // TODO: is there a way to only use prefs?
        $user_name = $GLOBALS['registry']->getAuth();

        // Build the SQL query
        $query = 'SELECT private_key_id, public_key FROM imp_smime_extrakeys WHERE pref_name=? AND private_key_id=? AND user_name=?';
        $values = [$prefName, $privateKeyId, $user_name];
        // Run the SQL query
        try {
            $result = $this->_db->selectOne($query, $values); // returns one key
            return $result['public_key'];
        } catch (Horde_Db_Exception $e) {
            dd($e);
            return $e;
        }
    }

    /**
     * Retrieves a specific private key from the extrakeys table.
     *
     * @return string  Specific S/MIME private key.
     * @throws Horde_Db_Exception
     */
    public function getExtraPrivateKey($prefName = 'smime_private_key', $id)
    {
        /* Get the user_name  */
        // TODO: is there a way to only use prefs?
        $user_name = $GLOBALS['registry']->getAuth();

        // Build the SQL query
        $query = 'SELECT private_key_id, private_key FROM imp_smime_extrakeys WHERE pref_name=? AND private_key_id=? AND user_name=?';
        $values = [$prefName, $id, $user_name];
        // Run the SQL query
        try {
            $result = $this->_db->selectOne($query, $values); // returns one key
            return $result['private_key'];
        } catch (Horde_Db_Exception $e) {
            dd($e);
            return $e;
        }
    }

    /**
     * check private keys allready exist
     *
     * @return bool if private key is there or not
     * @throws Horde_Db_Exception
     */
    public function checkPrivateKey($key)
    {
        /* Get the user_name  */
        // TODO: is there a way to only use prefs?
        $user_name = $GLOBALS['registry']->getAuth();
        
        // Build the SQL query
        $query = 'SELECT private_key FROM imp_smime_extrakeys WHERE user_name=? AND private_key=?';
        $values = [$user_name, $key];        
        // Run the SQL query
        try {
            $result = $this->_db->selectAll($query, $values); // returns an array with keys
            if(empty($result)){
                return false;
            }
            else {
                return true;
            }
        } catch (Horde_Db_Exception $e) {
            dd($e);
            return $e;
        }
    }


    /**
     * Retrieves all private keys from imp_smime_extrakeys table.
     *
     * @return array  All S/MIME private keys available.
     * @throws Horde_Db_Exception
     */
    public function listPrivateKeys($prefName = 'smime_private_key')
    {
        /* Get the user_name  */
        // TODO: is there a way to only use prefs?
        $user_name = $GLOBALS['registry']->getAuth();
        
        // Build the SQL query
        $query = 'SELECT private_key_id, private_key FROM imp_smime_extrakeys WHERE pref_name=? AND user_name=?';
        $values = [$prefName, $user_name];        
        // Run the SQL query
        try {
            $result = $this->_db->selectAll($query, $values); // returns an array with keys
            return $result;
        } catch (Horde_Db_Exception $e) {
            dd($e);
            return $e;
        }
    }


    /**
     * Setting a new Personal Certificate and belonging Public Certificate:
     * Transfers a Certificate and belonging Public Certificate from the Extra Keys table to Horde_Prefs
     *
     */
    public function setSmimePersonal($key)
    {
        // find the private key that has been selected
        $newprivatekey = $this->getExtraPrivateKey('smime_private_key', $key);
        $newpublickey = $this->getExtraPublicKey('smime_private_key', $key);
        // TODO: find the public key that belongs to this private key

        // check if a personal certificate is set
        $check = null;
        try {
            $check = $this->getPersonalPrivateKey();
        } catch (\Throwable $th) {
            dd($th);
        }

        if (!empty($check)) {
            // if there is, copy it to the database
            $this->unsetSmimePersonal;
        }

        // if not: import it
        // TODO: $signkey?
        if (!empty($newprivatekey) && !empty($newpublickey)) {
            $this->addPersonalPrivateKey($newprivatekey);
            $this->addPersonalPublicKey($newpublickey);
        }
    }

    /**
     * Unsetting a Personal Certificate and belonging Public Certificate:
     * Transfers a Personal Certificate and belonging Public Certificate to the Extra Keys table in the DB
     *
     */
    public function unsetSmimePersonal()
    {
        // get current personal certificates
        $PrivateKey = $this->getPersonalPrivateKey();
        $PublicKey = $this->getPersonalPublicKey();

        // push these to the extra keys table
        if (!empty($PrivateKey) && !empty($PublicKey)) {
            if(!$this->checkPrivateKey($PrivateKey))
            {
                $this->addExtraPersonalPrivateKey('smime_private_key', $PrivateKey, $PublicKey);
            }            
        }

        // remove the current Personal Keys
        $this->deletePersonalKeys();
    }

    /**
     * Returns any additional certificates from the prefs.
     *
     * @param integer $signkey  One of the IMP_Sime::KEY_* constants.
     *
     * @return string  Additional signing certs for inclusion.
     */
    public function getAdditionalCert($signkey = self::KEY_PRIMARY)
    {
        global $prefs;

        $key = $prefs->getValue(
            $signkey ? 'smime_additional_sign_cert' : 'smime_additional_cert'
        );
        if (!$key && $signkey == self::KEY_SECONDARY_OR_PRIMARY) {
            $key = $prefs->getValue('smime_additional_cert');
        }

        return $key;
    }

    /**
     * Deletes the specified personal keys from the prefs.
     *
     * @param boolean $signkey  Return the secondary key for signing?
     */
    public function deletePersonalKeys($signkey = false)
    {
        global $prefs;

        // We always delete the secondary keys because we cannot have them
        // without primary keys.
        $prefs->setValue('smime_public_sign_key', '');
        $prefs->setValue('smime_private_sign_key', '');
        $prefs->setValue('smime_additional_sign_cert', '');
        if (!$signkey) {
            $prefs->setValue('smime_public_key', '');
            $prefs->setValue('smime_private_key', '');
            $prefs->setValue('smime_additional_cert', '');
        }
        $this->unsetPassphrase($signkey);
    }

    /**
     * Adds a public key to an address book.
     *
     * @param string $cert  A public certificate to add.
     *
     * @throws Horde_Exception
     */
    public function addPublicKey($cert)
    {
        global $prefs, $registry;

        list($name, $email) = $this->publicKeyInfo($cert);

        $registry->call(
            'contacts/addField',
            array(
                $email,
                $name,
                self::PUBKEY_FIELD,
                $cert,
                $prefs->getValue('add_source')
            )
        );
    }

    /**
     * Returns information about a public certificate.
     *
     * @param string $cert  The public certificate.
     *
     * @return array  Two element array: the name and e-mail for the cert.
     * @throws Horde_Crypt_Exception
     */
    public function publicKeyInfo($cert)
    {
        /* Make sure the certificate is valid. */
        $key_info = openssl_x509_parse($cert);
        if (!is_array($key_info) || !isset($key_info['subject'])) {
            throw new Horde_Crypt_Exception(_("Not a valid public key."));
        }

        /* Add key to the user's address book. */
        $email = $this->_smime->getEmailFromKey($cert);
        if (is_null($email)) {
            throw new Horde_Crypt_Exception(
                _("No email information located in the public key.")
            );
        }

        /* Get the name corresponding to this key. */
        if (isset($key_info['subject']['CN'])) {
            $name = $key_info['subject']['CN'];
        } elseif (isset($key_info['subject']['OU'])) {
            $name = $key_info['subject']['OU'];
        } else {
            $name = $email;
        }

        return array($name, $email);
    }

    /**
     * Returns the params needed to encrypt a message being sent to the
     * specified email address(es).
     *
     * @param Horde_Mail_Rfc822_List $addr  The recipient addresses.
     *
     * @return array  The list of parameters needed by encrypt().
     * @throws Horde_Crypt_Exception
     */
    protected function _encryptParameters(Horde_Mail_Rfc822_List $addr)
    {
        return array(
            'pubkey' => array_map(
                array($this, 'getPublicKey'),
                $addr->bare_addresses
            ),
            'type' => 'message'
        );
    }

    /**
     * Retrieves a public key by e-mail.
     *
     * The key will be retrieved from a user's address book(s).
     *
     * @param string $address  The e-mail address to search for.
     *
     * @return string  The S/MIME public key requested.
     * @throws Horde_Exception
     */
    public function getPublicKey($address)
    {
        global $injector, $registry;

        try {
            $key = $injector->getInstance('Horde_Core_Hooks')->callHook(
                'smime_key',
                'imp',
                array($address)
            );
            if ($key) {
                return $key;
            }
        } catch (Horde_Exception_HookNotSet $e) {
        }

        $contacts = $injector->getInstance('IMP_Contacts');

        try {
            $key = $registry->call(
                'contacts/getField',
                array(
                    $address,
                    self::PUBKEY_FIELD,
                    $contacts->sources,
                    true,
                    true
                )
            );
        } catch (Horde_Exception $e) {
            /* See if the address points to the user's public key. */
            $personal_pubkey = $this->getPersonalPublicKey();
            if (!empty($personal_pubkey) &&
                $injector->getInstance('IMP_Identity')->hasAddress($address)) {
                return $personal_pubkey;
            }

            throw $e;
        }

        /* If more than one public key is returned, just return the first in
         * the array. There is no way of knowing which is the "preferred" key,
         * if the keys are different. */
        return is_array($key) ? reset($key) : $key;
    }

    /**
     * Retrieves all public keys from a user's address book(s).
     *
     * @return array  All S/MIME public keys available.
     * @throws Horde_Crypt_Exception
     */
    public function listPublicKeys()
    {
        global $injector, $registry;

        $sources = $injector->getInstance('IMP_Contacts')->sources;

        if (empty($sources)) {
            return array();
        }

        return $registry->call(
            'contacts/getAllAttributeValues',
            array(self::PUBKEY_FIELD, $sources)
        );
    }

    /**
     * Deletes a public key from a user's address book(s) by e-mail.
     *
     * @param string $email  The e-mail address to delete.
     *
     * @throws Horde_Crypt_Exception
     */
    public function deletePublicKey($email)
    {
        global $injector, $registry;

        $registry->call(
            'contacts/deleteField',
            array(
                $email,
                self::PUBKEY_FIELD,
                $injector->getInstance('IMP_Contacts')->sources
            )
        );
    }

    /**
     * Returns the parameters needed for signing a message.
     *
     * @return array  The list of parameters needed by encrypt().
     */
    protected function _signParameters()
    {
        $pubkey = $this->getPersonalPublicKey(true);
        $additional = array();
        if ($pubkey) {
            $additional[] = $this->getPersonalPublicKey();
            $secondary = true;
        } else {
            $pubkey = $this->getPersonalPublicKey();
            $secondary = false;
        }
        $additional[] = $this->getAdditionalCert($secondary);
        if ($secondary) {
            $additional[] = $this->getAdditionalCert();
        }
        return array(
            'type' => 'signature',
            'pubkey' => $pubkey,
            'privkey' => $this->getPersonalPrivateKey($secondary),
            'passphrase' => $this->getPassphrase($secondary),
            'sigtype' => 'detach',
            'certs' => implode("\n", $additional),
        );
    }

    /**
     * Verifies a signed message with a given public key.
     *
     * @param string $text  The text to verify.
     *
     * @return stdClass  See Horde_Crypt_Smime::verify().
     * @throws Horde_Crypt_Exception
     */
    public function verifySignature($text)
    {
        global $conf;

        return $this->_smime->verify(
            $text,
            empty($conf['openssl']['cafile'])
                ? array()
                : $conf['openssl']['cafile']
        );
    }

    /**
     * Decrypts a message with user's public/private keypair.
     *
     * @param string $text  The text to decrypt.
     *
     * @return string  See Horde_Crypt_Smime::decrypt().
     * @throws Horde_Crypt_Exception
     */
    public function decryptMessage($text)
    {
        return $this->_smime->decrypt($text, array(
            'type' => 'message',
            'pubkey' => $this->getPersonalPublicKey(),
            'privkey' => $this->getPersonalPrivateKey(),
            'passphrase' => $this->getPassphrase()
        ));
    }

    /**
     * Returns the user's passphrase from the session cache.
     *
     * @param integer $signkey  One of the IMP_Sime::KEY_* constants.
     *
     * @return mixed  The passphrase, if set.  Returns false if the passphrase
     *                has not been loaded yet.  Returns null if no passphrase
     *                is needed.
     */
    public function getPassphrase($signkey = self::KEY_PRIMARY)
    {
        global $prefs, $session;

        if ($signkey == self::KEY_SECONDARY_OR_PRIMARY) {
            if ($private_key = $this->getPersonalPrivateKey(self::KEY_SECONDARY)) {
                $signkey = self::KEY_SECONDARY;
            } else {
                $private_key = $this->getPersonalPrivateKey();
                $signkey = self::KEY_PRIMARY;
            }
        } else {
            $private_key = $this->getPersonalPrivateKey($signkey);
        }

        if (empty($private_key)) {
            return false;
        }

        $suffix = $signkey ? '_sign' : '';
        if ($session->exists('imp', 'smime_passphrase' . $suffix)) {
            return $session->get('imp', 'smime_passphrase' . $suffix);
        }

        if (!$session->exists('imp', 'smime_null_passphrase' . $suffix)) {
            $session->set(
                'imp',
                'smime_null_passphrase' . $suffix,
                $this->_smime->verifyPassphrase($private_key, null)
                    ? null
                    : false
            );
        }

        return $session->get('imp', 'smime_null_passphrase' . $suffix);
    }

    /**
     * Stores the user's passphrase in the session cache.
     *
     * @param string $passphrase  The user's passphrase.
     * @param integer $signkey    One of the IMP_Sime::KEY_* constants.
     *
     * @return boolean  Returns true if correct passphrase, false if incorrect.
     */
    public function storePassphrase($passphrase, $signkey = self::KEY_PRIMARY)
    {
        global $session;

        if ($signkey == self::KEY_SECONDARY_OR_PRIMARY) {
            if ($key = $this->getPersonalPrivateKey(self::KEY_SECONDARY)) {
                $signkey = self::KEY_SECONDARY;
            } else {
                $key = $this->getPersonalPrivateKey();
                $signkey = self::KEY_PRIMARY;
            }
        } else {
            $key = $this->getPersonalPrivateKey($signkey);
        }
        if ($this->_smime->verifyPassphrase($key, $passphrase) !== false) {
            $session->set(
                'imp',
                $signkey ? 'smime_passphrase_sign' : 'smime_passphrase',
                $passphrase,
                $session::ENCRYPT
            );
            return true;
        }

        return false;
    }

    /**
     * Clears the passphrase from the session cache.
     *
     * @param boolean $signkey    Is this the secondary key for signing?
     */
    public function unsetPassphrase($signkey = false)
    {
        global $session;

        if ($signkey) {
            $session->remove('imp', 'smime_null_passphrase_sign');
            $session->remove('imp', 'smime_passphrase_sign');
        } else {
            $session->remove('imp', 'smime_null_passphrase');
            $session->remove('imp', 'smime_passphrase');
        }
    }

    /**
     * Encrypts a MIME part using S/MIME using IMP defaults.
     *
     * @param Horde_Mime_Part $mime_part     The object to encrypt.
     * @param Horde_Mail_Rfc822_List $recip  The recipient address(es).
     *
     * @return Horde_Mime_Part  See Horde_Crypt_Smime::encryptMIMEPart().
     * @throws Horde_Crypt_Exception
     */
    public function encryptMimePart(
        $mime_part,
        Horde_Mail_Rfc822_List $recip
    ) {
        return $this->_smime->encryptMIMEPart(
            $mime_part,
            $this->_encryptParameters($recip)
        );
    }

    /**
     * Signs a MIME part using S/MIME using IMP defaults.
     *
     * @param MIME_Part $mime_part  The MIME_Part object to sign.
     *
     * @return Horde_Mime_Part  See Horde_Crypt_Smime::signMIMEPart().
     * @throws Horde_Crypt_Exception
     */
    public function signMimePart($mime_part)
    {
        return $this->_smime->signMIMEPart(
            $mime_part,
            $this->_signParameters()
        );
    }

    /**
     * Signs and encrypts a MIME part using S/MIME using IMP defaults.
     *
     * @param Horde_Mime_Part $mime_part     The object to sign and encrypt.
     * @param Horde_Mail_Rfc822_List $recip  The recipient address(es).
     *
     * @return Horde_Mime_Part  See
     *                          Horde_Crypt_Smime::signAndencryptMIMEPart().
     * @throws Horde_Crypt_Exception
     */
    public function signAndEncryptMimePart(
        $mime_part,
        Horde_Mail_Rfc822_List $recip
    ) {
        return $this->_smime->signAndEncryptMIMEPart(
            $mime_part,
            $this->_signParameters(),
            $this->_encryptParameters($recip)
        );
    }

    /**
     * Stores the public/private/additional certificates in the preferences
     * from a given PKCS 12 file.
     *
     * @param string $pkcs12    The PKCS 12 data.
     * @param string $password  The password of the PKCS 12 file.
     * @param string $pkpass    The password to use to encrypt the private key.
     * @param boolean $signkey  Is this the secondary key for signing?
     *
     * @throws Horde_Crypt_Exception
     */
    public function addFromPKCS12(
        $pkcs12,
        $password,
        $pkpass = null,
        $signkey = false
    ) {
        global $conf;

        $sslpath = empty($conf['openssl']['path'])
            ? null
            : $conf['openssl']['path'];

        $params = array('sslpath' => $sslpath, 'password' => $password);
        if (!empty($pkpass)) {
            $params['newpassword'] = $pkpass;
        }

        $result = $this->_smime->parsePKCS12Data($pkcs12, $params);
        $this->addPersonalPrivateKey($result->private, $signkey);
        $this->addPersonalPublicKey($result->public, $signkey);
        $this->addAdditionalCert($result->certs, $signkey);
    }

    /**
     * Extracts the contents from signed S/MIME data.
     *
     * @param string $data  The signed S/MIME data.
     *
     * @return string  The contents embedded in the signed data.
     * @throws Horde_Crypt_Exception
     */
    public function extractSignedContents($data)
    {
        global $conf;

        $sslpath = empty($conf['openssl']['path'])
            ? null
            : $conf['openssl']['path'];

        return $this->_smime->extractSignedContents($data, $sslpath);
    }

    /**
     * Checks for the presence of the OpenSSL extension to PHP.
     *
     * @throws Horde_Crypt_Exception
     */
    public function checkForOpenSsl()
    {
        $this->_smime->checkForOpenSSL();
    }

    /**
     * Converts a PEM format certificate to readable HTML version.
     *
     * @param string $cert  PEM format certificate.
     *
     * @return string  HTML detailing the certificate.
     */
    public function certToHTML($cert)
    {
        return $this->_smime->certToHTML($cert);
    }

    /**
     * Extracts the contents of a PEM format certificate to an array.
     *
     * @param string $cert  PEM format certificate.
     *
     * @return array  All extractable information about the certificate.
     */
    public function parseCert($cert)
    {
        return $this->_smime->parseCert($cert);
    }
}
