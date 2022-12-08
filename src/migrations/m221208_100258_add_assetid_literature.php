<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m221208_100258_add_assetid_literature migration.
 */
class m221208_100258_add_assetid_literature extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%museumplus_literature}}', 'assetId')) {
            $this->addColumn('{{%museumplus_literature}}', 'assetId', $this->integer());

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_literature}}', 'assetId'),
                '{{%museumplus_literature}}',
                'assetId',
                '{{%assets}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221208_100258_add_assetid_literature cannot be reverted.\n";
        return false;
    }
}
