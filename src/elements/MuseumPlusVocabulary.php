<?php

namespace furbo\museumplusforcraftcms\elements;

use craft\db\Query;
use craft\elements\User;
use craft\helpers\Cp;

use Craft;
use \craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use furbo\museumplusforcraftcms\elements\db\MuseumPlusVocabularyQuery;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\records\VocabularyEntryRecord;

class MuseumPlusVocabulary extends Element
{

    public $data = null;

    public $collectionId = null;

    public $type = null;

    public $parentId = null;

    public $language = null;

    private $record = null;

    public static function displayName(): string
    {
        return Craft::t('museum-plus-for-craft-cms', 'Vocabulary');
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasUris(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function refHandle(): ?string
    {
        return 'vocabularies';
    }

    public function getIsEditable(): bool
    {
        return true;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('museum-plus-for-craft-cms/vocabularies/' . $this->id);
    }

    protected function cpEditUrl(): ?string
    {
        return $this->getCpEditUrl();
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('museum-plus-for-craft-cms/vocabularies');
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public function canView(User $user): bool
    {
        return true;
    }

    protected static function defineSources(string $context): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('app', 'All'),
                'criteria' => [],
                'hasThumbs' => false
            ],
            [
                'heading' => 'Types'
            ]
        ];

        $types = MuseumPlusForCraftCms::$plugin->vocabulary->getTypes();
        foreach ($types as $type) {
            $sources[] = [
                'key' => "type:" . $type,
                'label' => $type,
                'criteria' => ['type' => $type]
            ];
        }

        return $sources;
    }

    public static function find(): ElementQueryInterface
    {
        return new MuseumPlusVocabularyQuery(static::class);
    }

    protected static function defineTableAttributes(): array
    {
        return [
            //'type' => Craft::t('app', 'Type'),
            'collectionId' => Craft::t('museum-plus-for-craft-cms', 'Collection ID'),
        ];
    }

    public function __toString(): string
    {
        return $this->title;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'collectionId',
            //'type',
        ];
    }

    public function beforeSave(bool $isNew): bool
    {
        return parent::beforeSave($isNew);
    }

    public function afterSave(bool $isNew): void
    {
        if($isNew){
            $itemRecord = new VocabularyEntryRecord();
            $itemRecord->id = $this->id;
        }
        else{
            $itemRecord = VocabularyEntryRecord::findOne($this->id);
        }
        $itemRecord->collectionId = $this->collectionId;
        $itemRecord->type = $this->type;
        $itemRecord->data = $this->data;
        $itemRecord->parentId = $this->parentId;
        $itemRecord->language = $this->language;
        $itemRecord->save(false);

        parent::afterSave($isNew);
    }

    public function getFieldLayout(): FieldLayout
    {
        return Craft::$app->getFields()->getLayoutByType(MuseumPlusVocabulary::class);
    }

    public function getRecord() {
        if (empty($this->record)) {
            $this->record = VocabularyEntryRecord::findOne($this->id);
        }
        return $this->record;
    }
    public function getDataAttributes() {
        $rec = $this->getRecord();
        return $rec->getDataAttributes();
    }

    public function getDataAttribute($name) {
        $rec = $this->getRecord();
        return $rec->getDataAttribute($name);
    }

    public function getParentElement()
    {
        return MuseumPlusVocabulary::find()->id($this->parentId)->one();
    }

}
