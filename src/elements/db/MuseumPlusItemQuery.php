<?php
namespace furbo\museumplusforcraftcms\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use furbo\museumplusforcraftcms\records\VocabularyEntryRecord;

class MuseumPlusItemQuery extends ElementQuery
{
    public $collectionId;
    public $assetId;
    public $geographic;
    public $classification;
    public $tag;
    public $objectGroup;
    public $objectGroupId;
    public $person;
    public $ownership;
    public $literature;
    public $inventoryNumber;
    public $extraTitle;
    public $extraDescription;

    public $vocabularyIds = [];


    public function collectionId($value)
    {
        $this->collectionId = $value;

        return $this;
    }

    public function objectGroup($value)
    {
        $this->objectGroup = $value;
        return $this;
    }

    public function objectGroupId($value)
    {
        $this->objectGroupId = $value;
        return $this;
    }

    public function person($value)
    {
        $this->person = $value;
        return $this;
    }

    public function ownership($value)
    {
        $this->ownership = $value;
        return $this;
    }
    public function literature($value)
    {
        $this->literature = $value;
        return $this;
    }

    public function inventoryNumber($value)
    {
        $this->inventoryNumber = $value;
        return $this;
    }

    public function extraTitle($value)
    {
        $this->extraTitle = $value;
        return $this;
    }

    public function extraDescription($value)
    {
        $this->extraDescription = $value;
        return $this;
    }

    public function vocabularyIds($value)
    {
        $this->vocabularyIds[] = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        // join in the items table
        $this->joinElementTable('museumplus_items');

        // select the collection id column
        $this->query->select([
            'museumplus_items.collectionId',
            'museumplus_items.data',
            'museumplus_items.assetId',
            'museumplus_items.inventoryNumber',
            'museumplus_items.extraTitle',
            'museumplus_items.extraDescription',
        ]);

        if ($this->collectionId) {
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.collectionId', $this->collectionId));
        }

        if ($this->assetId) {
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.assetId', $this->assetId));
        }

        if($this->inventoryNumber){
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.inventoryNumber', $this->inventoryNumber));
        }

        if(!is_null($this->extraTitle)){
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.extraTitle', false));
        }

        if(!is_null($this->extraDescription)){
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.extraDescription', false));
        }

        if($this->objectGroup){
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_objectgroups}}'])
                ->where(['objectGroupId' => $this->objectGroup]);
            $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
        }

        if($this->objectGroupId){
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_objectgroups}}'])
                ->where(['objectGroupId' => $this->objectGroupId]);
            $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
        }

        if($this->person){
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_people}}'])
                ->where(['personId' => $this->person]);
            $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
        }

        if ($this->ownership) {
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_ownerships}}'])
                ->where(['ownershipId' => $this->ownership]);
            $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
        }

        if ($this->literature) {
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_literature}}'])
                ->where(['literatureId' => $this->literature]);
            $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
        }

        if(!empty($this->vocabularyIds)){
            foreach ($this->vocabularyIds as $vocabularyId) {
                $descendantsIds = [];
                $record = VocabularyEntryRecord::findOne($vocabularyId);
                if (!empty($record)) {
                    $allDescendantVocabularyIds[$record->type][] = $vocabularyId;
                    // $record->type
                    $descendants = $record->getDescendants();
                    foreach ($descendants as $descendant) {
                        $descendantsIds[] = $descendant->id;
                    }
                    if (!empty($descendantsIds)) {
                        $allDescendantVocabularyIds[$record->type] = array_merge($allDescendantVocabularyIds[$record->type],$descendantsIds);
                    }
                    $allDescendantVocabularyIds[$record->type] = array_map('intval', $allDescendantVocabularyIds[$record->type]);
                }
            }
            //dd($allDescendantVocabularyIds);
            /*
               $subQuery = (new Query())
                    ->select(['itemId'])
                    ->from(['{{%museumplus_items_vocabulary}}'])
                    ->where(['in', 'vocabularyId', $allDescendantVocabularyIds]);
                $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
            */

            //$counter = 0;  // Counter to create unique aliases for each join
            foreach ($allDescendantVocabularyIds as $type => $ids) {
                if (!empty($ids)) {
                    $this->subQuery->andWhere([
                        'exists', (new \craft\db\Query())
                            ->select(['itemId'])
                            ->from(['museumplus_items_vocabulary'])
                            ->where(['in', "vocabularyId", $ids])
                            ->andWhere("itemId = elements.id")  // Linking subquery to the main query's element ID
                    ]);
                    /*
                    $this->subQuery->andWhere([
                        'exists', (new \craft\db\Query())
                            ->select(["{$alias1}". '.itemId'])
                            ->from(["{$alias1}" => 'museumplus_items_vocabulary'])
                            ->innerJoin(["{$alias2}" => 'museumplus_items_vocabulary'], "[[{$alias1}.itemId]] = [[{$alias2}.itemId]]")
                            ->where(['in', "{$alias1}.vocabularyId", $ids])
                            ->andWhere("{$alias1}.itemId = elements.id")  // Linking subquery to the main query's element ID
                    ]);
                    */

                    //$counter++;
                }
            }
        }

       // $this->subQuery->groupBy('museumplus_items.id')

        $this->subQuery->groupBy([
            'museumplus_items.id',
            'museumplus_items.collectionId',
            'museumplus_items.data',
            'museumplus_items.assetId',
            'museumplus_items.inventoryNumber',
            'museumplus_items.extraTitle',
            'museumplus_items.extraDescription',
            'elements_sites.id',
            'elements_sites.siteId'
        ]);


        //die($subQuery->createCommand()->getRawSql());
        return parent::beforePrepare();
    }
}

