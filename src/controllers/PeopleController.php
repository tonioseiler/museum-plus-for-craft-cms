<?php

namespace furbo\museumplusforcraftcms\controllers;

use Craft;
use craft\web\Controller;
use furbo\museumplusforcraftcms\elements\MuseumPlusPerson;
use yii\web\Response;

/**
 * People controller
 */
class PeopleController extends Controller
{
    public $defaultAction = 'index';
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    /**
     * museum-plus-for-craft-cms/people action
     */
    public function actionIndex(): Response
    {
        // ...
    }
    public function actionEdit(int $personId){
        $request = Craft::$app->getRequest();
        $siteHandle = $request->getParam('site', 1);
        $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

        $person = MuseumPlusPerson::find()
            ->id($personId)
            ->site($site)
            ->one();

        $variables['person'] = $person;

        $variables['actions'] = [];

        $variables['fullPageForm'] = true;

        $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        $variables['enabledSiteIds'] = [];
        foreach (Craft::$app->getSites()->getEditableSiteIds() as $site) {
            $variables['enabledSiteIds'][] = $site;
        }

        // Render the template
        return $this->renderTemplate('museum-plus-for-craft-cms/people/edit', $variables);

    }
}
