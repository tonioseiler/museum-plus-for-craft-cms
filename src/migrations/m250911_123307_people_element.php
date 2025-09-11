<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;
use furbo\museumplusforcraftcms\elements\MuseumPlusPerson;
use furbo\museumplusforcraftcms\records\PersonRecord;

/**
 * m250911_123307_people_element migration.
 */
class m250911_123307_people_element extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
       if ($this->db->tableExists('{{%museumplus_people}}')) {
           $people = PersonRecord::find()->all();
           foreach ($people as $person) {
               $personElement = new MuseumPlusPerson();
               $personElement->title = $person->title;
               $personElement->data = $person->data;
               $personElement->collectionId = $person->collectionId;
               if (Craft::$app->elements->saveElement($personElement)) {
                   $oldId = $person->id;
                   $newId = $personElement->id;

                   // museumplus_ownerships_people => personId
                   Craft::$app->db->createCommand()
                       ->update('{{%museumplus_ownerships_people}}', ['personId' => $newId], ['personId' => $oldId])
                       ->execute();

                   // museumplus_people_assets => peopleId
                   Craft::$app->db->createCommand()
                       ->update('{{%museumplus_people_assets}}', ['peopleId' => $newId], ['peopleId' => $oldId])
                       ->execute();

                   // museumplus_items_people => personId
                   Craft::$app->db->createCommand()
                       ->update('{{%museumplus_items_people}}', ['personId' => $newId], ['personId' => $oldId])
                       ->execute();
               }
           }
       }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m250911_123307_people_element cannot be reverted.\n";
        return false;
    }
}
