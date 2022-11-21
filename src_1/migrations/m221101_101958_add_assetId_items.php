<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m221101_101958_add_assetId_items migration.
 */
class m221101_101958_add_assetId_items extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%museumplus_items}}', 'assetId')) {
            $this->addColumn('{{%museumplus_items}}', 'assetId', $this->integer());

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items}}', 'assetId'),
                '{{%museumplus_items}}',
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
        echo "m221101_101958_add_assetId_items cannot be reverted.\n";
        return false;
    }
}
