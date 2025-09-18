<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m250918_082402_add_people_assets migration.
 */
class m250918_082402_add_people_assets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%museumplus_people_assets}}')) {
            $this->createTable('{{%museumplus_people_assets}}', [
                'id' => $this->primaryKey(),
                'peopleId' => $this->integer()->notNull(),
                'assetId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_people_assets}}', 'assetId'),
                '{{%museumplus_people_assets}}',
                'assetId',
                '{{%assets}}',
                'id',
                'CASCADE',
                'CASCADE'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_people_assets}}', 'fileId'),
                '{{%museumplus_people_assets}}',
                'peopleId',
                '{{%museumplus_people}}',
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
        echo "m250918_082402_add_people_assets cannot be reverted.\n";
        return false;
    }
}
