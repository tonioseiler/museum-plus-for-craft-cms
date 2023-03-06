<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m230306_121134_add_sensitive_items migration.
 */
class m230306_121134_add_sensitive_items extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%museumplus_items}}', 'sensitive')) {
            $this->addColumn('{{%museumplus_items}}', 'sensitive', $this->boolean());
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230306_121134_add_sensitive_items cannot be reverted.\n";
        return false;
    }
}
