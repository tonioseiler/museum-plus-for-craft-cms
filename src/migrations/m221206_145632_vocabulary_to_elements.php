<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m221206_145632_vocabulary_to_elements migration.
 */
class m221206_145632_vocabulary_to_elements extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->db->tableExists('{{%museumplus_vocabulary}}')) {
            if ($this->db->columnExists('{{%museumplus_vocabulary}}', 'title')) {
                $this->dropColumn('{{%museumplus_vocabulary}}', 'title');
            }
            $this->addForeignKey(
                $this->db->getForeignKeyName(),
                '{{%museumplus_vocabulary}}',
                'id',
                '{{%elements}}',
                'id',
                'CASCADE',
                null
            );
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        return true;
    }
}
