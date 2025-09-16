<?php

namespace furbo\museumplusforcraftcms\controllers;

use Craft;
use craft\web\Controller;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\elements\MuseumPlusPerson;
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

        if(empty($settings->sitemapSections)) {

            $sitemapSections = [
                  'furbo\museumplusforcraftcms\elements\MuseumPlusItem' => [
                      'enabled' => "1",
                      'changefreq' => 'weekly',
                      'priority' => '1',
                      'filename' => 'sitemap-collection.xml',
                  ],
                'furbo\museumplusforcraftcms\elements\MuseumPlusPerson' => [
                    'enabled' => "1",
                    'changefreq' => 'weekly',
                    'priority' => '1',
                    'filename' => 'sitemap-people.xml',
                ],
            ];
            $settings->sitemapSections = $sitemapSections;
            Craft::$app->getPlugins()->savePluginSettings(MuseumPlusForCraftCms::$plugin, $settings->getAttributes());
        }

        $sitemapSections = [];

        array_walk($settings->sitemapSections, function ($section, $class) use (&$sitemapSections) {
            $sitemapSections[] = [
                'handle' => $class,
                'heading' => $class::pluralDisplayName(),
                'enabled' => $section['enabled'],
                'changefreq' => $section['changefreq'],
                'priority' => $section['priority'],
                'filename' => $section['filename'],
                'entries' => $class::find()->site('*')->count(),
            ];
        });

        //dd($settings->sitemapSections, $sitemapSections);

        return $this->renderTemplate('museum-plus-for-craft-cms/_settings/general', [
            'settings' => $settings,
            'sitemapSections' => $sitemapSections,
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
    public function actionEditPersonFieldLayout(Settings $settings = null): Response
    {
        if(is_null($settings)){
            $settings = MuseumPlusForCraftCms::$plugin->settings;
        }

        return $this->renderTemplate('museum-plus-for-craft-cms/_settings/person-field-layout', [
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

        if(!Craft::$app->getPlugins()->savePluginSettings(MuseumPlusForCraftCms::$plugin, $settings->getAttributes())){
            return $this->asModelFailure($settings, Craft::t('museum-plus-for-craft-cms', 'Couldn’t save general settings.'), 'settings');
        }
        return $this->asSuccess(Craft::t('museum-plus-for-craft-cms', 'Settings saved.'));

    }

    public function actionSaveFieldLayout(): ?Response
    {
        $this->requirePostRequest();

        $settings = MuseumPlusForCraftCms::$plugin->settings;


        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = MuseumPlusItem::class;
        Craft::$app->getFields()->saveLayout($fieldLayout);

        if(!Craft::$app->getPlugins()->savePluginSettings(MuseumPlusForCraftCms::$plugin, $settings->getAttributes())){
            return $this->asModelFailure($settings, Craft::t('museum-plus-for-craft-cms', 'Couldn’t save general settings.'), 'settings');
        }
        return $this->asSuccess(Craft::t('museum-plus-for-craft-cms', 'Settings saved.'));

    }

    public function actionSavePersonFieldLayout(): ?Response
    {
        $this->requirePostRequest();

        $settings = MuseumPlusForCraftCms::$plugin->settings;


        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = MuseumPlusPerson::class;
        Craft::$app->getFields()->saveLayout($fieldLayout);

        if(!Craft::$app->getPlugins()->savePluginSettings(MuseumPlusForCraftCms::$plugin, $settings->getAttributes())){
            return $this->asModelFailure($settings, Craft::t('museum-plus-for-craft-cms', 'Couldn’t save general settings.'), 'settings');
        }
        return $this->asSuccess(Craft::t('museum-plus-for-craft-cms', 'Settings saved.'));
    }

}