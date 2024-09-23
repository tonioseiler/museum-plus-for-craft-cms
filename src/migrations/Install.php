<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%museumplus_items}}')) {
            // create the items table
            $this->createTable('{{%museumplus_items}}', [
                'id' => $this->primaryKey(),
                'data' => $this->longText()->null(),
                'collectionId' => $this->integer()->notNull(),
                'parentId' => $this->integer()->notNull()->defaultValue(0),
                'inventoryNumber' => $this->string(),
                'extraTitle' => $this->text(),
                'extraDescription' => $this->longText(),
                'sort' => $this->string(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);

            // give it a foreign key to the elements table
            $this->addForeignKey(
                $this->db->getForeignKeyName(),
                '{{%museumplus_items}}',
                'id',
                '{{%elements}}',
                'id',
                'CASCADE',
                null
            );
        }

        if (!$this->db->tableExists('{{%museumplus_objectgroups}}')) {
            $this->createTable('{{%museumplus_objectgroups}}', [
                'id' => $this->primaryKey(),
                'title' => $this->text(),
                'data' => $this->longText()->null(),
                'collectionId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);
        }

        if (!$this->db->tableExists('{{%museumplus_items_objectgroups}}')) {
            $this->createTable('{{%museumplus_items_objectgroups}}', [
                'id' => $this->primaryKey(),
                'itemId' => $this->integer()->notNull(),
                'objectGroupId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'sort' => $this->integer()->defaultValue(0)
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_objectgroups}}', 'objectGroupId'),
                '{{%museumplus_items_objectgroups}}',
                'objectGroupId',
                '{{%museumplus_objectgroups}}',
                'id'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_objectgroups}}', 'itemId'),
                '{{%museumplus_items_objectgroups}}',
                'itemId',
                '{{%museumplus_items}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if (!$this->db->tableExists('{{%museumplus_items_items}}')) {
            $this->createTable('{{%museumplus_items_items}}', [
                'id' => $this->primaryKey(),
                'itemId' => $this->integer()->notNull(),
                'relatedItemId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'sort' => $this->integer()->defaultValue(0)
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_items}}', 'relatedItemId'),
                '{{%museumplus_items_items}}',
                'relatedItemId',
                '{{%museumplus_items}}',
                'id'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_items}}', 'itemId'),
                '{{%museumplus_items_items}}',
                'itemId',
                '{{%museumplus_items}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if (!$this->db->tableExists('{{%museumplus_literature}}')) {
            $this->createTable('{{%museumplus_literature}}', [
                'id' => $this->primaryKey(),
                'title' => $this->text(),
                'data' => $this->longText()->null(),
                'collectionId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);
        }


        if (!$this->db->tableExists('{{%museumplus_items_literature}}')) {
            $this->createTable('{{%museumplus_items_literature}}', [
                'id' => $this->primaryKey(),
                'itemId' => $this->integer()->notNull(),
                'literatureId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'sort' => $this->integer()->defaultValue(0)
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_literature}}', 'literatureId'),
                '{{%museumplus_items_literature}}',
                'literatureId',
                '{{%museumplus_literature}}',
                'id'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_literature}}', 'itemId'),
                '{{%museumplus_items_literature}}',
                'itemId',
                '{{%museumplus_items}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if (!$this->db->tableExists('{{%museumplus_people}}')) {
            $this->createTable('{{%museumplus_people}}', [
                'id' => $this->primaryKey(),
                'title' => $this->text(),
                'data' => $this->longText()->null(),
                'collectionId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);
        }


        if (!$this->db->tableExists('{{%museumplus_items_people}}')) {
            $this->createTable('{{%museumplus_items_people}}', [
                'id' => $this->primaryKey(),
                'itemId' => $this->integer()->notNull(),
                'personId' => $this->integer()->notNull(),
                'type' => $this->string(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'sort' => $this->integer()->defaultValue(0)
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_people}}', 'personId'),
                '{{%museumplus_items_people}}',
                'personId',
                '{{%museumplus_people}}',
                'id'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_people}}', 'itemId'),
                '{{%museumplus_items_people}}',
                'itemId',
                '{{%museumplus_items}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

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
                'uid' => $this->uid(),
                'sort' => $this->integer()->defaultValue(0)
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


        if (!$this->db->tableExists('{{%museumplus_items_assets}}')) {
            $this->createTable('{{%museumplus_items_assets}}', [
                'id' => $this->primaryKey(),
                'itemId' => $this->integer()->notNull(),
                'assetId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'sort' => $this->integer()->defaultValue(0)
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_assets}}', 'assetId'),
                '{{%museumplus_items_assets}}',
                'assetId',
                '{{%assets}}',
                'id',
                'CASCADE',
                'CASCADE'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_assets}}', 'fileId'),
                '{{%museumplus_items_assets}}',
                'itemId',
                '{{%museumplus_items}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

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

        if (!$this->db->columnExists('{{%museumplus_objectgroups}}', 'title')) {
            $this->addColumn('{{%museumplus_objectgroups}}', 'title', $this->text());
        }

        if (!$this->db->columnExists('{{%museumplus_people}}', 'title')) {
            $this->addColumn('{{%museumplus_people}}', 'title', $this->text());
        }

        if (!$this->db->columnExists('{{%museumplus_literature}}', 'title')) {
            $this->addColumn('{{%museumplus_literature}}', 'title', $this->text());
        }

        if (!$this->db->tableExists('{{%museumplus_vocabulary}}')) {
            $this->createTable('{{%museumplus_vocabulary}}', [
                'id' => $this->primaryKey(),
                'parentId' => $this->integer()->notNull()->defaultValue(0),
                'assetId' => $this->integer(),
                'language' => $this->string()->null(),
                'type' => $this->string(),
                'data' => $this->longText()->null(),
                'collectionId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);

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


        if (!$this->db->tableExists('{{%museumplus_items_vocabulary}')) {
            $this->createTable('{{%museumplus_items_vocabulary}}', [
                'id' => $this->primaryKey(),
                'itemId' => $this->integer()->notNull(),
                'vocabularyId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'sort' => $this->integer()->defaultValue(0)
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_vocabulary}}', 'vocabularyId'),
                '{{%museumplus_items_vocabulary}}',
                'vocabularyId',
                '{{%museumplus_vocabulary}}',
                'id'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_vocabulary}}', 'itemId'),
                '{{%museumplus_items_vocabulary}}',
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
        Craft::$app->getDb()->createCommand('SET FOREIGN_KEY_CHECKS = 0;')->execute();
        $this->dropTable('{{%museumplus_items}}');
        $this->dropTable('{{%museumplus_objectgroups}}');
        $this->dropTable('{{%museumplus_items_objectgroups}}');
        $this->dropTable('{{%museumplus_items_items}}');
        $this->dropTable('{{%museumplus_literature}}');
        $this->dropTable('{{%museumplus_items_literature}}');
        $this->dropTable('{{%museumplus_people}}');
        $this->dropTable('{{%museumplus_items_people}}');
        $this->dropTable('{{%museumplus_ownerships}}');
        $this->dropTable('{{%museumplus_items_ownerships}}');
        $this->dropTable('{{%museumplus_items_assets}}');
        $this->dropTable('{{%museumplus_vocabulary}}');
        $this->dropTable('{{%museumplus_items_vocabulary}}');
        Craft::$app->getDb()->createCommand('SET FOREIGN_KEY_CHECKS = 1;')->execute();
        return true;
    }
}
