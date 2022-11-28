<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m221127_235644_add_title_to_literature migration.
 */
class m221127_235644_add_title_to_literature extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%museumplus_literature}}', 'title')) {
            $this->addColumn('{{%museumplus_literature}}', 'title', $this->text());
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221127_235644_add_title_to_literature cannot be reverted.\n";
        return false;
    }
}
