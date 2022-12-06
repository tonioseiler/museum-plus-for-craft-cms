<?php

namespace furbo\museumplusforcraftcms\elements;

use Craft;
use \craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use furbo\museumplusforcraftcms\elements\db\MuseumPlusVocabularyQuery;

class MuseumPlusVocabulary extends Element
{

    public $data = null;

    public $collectionId = null;

    public $type = null;

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

    protected static function defineSources(string $context): array
    {
        return parent::defineSources($context); // TODO: Change the autogenerated stub
    }

    public static function find(): ElementQueryInterface
    {
        return new MuseumPlusVocabularyQuery(static::class);
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'type' => Craft::t('museum-plus-for-craft-cms', 'Type'),
            'collectionId' => Craft::t('museum-plus-for-craft-cms', 'Collection ID'),
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'title',
            'type',
            'collectionId',
        ];
    }

    public function beforeSave(bool $isNew): bool
    {
        return parent::beforeSave($isNew);
    }

}