<?php

namespace furbo\museumplusforcraftcms\fields;

use Craft;
use craft\fields\BaseRelationField;
use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;

class MuseumPlusVocabularies extends BaseRelationField
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'MuseumPlus - Vocabularies');
    }

    public static function elementType(): string
    {
        return MuseumPlusVocabulary::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add an vocabulary');
    }
}