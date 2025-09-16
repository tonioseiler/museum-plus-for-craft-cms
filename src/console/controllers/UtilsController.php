<?php

namespace furbo\museumplusforcraftcms\console\controllers;

use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\elements\MuseumPlusPerson;
use yii\console\Controller;

class UtilsController extends Controller
{
    public function actionResaveAll()
    {
        $this->actionResaveItems();
        $this->actionResavePeople();
    }

    public function actionResaveItems()
    {
        $items = MuseumPlusItem::find()->all();
        foreach ($items as $item) {
            if(\Craft::$app->elements->saveElement($item)) {
                echo "Saved item {$item->id}" . PHP_EOL;
            }else{
                echo "Failed to save item {$item->id}" . PHP_EOL;
            }
        }
    }

    public function actionResavePeople()
    {
        $people = MuseumPlusPerson::find()->all();
        foreach ($people as $person) {
            if(\Craft::$app->elements->saveElement($person)){
                echo "Saved $person->id" . PHP_EOL;
            }else{
                echo "Failed to save $person->id" . PHP_EOL;
            }

        }
    }





}