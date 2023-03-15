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

        $ret = [];

        if ($registry->hasMethod('contacts/getField') ||
            $injector->getInstance('Horde_Core_Hooks')->hookExists('smime_key', 'imp')) {
            $ret += [
                self::ENCRYPT => _('S/MIME Encrypt Message'),
            ];
        }

        if ($this->getPersonalPrivateKey()) {
            $ret += [
                self::SIGN => _('S/MIME Sign Message'),
                self::SIGNENC => _('S/MIME Sign/Encrypt Message'),
            ];
        }

        return $ret;
    }

    /**
     * Adds the personal public key to the prefs.
     *
     * @param string|array $key  The public key to add.
     * @param boolean $signkey   The secondary key for signing (optional)
     */
    public function addPersonalPublicKey($key, $signkey = false)
    {
        $prefName = $signkey ? 'smime_public_sign_key' : 'smime_public_key';
        $val = is_array($key) ? implode('', $key) : $key;
        $val = HordeString::convertToUtf8($val);

        $GLOBALS['prefs']->setValue($prefName, $val);
    }

    /**
     * Adds the personal private key to the prefs.
     *
     * @param string|array $key  The private key to add.
     * @param boolean $signkey   Is this the secondary key for signing?
     * @param boolean $calledFromSetSmime to stop unneded notifications
     */
    public function addPersonalPrivateKey($key, $signkey = false, $calledFromSetSmime = false)
    {
        global $prefs;

        $prefName = $signkey ? 'smime_private_sign_key' : 'smime_private_key';
        $val = is_array($key) ? implode('', $key) : $key;
        $val = HordeString::convertToUtf8($val);

        // check if a private key already exists
        $check  = $prefs->getValue('smime_private_key');

        // it there is a private key, these will be unset first and then the new one will be loaded
        if (empty($check)) {
            $GLOBALS['prefs']->setValue($prefName, $val);
        } else {
            $this->unsetSmimePersonal($signkey, $calledFromSetSmime);
            $GLOBALS['prefs']->setValue($prefName, $val);
        }
    }

    /**
     * Adds extra personal keys to the extra keys table.
     *
     * @param string|array $key  The private key to add.
     * @param string|array $key  The public key to add.
     * @param string $password  The password for the private key to add.
     * @param string $pref_name To be removed... TODO.@param string|array
     * @param string $identity The name of the identity to save the keys for
     * @param bool $identity_used Marks the keys as the one that is being used
     */
    public function addExtraPersonalKeys(
        $private_key,
        $public_key,
        $password,
        $pref_name = 'smime_private_key',
        $identity=0,
        $identity_used=false
    ) {
        global $notification;
        /* Get the user_name  */
        // TODO: is there a way to only use prefs?
        $user_name = $GLOBALS['registry']->getAuth();

        // Encrypt the password
        $key = $GLOBALS['conf']['secret_key'];
        $blowfish = new Horde_Crypt_Blowfish($key);
        $encryptedPassword = $blowfish->encrypt($password);
        $encryptedPassword = base64_encode($encryptedPassword);

        // TODO: add check if certificate already exists give warning
        if ($this->privateKeyExists($private_key)) {
            $notification->push(_('Key is allready in the Database'), 'horde.success');
            return false;
        }


        if (!empty($public_key) && !empty($private_key) && !empty($encryptedPassword)) {
            $query = 'INSERT INTO imp_smime_extrakeys (pref_name, user_name, private_key, public_key, privatekey_passwd, identity, identity_used) VALUES (?, ?, ?, ?, ?, ?, ?)';
            $values = [$pref_name, $user_name, $private_key, $public_key, $encryptedPassword, $identity, $identity_used];
            $this->_db->insert($query, $values);
            return true;
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
        $val = HordeString::convertToUtf8($val);
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
     * Retrieves a specific public key from the extrakeys table or throws an exception.
     *
     * @return string  Specific S/MIME private key.
     * @throws Horde_Db_Exception
     *
     * TODO: need to remove the $prefName thingie for extrakeys table, makes no sense
     */
    public function getExtraPublicKey($privateKeyId, $prefName = 'smime_private_key')
    {
        /* Get the user_name  */
        // TODO: is there a way to only use prefs?
        $user_name = $GLOBALS['registry']->getAuth();

        // Build the SQL query
        $query = 'SELECT private_key_id, public_key FROM imp_smime_extrakeys WHERE pref_name=? AND private_key_id=? AND user_name=?';
        $values = [$prefName, $privateKeyId, $user_name];
        // Run the SQL query
        $result = $this->_db->selectOne($query, $values); // returns one key
        return $result['public_key'];
    }

    /**
     * Retrieves a specific private key from the extrakeys table.
     *
     * @return string  Specific S/MIME private key.
     * @throws Horde_Db_Exception
     */
    public function getExtraPrivateKey($id, $prefName = 'smime_private_key')
    {
        /* Get the user_name  */
        // TODO: is there a way to only use prefs?
        // TODO: delete the prefName variable?
        $user_name = $GLOBALS['registry']->getAuth();

        // Build the SQL query
        $query = 'SELECT private_key_id, private_key FROM imp_smime_extrakeys WHERE private_key_id=? AND user_name=?';
        $values = [$id, $user_name];

        // Run the SQL query
        $result = $this->_db->selectOne($query, $values); // returns one key
        return $result['private_key'];
    }

    /**
     * Retrieves the primary key (certificate) that is used by an identity
     *
     * @param string $identity: the identities name
     * @param string $type: public or private key (private is set by default)
     * @return string the privatekey that is used by the identity
     */

    public function getUsedKeyOfIdentity($identity, $pref_name='smime_private_key', $type='private')
    {
        /* Get the user_name  */
        // TODO: is there a way to only use prefs?
        $user_name = $GLOBALS['registry']->getAuth();

        if ($type === 'public') {
            $query = 'SELECT private_key_id, public_key FROM imp_smime_extrakeys WHERE identity=? AND identity_used=true AND user_name=? AND pref_name=?';
        } else {
            $query = 'SELECT private_key_id, private_key FROM imp_smime_extrakeys WHERE identity=? AND identity_used=true AND user_name=? AND pref_name=?';
        }

        $values = [$identity, $user_name, $pref_name];

        // Run the SQL query
        $result = $this->_db->selectOne($query, $values); // returns one key
        return $result['private_key'];
    }

    /**
     * Get private key id of the set Personal Certificate (if it exists in the database)
     *
     * @return int id of extra private certificate in DB
     * @throws Horde_Db_Exception
     */
    public function getSetPrivateKeyId($signkey = self::KEY_PRIMARY)
    {
        {
            /* Get the user_name and personal certificate if existant */
            // TODO: is there a way to only use prefs?
            $user_name = $GLOBALS['registry']->getAuth();
            $personalCertificate = $this->getPersonalPrivateKey($signkey);

            // Build the SQL query
            $query = 'SELECT private_key_id, private_key FROM imp_smime_extrakeys WHERE user_name=?';
            $values = [$user_name];
            // Run the SQL query
            $result = $this->_db->selectAll($query, $values); // returns one key
            if (!empty($result)) {
                // check if privatekeys are the same
                foreach ($result as $key => $value) {
                    if ($value['private_key'] == $personalCertificate) {
                        return $value['private_key_id'];
                    }
                }
            } else {
                return -1;
            }
        }
    }

    /**
     * Check if the private keys allready exist.
     * Example: if the key already exists, there is no need to load it into the database again
     *
     * @return bool if private key is there or not
     * @throws Horde_Db_Exception
     */
    public function privateKeyExists($personalCertificate)
    {
        /* Get the user_name  */
        // TODO: is there a way to only use prefs?
        $user_name = $GLOBALS['registry']->getAuth();

        // Build the SQL query
        $query = 'SELECT private_key FROM imp_smime_extrakeys WHERE user_name=?';
        $values = [$user_name];

        // Run the SQL query
        $result = $this->_db->selectValues($query, $values); // returns an array with keys
        if (!empty($result)) {
            // check if privatekeys are the same
            foreach ($result as $key => $value) {
                if ($value == $personalCertificate || strcmp($value, $personalCertificate) == 0) {
                    return true;
                }
            }
        } else {
            return false;
        }
    }


    /**
     * Retrieves all public and private keys and their aliases from imp_smime_extrakeys table.
     *
     * @return array  All S/MIME private keys available.
     * @throws Horde_Db_Exception
     */
    public function listAllKeys($prefName = 'smime_private_key', $identity = 0)
    {
        /* Get the user_name  */
        // TODO: is there a way to only use prefs?
        $user_name = $GLOBALS['registry']->getAuth();

        // Build the SQL query
        $query = 'SELECT private_key_id, private_key, public_key, alias FROM imp_smime_extrakeys WHERE pref_name=? AND user_name=? AND identity=?';
        $values = [$prefName, $user_name, $identity];

        // Run the SQL query
        $result = $this->_db->selectAll($query, $values); // returns an array with keys
        return $result;
    }

    /**
     * Retrieves all private key ids from imp_smime_extrakeys table.
     *
     * @return array  All S/MIME private keys available.
     * @throws Horde_Db_Exception
     */
    public function listPrivateKeyIds($prefName = 'smime_private_key', $identity = 0)
    {
        /* Get the user_name  */
        // TODO: is there a way to only use prefs?
        $user_name = $GLOBALS['registry']->getAuth();

        // Build the SQL query
        $query = 'SELECT private_key_id FROM imp_smime_extrakeys WHERE pref_name=? AND user_name=? AND identity=?';
        $values = [$prefName, $user_name];
        // Run the SQL query
        $result = $this->_db->selectValues($query, $values, $identity); // returns an array with keys
        return $result;
    }

    /**
     * Setting an alias in the database
     * @return bool|error returns eiter true, false or an error concerning the insertion of an alias in the database
     *
     * @param int $keyid to set the alias to the key with specific id     *
     */
    public function updateAlias($keyid, $alias)
    {
        $query = 'UPDATE imp_smime_extrakeys SET alias = ? WHERE private_key_id = ?';
        $values = [$alias, $keyid];
        $this->_db->insert($query, $values);
    }

    /**
     * Getting an alias from the database
     *
     * @param int $keyid to find the alias belong to the key
     * @return string|bool returns an alias (name) of the certification or false if nothing is returned
     */
    public function getAlias($keyid)
    {
        $query = 'SELECT alias FROM imp_smime_extrakeys WHERE private_key_id=?';
        $values = [$keyid];
        $result = $this->_db->selectValue($query, $values);

        // checking if $result is empty
        if (empty($result)) {
            return false;
        } else {
            return $result;
        }
    }


    /**
     * Setting a new Personal Certificate and belonging Public Certificate:
     * Transfers a Certificate and belonging Public Certificate from the Extra Keys table to Horde_Prefs
     *
     * @param int $keyid returns the key from the keyid
     * @param int $signkey sets a sign key, per default a personal (primary) key is set
     */
    public function setSmimePersonal($keyid, $signkey=self::KEY_PRIMARY)
    {
        if ($signkey == self::KEY_PRIMARY) {
            $prefName = 'smime_private_key';
        } elseif ($signkey == self::KEY_SECONDARY) {
            $prefName = 'smime_private_sign_key';
        }

        // Warns unsetSmime functions that no notifications are needed
        $calledFromSetSmime = true;

        // find the private key that has been selected (NB: do not care if the key is a sign key or not, so no prefname?)
        $newprivatekey = $this->getExtraPrivateKey($keyid);
        $newpublickey = $this->getExtraPublicKey($keyid);

        // check if a personal certificate is set
        $check = null;
        $check = $this->getPersonalPrivateKey();
        $keyExists = $this->privateKeyExists($check);

        // check if there is a personal Certificate set
        if (!empty($check)) {
            // if there is a personal certificate, copy the personal certificate itself or the singkey (depending on wheater it is set) to the database otherwise discontinue the action
            if ($keyExists) { // if the key exists in the database just add (overwrite) the key to the prefs table
                $this->addPersonalPrivateKey($newprivatekey, $signkey, $calledFromSetSmime);
                $this->addPersonalPublicKey($newpublickey, $signkey);
                return;
            }
            // if the key is not in the database, first unset the key (which copies it to the database) and than add (overwrite) the new keys in the prefs table
            // Note $calledFromSetSmime: because setSmimePersonal() adds certifactes from the database, there is no need to check for a correct password, as it should be set in the database already. Setting $calledFromSetSmime = true stopps notifications from poping up.
            // TODO: the singkey stuff is very confusing, needs to be refactored
            if ($this->unsetSmimePersonal($signkey = self::KEY_PRIMARY, $calledFromSetSmime) != false) {
                $this->addPersonalPrivateKey($newprivatekey, $signkey, $calledFromSetSmime);
                $this->addPersonalPublicKey($newpublickey, $signkey);
                return;
            }
            // otherwise do nothing
            return;
        }
        // if not: import it. NOte: if a newly imported but yet non-existant (in the database) key is to be added, $calledFromSetSmime is not set to true, because password checks need to happen
        $this->addPersonalPrivateKey($newprivatekey, $signkey);
        $this->addPersonalPublicKey($newpublickey, $signkey);
    }

    /**
     * Setting a new certificate for signing SMIME mails
     *
     * @param int $keyid to inform which key should be set as a secondary signkey
     */
    public function setSmimeSecondary($keyid)
    {
        $this->setSmimePersonal($keyid, self::KEY_SECONDARY);
    }

    /**
     * Unsetting a Personal Certificate and belonging Public Certificate:
     * Transfers a Personal Certificate and belonging Public Certificate to the Extra Keys table in the DB
     *
     * @param int $singkey defines the key to be processed. Per default it is the personal (primary) key, when e.g. set to self::KEY_SECONDARY the secondary sign key will be processed
     * @param bool $calledFromSetSmime disables notifications for unset passwords: If the function is called from setSmimePersonal there is no reason to check for a password, because the key and the password is set in the database allready.
     */
    public function unsetSmimePersonal($signkey = self::KEY_PRIMARY, $calledFromSetSmime = false)
    {
        global $notification;

        // get current personal certificates
        $privateKey = $this->getPersonalPrivateKey($signkey);
        $publicKey = $this->getPersonalPublicKey($signkey);

        // get password, hash it and save it to the table
        $password = $this->getPassphrase($signkey);

        if ($password == false || is_null($password) || empty($password)) {
            // check if unsetSmimePersonal is called from setSmime, where passwords are set in the DB allready, and there is no need to push any notifications
            if ($calledFromSetSmime == false) {
                $notification->push(
                    _('Please set a correct password before unsetting the keys.'),
                    'horde.error'
                );
            }
            return false;
        }

        // push these to the extra keys table
        if (!empty($privateKey) && !empty($publicKey) && !empty($password)) {
            if ($this->addExtraPersonalKeys($privateKey, $publicKey, $password)) {
                try {
                    $this->deletePersonalKeys($signkey);
                    $notification->push(
                        _('S/MIME Certificate unset and successfully transfered to extra keys.'),
                        'horde.success'
                    );
                    return true;
                } catch (\Throwable $th) {
                    $notification->push(
                        _('S/MIME Certificates were not proberly deleted from database.'),
                        'horde.error'
                    );
                    throw $th;
                }
            } else {
                // unsetting can be done because certificate is in the database anyway
                $this->deletePersonalKeys($signkey);
            }
        }
    }

    /**
     * Unsetting a Certificate for Singing and transerfing it to extra tables
     */
    public function unsetSmimeSecondary()
    {
        $this->unsetSmimePersonal(self::KEY_SECONDARY);
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
     * Deletes the specified extra keys from the extra-keys-table.
     *
     * @param boolean $signkey  Return the secondary key for signing?
     */
    public function deleteExtraKey($private_key_id, $signkey = false)
    {
        /* Build the SQL query. */
        $query = 'DELETE FROM imp_smime_extrakeys WHERE private_key_id = ?';
        $values = [ $private_key_id ];

        $this->_db->delete($query, $values);
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

        [$name, $email] = $this->publicKeyInfo($cert);

        $registry->call(
            'contacts/addField',
            [
                $email,
                $name,
                self::PUBKEY_FIELD,
                $cert,
                $prefs->getValue('add_source'),
            ]
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
            throw new Horde_Crypt_Exception(_('Not a valid public key.'));
        }

        /* Add key to the user's address book. */
        $email = $this->_smime->getEmailFromKey($cert);
        if (is_null($email)) {
            throw new Horde_Crypt_Exception(
                _('No email information located in the public key.')
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

        return [$name, $email];
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
        return [
            'pubkey' => array_map(
                [$this, 'getPublicKey'],
                $addr->bare_addresses
            ),
            'type' => 'message',
        ];
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
                [$address]
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
                [
                    $address,
                    self::PUBKEY_FIELD,
                    $contacts->sources,
                    true,
                    true,
                ]
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
            return [];
        }

        return $registry->call(
            'contacts/getAllAttributeValues',
            [self::PUBKEY_FIELD, $sources]
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
            [
                $email,
                self::PUBKEY_FIELD,
                $injector->getInstance('IMP_Contacts')->sources,
            ]
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
        $additional = [];
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
        return [
            'type' => 'signature',
            'pubkey' => $pubkey,
            'privkey' => $this->getPersonalPrivateKey($secondary),
            'passphrase' => $this->getPassphrase($secondary),
            'sigtype' => 'detach',
            'certs' => implode("\n", $additional),
        ];
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
                ? []
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
    public function decryptMessage($text, $differentKey = null)
    {
        if ($differentKey === null) {
            return $this->_smime->decrypt($text, [
                'type' => 'message',
                'pubkey' => $this->getPersonalPublicKey(),
                'privkey' => $this->getPersonalPrivateKey(),
                'passphrase' => $this->getPassphrase(),
            ]);
        } else {
            return $this->_smime->decrypt($text, [
                'type' => 'message',
                'pubkey' => $this->getExtraPublicKey($differentKey),
                'privkey' => $this->getExtraPrivateKey($differentKey),
                'passphrase' => $this->getPassphrase(null, $differentKey), // create get pasExtraKeyPassphrase()?
            ]);
        }
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
    public function getPassphrase($signkey = self::KEY_PRIMARY, $differentKey = null)
    {
        global $prefs, $session;

        if ($differentKey === null) {
            if ($signkey == self::KEY_SECONDARY_OR_PRIMARY || $signkey == self::KEY_SECONDARY) {
                if ($private_key = $this->getPersonalPrivateKey(self::KEY_SECONDARY)) {
                    $signkey = self::KEY_SECONDARY;
                } else {
                    $private_key = $this->getPersonalPrivateKey();
                    $signkey = self::KEY_PRIMARY;
                }
            } else {
                $private_key = $this->getPersonalPrivateKey($signkey);
            }
        } else {
            // TODO: take care of secondary keys in extratables
            $private_key = $this->getExtraPrivateKey($differentKey);
        }

        if (empty($private_key)) {
            return false;
        }

        if ($differentKey === null) {
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
            $result = $session->get('imp', 'smime_null_passphrase' . $suffix);
        } else {
            // TODO: take care of extra sign keys
            // get passphrase for specific key in the extra tables

            // Build the SQL query
            $query = 'SELECT privatekey_passwd FROM imp_smime_extrakeys WHERE private_key_id=?';
            $values = [$differentKey];
            // Run the SQL query
            $result = $this->_db->selectValue($query, $values);

            # decrypt the hashed value here
            $key = $GLOBALS['conf']['secret_key'];
            $blowfish = new Horde_Crypt_Blowfish($key);
            $result = base64_decode($result);
            $result = $blowfish->decrypt($result);
        }
        return $result;
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
     * TODO: Should keys be added to the extra table per default?
     *
     * @param string $pkcs12    The PKCS 12 data.
     * @param string $password  The password of the PKCS 12 file.
     * @param string $pkpass    The password to use to encrypt the private key.
     * @param boolean $signkey  Is this the secondary key for signing?
     * @param boolean $extrakey Specifies if the key should be added to the extrakeys table
     *
     * @throws Horde_Crypt_Exception
     */
    public function addFromPKCS12(
        $pkcs12,
        $password,
        $pkpass = null,
        $signkey = false,
        $extrakey = false,
        $identity = 0,
        $identity_used = false
    ) {
        global $conf, $notification;

        $sslpath = empty($conf['openssl']['path'])
            ? null
            : $conf['openssl']['path'];

        $params = ['sslpath' => $sslpath, 'password' => $password];
        if (!empty($pkpass)) {
            $params['newpassword'] = $pkpass;
        }

        $result = $this->_smime->parsePKCS12Data($pkcs12, $params);

        if ($extrakey === false) {
            $this->addPersonalPrivateKey($result->private, $signkey);
            $this->addPersonalPublicKey($result->public, $signkey);
            $this->addAdditionalCert($result->certs, $signkey);
        } else {
            // need to add extrakeys here... TODO: add check of key to extraKeys, remove it from set or unsetkeys
            $result = $this->addExtraPersonalKeys($result->private, $result->public, $password, $pref_name = 'smime_private_key', $identity, $identity_used);
            if ($result) {
                $notification->push(_('S/MIME Public/Private Keypair successfully added to exra keys in keystore.'), 'horde.success');
            }
        }
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
