<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m240523_140346_add_extra_fields_to_items_table migration.
 */
class m240523_140346_add_extra_fields_to_items_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%museumplus_items}}', 'extraTitle')) {
            $this->addColumn('{{%museumplus_items}}', 'extraTitle', $this->text()->after('sensitive'));
        }
        if (!$this->db->columnExists('{{%museumplus_items}}', 'extraDescription')) {
            $this->addColumn('{{%items}}', 'extraDescription', $this->longText()->after('extraTitle'));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240523_140346_add_extra_fields_to_items_table cannot be reverted.\n";
        return false;
    }
}
