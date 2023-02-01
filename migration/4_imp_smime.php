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
            $t = $this->createTable('imp_smime_extrakeys', array('autoincrementKey' => 'private_key_id'));
            $t->column('pref_name', 'string', array('null' => false));
            $t->column('user_name', 'string', array('null' => false));
            $t->column('private_key', 'longblob', array('null' => false));
            $t->column('public_key', 'longblob', array('null' => true));
            $t->end();
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
