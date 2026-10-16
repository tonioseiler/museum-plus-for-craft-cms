<?php

namespace furbo\museumplusforcraftcms\migrations;

use craft\db\Migration;
use craft\helpers\Db;

/**
 * m261016_150000_add_item_indexes migration.
 *
 * museumplus_items had no index on collectionId and parentId. Lookups by collectionId that read whole rows
 * (MuseumPlusItemRecord::find()->where(['collectionId' => …])->one()) scanned the table including the LONGTEXT
 * data column (~1.4 s with 44,000 objects), and the parent/child reset (UPDATE … WHERE parentId = …) locked every
 * row it scanned (~1.4 s). Both run in every item sync, the first one also on the object page of a part object.
 * museumplus_vocabulary.collectionId: looked up for every vocabulary reference of every object in a sync.
 */
class m261016_150000_add_item_indexes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createIndexIfMissing('{{%museumplus_items}}', ['collectionId']);
        $this->createIndexIfMissing('{{%museumplus_items}}', ['parentId']);
        $this->createIndexIfMissing('{{%museumplus_vocabulary}}', ['collectionId']);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        foreach ([['{{%museumplus_items}}', 'collectionId'], ['{{%museumplus_items}}', 'parentId'], ['{{%museumplus_vocabulary}}', 'collectionId']] as [$table, $column]) {
            $index = Db::findIndex($table, [$column], false, $this->db);
            if ($index !== null) {
                $this->dropIndex($index, $table);
            }
        }

        return true;
    }
}
