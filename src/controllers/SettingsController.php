<?php

namespace furbo\museumplusforcraftcms\controllers;

use Craft;
use craft\web\Controller;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\models\Settings;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use yii\web\Response;

class SettingsController extends Controller
{

    public function actionIndex(): Response
    {
        return $this->redirect('museum-plus-for-craft-cms/settings/general');
    }

    public function actionEditGeneral(Settings $settings = null): Response
    {
        if(is_null($settings)){
            $settings = MuseumPlusForCraftCms::$plugin->settings;
        }

        return $this->renderTemplate('museum-plus-for-craft-cms/_settings/general', [
            'settings' => $settings
        ]);
    }

    public function actionEditSites(Settings $settings = null): Response
    {
        if(is_null($settings)){
            $settings = MuseumPlusForCraftCms::$plugin->settings;
        }

        return $this->renderTemplate('museum-plus-for-craft-cms/_settings/sites', [
            'settings' => $settings
        ]);
    }

    public function actionEditApi(Settings $settings = null): Response
    {
        if(is_null($settings)){
            $settings = MuseumPlusForCraftCms::$plugin->settings;
        }

        return $this->renderTemplate('museum-plus-for-craft-cms/_settings/api', [
            'settings' => $settings
        ]);
    }

    public function actionEditAttachments(Settings $settings = null): Response
    {
        if(is_null($settings)){
            $settings = MuseumPlusForCraftCms::$plugin->settings;
        }

        return $this->renderTemplate('museum-plus-for-craft-cms/_settings/attachments', [
            'settings' => $settings
        ]);
    }

    public function actionEditObjectsGroups(Settings $settings = null): Response
    {
        if(is_null($settings)){
            $settings = MuseumPlusForCraftCms::$plugin->settings;
        }

        return $this->renderTemplate('museum-plus-for-craft-cms/_settings/objects-groups', [
            'settings' => $settings
        ]);
    }

    public function actionEditFieldLayout(Settings $settings = null): Response
    {
        if(is_null($settings)){
            $settings = MuseumPlusForCraftCms::$plugin->settings;
        }

        return $this->renderTemplate('museum-plus-for-craft-cms/_settings/field-layout', [
            'settings' => $settings
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $settings = MuseumPlusForCraftCms::$plugin->settings;


        foreach ($this->request->getBodyParams() as $key => $param){
            if(isset($settings->{$key})){
                $settings->{$key} = $param;
            }
        }

        if(!is_null($this->request->getBodyParam('fieldLayout'))){
            $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
            $fieldLayout->type = MuseumPlusItem::class;
            Craft::$app->getFields()->saveLayout($fieldLayout);
        }

        if(!Craft::$app->getPlugins()->savePluginSettings(MuseumPlusForCraftCms::$plugin, $settings->getAttributes())){
            return $this->asModelFailure($settings, Craft::t('museum-plus-for-craft-cms', 'Couldn’t save general settings.'), 'settings');
        }
        return $this->asSuccess(Craft::t('museum-plus-for-craft-cms', 'Settings saved.'));

    }
}