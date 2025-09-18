<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace furbo\museumplusforcraftcms\records;

use Craft;
use craft\db\ActiveRecord;


use craft\db\Query;
use furbo\museumplusforcraftcms\records\MuseumPlusItemRecord;
use furbo\museumplusforcraftcms\records\DataRecord;
use furbo\museumplusforcraftcms\records\OwnershipRecord;


/*
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 */

class PersonRecord extends DataRecord
{

    public static function tableName(): string
    {
        return '{{%museumplus_people}}';
    }

    public function getItems($type='ObjPerAssociationRef') {
        $result = $this->hasMany(MuseumPlusItemRecord::className(), ['id' => 'itemId'])
            ->viaTable('museumplus_items_people', ['personId' => 'id'], function($query) use ($type) {
                $query->andWhere(['type' => $type]);
            });
        return $result;
    }

    public function getOwnerships() {
        return $this->hasMany(OwnershipRecord::className(), ['id' => 'ownershipId'])
            ->viaTable('museumplus_ownerships_people', ['personId' => 'id']);
    }

    public function syncPersonMultimediaRelations($assetIds)
    {
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_people_assets}}', ['peopleId' => $this->id])
            ->execute();

        //$sort = 1;
        foreach ($assetIds as $assetId) {
            Craft::$app->db->createCommand()
                ->insert('{{%museumplus_people_assets}}', [
                    'peopleId' => $this->id,
                    'assetId' => $assetId,
                    /*'sort' => $sort*/
                ])->execute();
            //$sort++;
        }
    }

    public function getMultimedia()
    {
        $assets = [];
        $multiMedia = (new Query())
            ->select(['assetId'])
            ->from('{{%museumplus_people_assets}}')
            ->where(['peopleId' => $this->id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        foreach($multiMedia as $asset){
            $assets[] = Craft::$app->assets->getAssetById($asset['assetId']);
        }
        return $assets;
    }


}
