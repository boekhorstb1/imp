<?php
/**
 * Create IMP SMIME alias in tables.
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
class ImpSmimeAlias extends Horde_Db_Migration_Base
{
    /**
     * Upgrade. NOTE: this upgrade has been written only for testing only for this server!! The release will not contain this migration. All of it will be in in number 4 migration.
     */
    public function up()
    {
        // Create: imp_smime_extrakeys
        $t = $this->_connection->table('imp_smime_extrakeys');
        $cols = $t->getColumns();
        if (!in_array('alias', array_keys($cols))) {
            $this->addColumn('imp_smime_extrakeys', 'alias', 'string', ['limit' => 50,'null' => true]);
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->removeColumn('imp_smime_extrakeys', 'alias');
    }
}
