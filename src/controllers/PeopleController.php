<?php

namespace furbo\museumplusforcraftcms\controllers;

use Craft;
use craft\web\Controller;
use furbo\museumplusforcraftcms\elements\MuseumPlusPerson;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
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
    public function actionUpdate()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $personId = $request->getBodyParam('personId');

        $siteHandle = $request->getParam('site', 1);
        $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

        $person = MuseumPlusPerson::find()->id($personId)->one();

        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');
        $person->setFieldValuesFromRequest($fieldsLocation);

        $person->setScenario(\craft\base\Element::SCENARIO_DEFAULT);

        if (!Craft::$app->getElements()->saveElement($person, true)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $person->getErrors(),
                ]);
            }

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('museum-plus-for-craft-cms', 'Person saved.'));

        return $this->redirectToPostedUrl($person);

    }

    public function actionSync()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $personId = $request->getBodyParam('personId');
        $person = MuseumPlusPerson::find()->id($personId)->one();

        $museumPlus = MuseumPlusForCraftCms::$plugin->museumPlus;
        $data = $museumPlus->getPerson($person->collectionId);

        $person->data = $data;
        if (!empty($data->PerNameTxt))
            $person->title = $data->PerNameTxt;
        else if (!empty($data->PerNameTxt))
            $person->title = $data->PerPersonTxt;
        else if (!empty($data->PerNameVrt))
            $person->title = $data->PerNameVrt;
        else
            $person->title = 'Unknown';

        $person->slug = $person->title;

        $success = Craft::$app->elements->saveElement($person);

        if($success){
            Craft::$app->getSession()->setSuccess(Craft::t('museum-plus-for-craft-cms', "Person synced."));
        }else{
            Craft::$app->getSession()->setError(Craft::t('museum-plus-for-craft-cms', "Person not synced."));
        }
        return $this->redirectToPostedUrl($person);

    }
}
