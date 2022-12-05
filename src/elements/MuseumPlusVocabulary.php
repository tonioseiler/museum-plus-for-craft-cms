<?php

namespace furbo\museumplusforcraftcms\elements;

use Craft;
use \craft\base\Element;
use craft\helpers\UrlHelper;

class MuseumPlusVocabulary extends Element
{
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

}