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
        // Create: imp_smime_privatekeys
        $tableList = $this->tables();
        if (!in_array('imp_smime_privatekeys', $tableList)) {
            $t = $this->createTable('imp_smime_privatekeys', array('autoincrementKey' => 'private_key_id'));
            $t->column('private_key', 'string', array('null' => false));
            $t->end();
        }

        // Create: imp_smime_publickeys
        if (!in_array('imp_smime_publickeys', $tableList)) {
            $t = $this->createTable('imp_smime_publickeys', array('autoincrementKey' => 'public_key_id'));
            $t->column('public_key', 'string', array('null' => false));
            $t->column('private_key_id', 'int');
            $t->end();
        }

        // adding foreign keys, so each public key only has one private key
        // $this->execute(
        //     "ALTER TABLE imp_smime_publickeys
        //     ADD FOREIGN KEY (private_key_id) REFERENCES imp_smime_privatekeys (private_key_id)"
        // );
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->dropTable('imp_smime_privatekeys');
        $this->dropTable('imp_smime_publickeys');
    }

}

