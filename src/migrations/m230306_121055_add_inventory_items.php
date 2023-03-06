<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m230306_121055_add_inventory_items migration.
 */
class m230306_121055_add_inventory_items extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%museumplus_items}}', 'inventoryNumber')) {
            $this->addColumn('{{%museumplus_items}}', 'inventoryNumber', $this->string());
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230306_121055_add_inventory_items cannot be reverted.\n";
        return false;
    }
}
