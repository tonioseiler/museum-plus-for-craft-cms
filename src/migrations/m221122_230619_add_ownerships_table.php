<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m221122_230619_add_ownerships_table migration.
 */
class m221122_230619_add_ownerships_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%museumplus_ownerships}}')) {
            $this->createTable('{{%museumplus_ownerships}}', [
                'id' => $this->primaryKey(),
                'title' => $this->text(),
                'data' => $this->longText()->null(),
                'collectionId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);
        }


        if (!$this->db->tableExists('{{%museumplus_items_ownerships}}')) {
            $this->createTable('{{%museumplus_items_ownerships}}', [
                'id' => $this->primaryKey(),
                'itemId' => $this->integer()->notNull(),
                'ownershipId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_ownerships}}', 'ownershipId'),
                '{{%museumplus_items_ownerships}}',
                'ownershipId',
                '{{%museumplus_ownerships}}',
                'id'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_ownerships}}', 'itemId'),
                '{{%museumplus_items_ownerships}}',
                'itemId',
                '{{%museumplus_items}}',
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
        echo "m221122_230619_add_ownerships_table cannot be reverted.\n";
        return false;
    }
}
