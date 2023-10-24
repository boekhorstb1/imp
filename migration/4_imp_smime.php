<?php
/**
 * Create IMP SMIME tables.
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class ImpSMIME extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {   
        // Create: imp_smime_extrakeys
        $tableList = $this->tables();
        if (!in_array('imp_smime_extrakeys', $tableList)) {
            $t = $this->createTable('imp_smime_extrakeys', ['autoincrementKey' => 'private_key_id']);
            $t->column('pref_name', 'string', ['limit' => 50, 'null' => false]);
            $t->column('user_name', 'string', ['limit' => 50,'null' => false]);
            $t->column('private_key', 'binary', ['null' => false]);
            $t->column('public_key', 'binary', ['null' => true]);
            $t->column('privatekey_passwd', 'string', ['limit' => 50,'null' => true]);
            $t->column('alias', 'string', ['limit' => 50,'null' => true]);
            $t->column('identity', 'string', ['limit' => 50,'0' => true]);
            $t->column('identity_used', 'bool', ['limit' => 50,'false' => true]); // how to set a default boolean?
            $t->end();
        }
            
        // Check if 'horde_prefs' table exists before trying to access it.
        if (in_array('horde_prefs', $tableList)) {
            // Get all 'smime_private_key' data from 'horde_prefs' using a native SQL query.
            $sql = "SELECT * FROM horde_prefs WHERE pref_name = 'smime_private_key'";
            $smimePrivateKeyData = $this->_connection->selectAll($sql);
    
            // Now, insert the 'smime_private_key' data into 'imp_smime_extrakeys'.
            foreach ($smimePrivateKeyData as $record) {
                $prefUid = $record['pref_uid'];

                // Retrieve the corresponding 'smime_public_key' for the 'pref_uid' using another native SQL query.
                $sql = "SELECT * FROM horde_prefs WHERE pref_name = 'smime_public_key' AND pref_uid = ?";
                $smimePublicKeyRecord = $this->_connection->selectAll($sql, [$prefUid]);

                if ($smimePublicKeyRecord) {
                    $insertSql = "INSERT INTO imp_smime_extrakeys (pref_name, user_name, private_key, public_key, privatekey_passwd, alias, identity, identity_used) 
                                 VALUES (?, ?, ?, ?, NULL, NULL, ?, ?)";
                    $this->_connection->insert($insertSql, [
                        $record['pref_name'],
                        $prefUid,
                        $record['pref_value'],
                        $smimePublicKeyRecord[0]['pref_value'],
                        0, # default identity is 0
                        0, # defautl identitity used is also 0
                    ]);
                }

            }
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->dropTable('imp_smime_extrakeys');
    }
}
