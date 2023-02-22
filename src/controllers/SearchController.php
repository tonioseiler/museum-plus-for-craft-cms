<?php

namespace furbo\museumplusforcraftcms\controllers;

use \craft\web\Controller;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;

class SearchController extends Controller
{
    protected array|int|bool $allowAnonymous = ['search-items', 'autocomplete'];

    public function actionSearchItems($searchString = null)
    {
        $searchString = str_replace(array(".", "-"), "* *", $searchString);
        $query = MuseumPlusItem::find()
            ->search($searchString)
            //->where(['like', 'title', $searchString])
            ->orderBy('score')
            ->limit(10)
            ->all();
        $items = [];
        foreach ($query as $item) {
            $people = [];
            foreach ($item->getAssociationPeople()->all() as $person) {
                $people[] = $person->getDataAttribute('PerPersonTxt');
            }

            $dates = [];
            foreach ($item->getDating() as $date) {
                $dates[] = $date;
            }

            $objectIds = [];
            foreach ($item->getObjectGroups()->all() as $object) {
                $objectIds[] = $object->id;
            }

            $image = $item->getAttachment();
            $items[] = [
                'id' => $item->id,
                'title' => $item->title,
                'url' => $item->url,
                'image' => $image ? $image->getUrl("transformXS") : null,
                'number' => $item->getDataAttribute('ObjObjectNumberTxt'),
                'people' => implode(', ', $people),
                'objectIds' => $objectIds,
                'dates' => implode(', ', $dates),
            ];

        }

        return $this->asJson($items);
    }

    public function actionAutocomplete($searchString = null)
    {
        $vocabularies = [];
        $query = MuseumPlusForCraftCms::$plugin->vocabulary->search($searchString);
        foreach ($query as $vocabulary) {
            $vocabularies[] = $vocabulary->title;
        }
        return $this->asJson($vocabularies);
    }
}