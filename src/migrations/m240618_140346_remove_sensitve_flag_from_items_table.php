<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m240618_140346_remove_sensitve_flag_from_items_table migration.
 */
class m240618_140346_remove_sensitve_flag_from_items_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%museumplus_items}}', 'sensitive')) {
            $this->dropColumn('{{%museumplus_items}}', 'sensitive');
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240618_140346_remove_sensitve_flag_from_items_table cannot be reverted.\n";
        return false;
    }
}
