<?php
/**
* MuseumPlus for CraftCMS plugin for Craft CMS 3.x
*
* Allows to import MuseumsPlus Collection data to Craft CMS and publish data. Additioanl Web Specific Data can be added to the imported data.
*
* @link      https://furbo.ch
* @copyright Copyright (c) 2022 Furbo GmbH
*/

namespace furbo\museumplusforcraftcms\controllers;

use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;

use Craft;
use craft\web\Controller;

/**
* Collection Controller
*
* @author    Furbo GmbH
* @package   MuseumPlusForCraftCms
* @since     1.0.0
*/
class CollectionController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
    * @var    bool|array Allows anonymous access to this controller's actions.
    *         The actions must be in 'kebab-case'
    * @access protected
    */
    protected array|int|bool $allowAnonymous = ['get-items-by-tag', 'get-items-by-id', 'get-items-by-ids', 'search-items', 'show'];

    // Public Methods
    // =========================================================================

    public function actionEdit(int $itemId)
    {
        $request = Craft::$app->getRequest();

        $variables = [];

        // Get the item
        // ---------------------------------------------------------------------
        $item = MuseumPlusItem::find()
            ->id($itemId)
            ->one();

        // Set the variables
        // ---------------------------------------------------------------------

        $variables['item'] = $item;


        // Determine which actions should be available
        // ---------------------------------------------------------------------

        $variables['actions'] = [];

        $variables['fullPageForm'] = true;

        // Get the site
        // ---------------------------------------------------------------------

        $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        $variables['enabledSiteIds'] = [];
        foreach (Craft::$app->getSites()->getEditableSiteIds() as $site) {
            $variables['enabledSiteIds'][] = $site;
        }

        // Render the template
        return $this->renderTemplate('museum-plus-for-craft-cms/collection/edit', $variables);
    }

    public function actionSync()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $itemId = $request->getBodyParam('itemId');
        $item = MuseumPlusForCraftCms::$plugin->collection->getItemById($itemId);
        MuseumPlusForCraftCms::$plugin->getInstance()->controllerNamespace = 'furbo\museumplusforcraftcms\console\controllers';
        $command = MuseumPlusForCraftCms::$plugin->getInstance()->runAction('collection/update-item', ['collectionItemId' => $item->collectionId]);
        Craft::$app->getSession()->setNotice(Craft::t('museum-plus-for-craft-cms', 'Item synchronized.'));
        return $this->redirectToPostedUrl($item);
    }

    public function actionUpdate()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $itemId = $request->getBodyParam('itemId');
        $item = MuseumPlusItem::find()
            ->id($itemId)
            ->one();

        // Set the title
        $item->title = $request->getBodyParam('title', $item->title);

        //set the custom fields
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');
        $item->setFieldValuesFromRequest($fieldsLocation);

        //dd($request->getBodyParams());

        $item->setScenario(\craft\base\Element::SCENARIO_DEFAULT);

        // Save it
        // TODO: why does validation always fails
        if (!Craft::$app->getElements()->saveElement($item, true)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $item->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('museum-plus-for-craft-cms', 'Couldn’t save item.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $item
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            $return = [];

            $return['success'] = true;
            $return['id'] = $item->id;
            $return['title'] = $item->title;

            if (!$request->getIsConsoleRequest() && $request->getIsCpRequest()) {
                $return['cpEditUrl'] = $item->getCpEditUrl();
            }

            $return['dateCreated'] = DateTimeHelper::toIso8601($item->dateCreated);
            $return['dateUpdated'] = DateTimeHelper::toIso8601($item->dateUpdated);

            return $this->asJson($return);
        }

        Craft::$app->getSession()->setNotice(Craft::t('museum-plus-for-craft-cms', 'Item saved.'));

        return $this->redirectToPostedUrl($item);

    }

    public function actionShow($slug, $id)
    {
        dd("show");
    }

    public function actionGetItemsByTag($tagId) {
        return MuseumPlusForCraftCms::$plugin->collection->getItemsByTag($tagId);
    }

    public function actionGetItemsById($id) {
        return MuseumPlusForCraftCms::$plugin->collection->getItemsById($id);
    }

    public function actionGetItemsByIds($ids) {
        return MuseumPlusForCraftCms::$plugin->collection->getItemsById($ids);
    }

    public function actionSearchItems($params = []) {
        return MuseumPlusForCraftCms::$plugin->collection->searchItems($params);
    }

}
