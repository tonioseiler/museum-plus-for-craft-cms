<?php
/**
 * MuseumPlus for CraftCMS plugin for Craft CMS 3.x
 *
 * Allows to import MuseumsPlus Collection data to Craft CMS and publish data. Additioanl Web Specific Data can be added to the imported data.
 *
 * @link      https://furbo.ch
 * @copyright Copyright (c) 2022 Furbo GmbH
 */

namespace furbo\museumplusforcraftcms;

use craft\events\DefineAttributeKeywordsEvent;
use craft\events\IndexKeywordsEvent;
use craft\events\RegisterElementSearchableAttributesEvent;
use craft\helpers\App;
use craft\services\Search;
use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;
use furbo\museumplusforcraftcms\services\MuseumPlusService;
use furbo\museumplusforcraftcms\variables\MuseumPlusForCraftCmsVariable;
use furbo\museumplusforcraftcms\models\Settings;
use furbo\museumplusforcraftcms\fields\MuseumPlusItems as ItemsField;
use     furbo\museumplusforcraftcms\fields\MuseumPlusVocabularies as VocabulariesField;
use furbo\museumplusforcraftcms\utilities\Collection as CollectionUtility;
use furbo\museumplusforcraftcms\widgets\Collection as CollectionWidget;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\console\Application as ConsoleApplication;
use craft\web\UrlManager;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Utilities;
use craft\web\twig\variables\CraftVariable;
use craft\services\Dashboard;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 *
 * @property  MuseumPlusService $museumPlus
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class MuseumPlusForCraftCms extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * MuseumPlusForCraftCms::$plugin
     *
     * @var MuseumPlusForCraftCms
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSettings = true;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * MuseumPlusForCraftCms::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Add in our console commands
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'furbo\museumplusforcraftcms\console\controllers';
        }

        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules = array_merge($event->rules, $this->getRoutes());
            }
        );

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules = array_merge($event->rules, $this->getCpRoutes());
            }
        );


        // Register our elements
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = MuseumPlusItem::class;
                $event->types[] = MuseumPlusVocabulary::class;
            }
        );

        // Register our fields
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = ItemsField::class;
                $event->types[] = VocabulariesField::class;
            }
        );

        // Register our utilities
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = CollectionUtility::class;
            }
        );

        // Register our widgets
        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = CollectionWidget::class;
            }
        );

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('museumPlusForCraftCms', MuseumPlusForCraftCmsVariable::class);
            }
        );

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                }
            }
        );

        // Executed after settings are saved
        Event::on(
            Plugin::class,
            Plugin::EVENT_AFTER_SAVE_SETTINGS,
            function (Event $event) {
                if ($event->sender::class == "furbo\museumplusforcraftcms\MuseumPlusForCraftCms") {
                    //save field layout
                    $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost('settings');
                    $fieldLayout->type = MuseumPlusItem::class;
                    Craft::$app->getFields()->saveLayout($fieldLayout);



                }
            }
        );

        Event::on(
            Search::class,
            Search::EVENT_BEFORE_INDEX_KEYWORDS,
            function(IndexKeywordsEvent $e) {
                // Element being indexed:
                $element = $e->element;

                // Current attribute name:
                $attribute = $e->attribute;

                if($attribute == 'data'){
                    
                        $data = "";
                    try {
                        foreach($element->getAssociationPeople()->all() as $person){
                            if(is_array($person->getDataAttribute('PerPersonTxt'))){
                                foreach($person->getDataAttribute('PerPersonTxt') as $personTxt){
                                    $data .= $personTxt . " ";
                                }
                            }else{
                                $data .= $person->getDataAttribute('PerPersonTxt') . " ";
                            }
                        }

                        // foreach ($element->getDating() as $date){
                        //     $data .= $date . " ";
                        // }

                        //$data .= $element->getDataAttribute('ObjObjectNumberTxt') . " ";

                        foreach($element->getGeographicReferences()->all() as $geo){
                            $data .= $geo->title . " ";
                        }

                        foreach($element->getMaterial() as $material){
                            $data .= $material . " ";
                        }

                        // foreach($element->getClassification()->all() as $classification){
                        //     $data .= $classification->title . " ";
                        // }

                        // foreach($element->getObjectGroups()->all() as $objectGroup){
                        //     $data .= $objectGroup->title . " ";
                        // }

                        // foreach($element->getOwnerships()->all() as $ownership){
                        //     $data .= $ownership->getDataAttribute('OwsOwnershipVrt') . " ";
                        // }

                        // foreach($element->getTags()->all() as $tag){
                        //     $data .= $tag->title . " ";
                        // }

                        //$data .= $element->getDetailText() . " ";

                        // foreach($element->getLiterature()->all() as $literature){
                        //      $data .= $literature->title . " ";
                        // }
                    } catch (\Throwable $th) {}
                    
                    $e->keywords = $data;
                        
                }
            }
        );

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'museum-plus-for-craft-cms',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'museum-plus-for-craft-cms/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    protected function getCpRoutes(): array
    {
        return [
            'museum-plus-for-craft-cms' => [ 'template' => 'museum-plus-for-craft-cms' ],
            'museum-plus-for-craft-cms/collection' => ['template' => 'museum-plus-for-craft-cms/collection'],
            'museum-plus-for-craft-cms/collection/<itemId:\d+>' => 'museum-plus-for-craft-cms/collection/edit',
            'museum-plus-for-craft-cms/vocabularies' => ['template' => 'museum-plus-for-craft-cms/vocabularies'],
            'museum-plus-for-craft-cms/vocabularies/<vocabularyId:\d+>' => 'museum-plus-for-craft-cms/vocabularies/edit',
        ];
    }

    protected function getRoutes(): array
    {
        return [
            'search/items/<searchString>' => 'museum-plus-for-craft-cms/search/search-items',
            'search/autocomplete/<searchString>' => 'museum-plus-for-craft-cms/search/autocomplete',
            'bookmark/save' => 'museum-plus-for-craft-cms/bookmark/save',
            'bookmark/check/<objectId>' => 'museum-plus-for-craft-cms/bookmark/check',
            'vocabularies/get-all' => 'museum-plus-for-craft-cms/vocabularies/get-all',
            'collection/get-random-item-by-tag/<tagId>' => 'museum-plus-for-craft-cms/collection/get-random-item-by-tag',
        ];
    }

    public function getCpNavItem(): ?array
    {
        $cpNavItem = parent::getCpNavItem();

        $settings = self::$plugin->getSettings();

        $cpNavItem['label'] = $settings['cpTitle'];
        //$cpNavItem['url'] = 'museum-plus-for-craft-cms/collection';

        $cpNavItem['subnav'] = [];

        $cpNavItem['subnav']['items'] = ['label' => Craft::t('museum-plus-for-craft-cms', 'Items'), 'url' => 'museum-plus-for-craft-cms/collection'];

        $cpNavItem['subnav']['vocabularies'] = ['label' => Craft::t('museum-plus-for-craft-cms', 'Vocabularies'), 'url' => 'museum-plus-for-craft-cms/vocabularies'];


        return $cpNavItem;
    }
}
