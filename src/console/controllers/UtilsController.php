<?php

namespace furbo\museumplusforcraftcms\console\controllers;

use craft\helpers\App;
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
        App::maxPowerCaptain();
        //$items = MuseumPlusItem::find()->site('*')->all();

        $numItems = MuseumPlusItem::find()->count();
        $batchSize = 100;
        $numBatches = floor($numItems / $batchSize);
        for ($i = 0; $i <= $numBatches; $i++) {
            $items = MuseumPlusItem::find()
                ->limit($batchSize)
                ->offset($i * $batchSize)
                ->orderBy('dateUpdated asc')
                ->all();

            foreach ($items as $item) {
                if(\Craft::$app->elements->saveElement($item)) {
                    echo "Saved item {$item->id}" . PHP_EOL;
                }else{
                    echo "Failed to save item {$item->id}" . PHP_EOL;
                }
            }
            
        }
    }

    public function actionResavePeople()
    {
        App::maxPowerCaptain();
        $people = MuseumPlusPerson::find()->site('*')->all();
        foreach ($people as $person) {
            if(\Craft::$app->elements->saveElement($person)){
                echo "Saved $person->id" . PHP_EOL;
            }else{
                echo "Failed to save $person->id" . PHP_EOL;
            }

        }
    }





}