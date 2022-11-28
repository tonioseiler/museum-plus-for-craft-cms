<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m221127_211317_add_title_to_people migration.
 */
class m221127_211317_add_title_to_people extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%museumplus_people}}', 'title')) {
            $this->addColumn('{{%museumplus_people}}', 'title', $this->text());
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221127_211317_add_title_to_people cannot be reverted.\n";
        return false;
    }
}
