<?php

namespace furbo\museumplusforcraftcms\controllers;

use Craft;
use craft\web\Controller;
use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;

class VocabulariesController extends Controller
{
    protected array|int|bool $allowAnonymous = ['get-all'];

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

    public function actionGetAll()
    {
        $vocabularies = MuseumPlusVocabulary::find()
            ->type(['ObjKeyWordVgr'])
            ->all();
        $vocabularies = array_map(function($vocabulary) {
            return [
                'id' => $vocabulary->id,
                'title' => $vocabulary->title,
            ];
        }, $vocabularies);
        return $this->asJson($vocabularies);
    }
}