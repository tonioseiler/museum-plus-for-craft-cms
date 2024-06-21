<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m240621_150823_add_sort_columns_to_intermediat_tables migration.
 */
class m240621_150823_add_sort_columns_to_intermediat_tables extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%museumplus_items_items}}', 'sort')) {
            $this->addColumn('{{%museumplus_items_items}}', 'sort', $this->integer()->defaultValue(0));
        }
        if (!$this->db->columnExists('{{%museumplus_items_literature}}', 'sort')) {
            $this->addColumn('{{%museumplus_items_literature}}', 'sort', $this->integer()->defaultValue(0));
        }
        if (!$this->db->columnExists('{{%museumplus_items_objectgroups}}', 'sort')) {
            $this->addColumn('{{%museumplus_items_objectgroups}}', 'sort', $this->integer()->defaultValue(0));
        }
        if (!$this->db->columnExists('{{%museumplus_items_ownerships}}', 'sort')) {
            $this->addColumn('{{%museumplus_items_ownerships}}', 'sort', $this->integer()->defaultValue(0));
        }
        if (!$this->db->columnExists('{{%museumplus_items_people}}', 'sort')) {
            $this->addColumn('{{%museumplus_items_people}}', 'sort', $this->integer()->defaultValue(0));
        }
        if (!$this->db->columnExists('{{%museumplus_items_vocabulary}}', 'sort')) {
            $this->addColumn('{{%museumplus_items_vocabulary}}', 'sort', $this->integer()->defaultValue(0));
        }
        if (!$this->db->columnExists('{{%museumplus_items_assets}}', 'sort')) {
            $this->addColumn('{{%museumplus_items_assets}}', 'sort', $this->integer()->defaultValue(0));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240621_150823_add_sort_columns_to_intermediat_tables cannot be reverted.\n";
        return false;
    }
}
