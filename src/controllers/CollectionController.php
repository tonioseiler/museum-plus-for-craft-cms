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

use furbo\museumplusforcraftcms\MuseumplusForCraftcms;
use furbo\museumplusforcraftcms\elements\MuseumplusItem;

use Craft;
use craft\web\Controller;

/**
 * Collection Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Furbo GmbH
 * @package   MuseumplusForCraftcms
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
    protected array|int|bool $allowAnonymous = ['index', 'do-something'];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/museum-plus-for-craft-cms/collection
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $result = 'Welcome to the CollectionController actionIndex() method';

        return $result;
    }

    public function actionEdit(int $itemId = null)
    {
        $request = Craft::$app->getRequest();

        $variables = [];

        // Get the item
        // ---------------------------------------------------------------------
        $item = MuseumplusItem::find()
             ->id($itemId)
             ->one();

        // Set the variables
        // ---------------------------------------------------------------------

        $variables['item'] = $item;


        // Determine which actions should be available
        // ---------------------------------------------------------------------

        $variables['actions'] = [];

        // Full page form variables

        $variables['continueEditingUrl'] = 'collection/{id}';
        $variables['saveShortcutRedirect'] = $variables['continueEditingUrl'];

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

    public function actionUpdate()
    {
        $result = 'Welcome to the CollectionController actionDoSomething() method';

        return $result;
    }
}
