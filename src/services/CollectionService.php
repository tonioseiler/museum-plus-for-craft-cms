<?php
/**
 * MuseumPlus for CraftCMS plugin for Craft CMS 3.x
 *
 * Allows to import MuseumsPlus Collection data to Craft CMS and publish data. Additioanl Web Specific Data can be added to the imported data.
 *
 * @link      https://furbo.ch
 * @copyright Copyright (c) 2022 Furbo GmbH
 */

namespace furbo\museumplusforcraftcms\services;

use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\records\MuseumPlusItemRecord;
use furbo\museumplusforcraftcms\records\ObjectGroupRecord;

use Craft;
use craft\base\Component;
use craft\helpers\App;
use furbo\museumplusforcraftcms\records\PersonRecord;
use furbo\museumplusforcraftcms\records\OwnershipRecord;
use furbo\museumplusforcraftcms\records\LiteratureRecord;
use furbo\museumplusforcraftcms\records\VocabularyEntryRecord;
use yii\db\Expression;


/**
 * MuseumPlus Service
 *
 * From any other plugin file, call it like this:
 *
 *     MuseumPlusForCraftCms::$plugin->collection->someMethod()
 *
 *
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 */
class CollectionService extends Component
{
    public function getAllObjectGroups() {
        $objectGroupRecords = ObjectGroupRecord::find()
            ->orderBy(['title' => SORT_ASC])
            ->all();
        return $objectGroupRecords;
    }

    public function getObjectGroupById($id) {
        $object = ObjectGroupRecord::find()
            ->where(['id' => $id])
            ->one();
        return $object;
    }

    public function getPeopleById($id) {
        $people = PersonRecord::find()
            ->where(['id' => $id])
            ->one();
        return $people;
    }

    public function getAllPeople() {
        $people = PersonRecord::find()
            ->orderBy(['title' => SORT_ASC])
            ->all();
        return $people;
    }

    public function getOwnershipById($id) {
        $ownership = OwnershipRecord::find()
            ->where(['id' => $id])
            ->one();
        return $ownership;
    }

    public function getAllOwnerships() {
        $ownerships = OwnershipRecord::find()
            ->orderBy(['title' => SORT_ASC])
            ->all();
        return $ownerships;
    }

    public function getLiteratureById($id) {
        $literature = LiteratureRecord::find()
            ->where(['id' => $id])
            ->one();
        return $literature;
    }

    public function getAllLiteratures() {
        $literature = LiteratureRecord::find()
            ->orderBy(['title' => SORT_ASC])
            ->all();
        return $literature;
    }

    public function getItemById($id) {
        $item = MuseumPlusItem::find()
            ->id($id)
            ->one();
        return $item;
    }

    public function getItemsByIds($ids, $limit = 10, $offset = 0) {
        $items = MuseumPlusItem::find()
            ->id($ids)
        ;
        //Craft::$app->session->set('museumPlusCriteria', $items);
        $items = $items->limit($limit)->offset($offset);
        return $items;
    }


    public function searchItems($params, $limit = 10, $offset = 0) {
        $criteria = [];
        $items = MuseumPlusItem::find();
        $items->orderBy(['sort' => SORT_ASC]);
        if(isset($params['search'])) {
            //escape special characters
            $params['search'] = str_replace(array(".", "-"), "* *", $params['search']);
            $items = $items->search("*" . $params['search'] . "*");
            $criteria['search'] = $params['search'];
        }

        /* removed as too specific
        if(isset($params['geographic'])) {
            if(!empty($params['geographic'])) {
                $items = $items->vocabularyIds($params['geographic']);
                $criteria['geographic'] = $params['geographic'];
            }
        }
        if(isset($params['classification'])) {
            if(!empty($params['classification'])) {
                $items = $items->vocabularyIds($params['classification']);
                $criteria['classification'] = $params['classification'];
            }
        }
        */

        if(isset($params['vocabulary'])) {
            if(!empty($params['vocabulary'])) {
                if(is_array($params['vocabulary'])) {
                    foreach ($params['vocabulary'] as $singleVoc) {
                        if(!empty ($singleVoc) ) {
                            $items = $items->vocabularyIds($singleVoc);
                        }
                    }
                } else {
                    $items = $items->vocabularyIds($params['vocabulary']);
                }
            }
        }

        if(isset($params['objectGroup'])) {
            $items = $items->objectGroup($params['objectGroup']);
            $criteria['objectGroup'] = $params['objectGroup'];
        }

        if(isset($params['person'])) {
            $items = $items->person($params['person']);
            $criteria['person'] = $params['person'];
        }

        if(isset($params['ownership'])) {
            $items = $items->ownership($params['ownership']);
            $criteria['ownership'] = $params['ownership'];
        }

        if(isset($params['literature'])) {
            $items = $items->literature($params['literature']);
            $criteria['literature'] = $params['literature'];
        }

        if(isset($params['inventoryNumber'])) {
            $items = $items->inventoryNumber($params['inventoryNumber']);
            $criteria['inventoryNumber'] = $params['inventoryNumber'];
        }

        //Craft::$app->session->set('museumPlusCriteria', $items);

        if(isset($params['firstObjectId'])) {
            $params['firstObjectId'] = intval($params['firstObjectId']);
            $firstObject = MuseumPlusItem::find()->id($params['firstObjectId']);
            if($firstObject) {
                $ids = $items->ids();
                //remove element from array by value
                $key = array_search($params['firstObjectId'], $ids);
                if($key !== false) {
                    unset($ids[$key]);
                }
                //add element to the beginning of the array
                array_unshift($ids, $params['firstObjectId']);
                $items = $items->orderBy([new Expression('FIELD (museumplus_items.id, ' . implode(',', $ids) . ')')]);
            }
        }

        $items = $items->limit($limit)->offset($offset);
        return $items;
    }

    public function getBookmarks($limit = 10, $offset = 0) {
        if(Craft::$app->session->has('bookmarks')) {
            $bookmarks = MuseumPlusItem::find();
            $bookmarks = $bookmarks->id(Craft::$app->session->get('bookmarks'));
            Craft::$app->session->set('museumPlusCriteria', $bookmarks);
            $bookmarks = $bookmarks->limit($limit)->offset($offset);
            return $bookmarks;
        } else {
            return [];
        }
    }

    public function isBookmarked($id) {
        if(Craft::$app->session->has('bookmarks')) {
            $bookmarks = Craft::$app->session->get('bookmarks');
            if(in_array($id, $bookmarks)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}
