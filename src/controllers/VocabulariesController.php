<?php

namespace furbo\museumplusforcraftcms\controllers;

use Craft;
use craft\web\Controller;
use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;

class VocabulariesController extends Controller
{
    public function actionEdit(int $vocabularyId)
    {
        $request = Craft::$app->getRequest();

        $variables = [];

        $vocabulary = MuseumPlusVocabulary::find()
            ->id($vocabularyId)
            ->one();

        $variables['vocabulary'] = $vocabulary;

        return $this->renderTemplate('museum-plus-for-craft-cms/vocabularies/edit', $variables);
    }
}