<?php
/**
 * MuseumPlus for CraftCMS plugin for Craft CMS 3.x
 *
 * Allows to import MuseumsPlus Collection data to Craft CMS and publish data. Additioanl Web Specific Data can be added to the imported data.
 *
 * @link      https://furbo.ch
 * @copyright Copyright (c) 2022 Furbo GmbH
 */

namespace furbo\museumplusforcraftcms\elements;

use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\User;
use craft\helpers\Cp;

use craft\helpers\Html;
use craft\helpers\UrlHelper;
use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\elements\db\MuseumPlusItemQuery;
use furbo\museumplusforcraftcms\records\ObjectGroupRecord;
use furbo\museumplusforcraftcms\records\MuseumPlusItemRecord;

//use furbo\museumplusforcraftcms\elements\db\MuseumPlusVocabularyQuery;
//use furbo\museumplusforcraftcms\records\VocabularyEntryRecord;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\models\FieldLayout;
use craft\models\TagGroup;
use craft\helpers\Db;

/**
 *  Element MuseumPlusItem
 *
 *
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 */
class MuseumPlusItem  extends Element
{

    // Public Properties
    // =========================================================================

    public $data = null;

    public $collectionId = null;

    public $assetId = null;

    public $multiMedia = [];

    public $inventoryNumber;

    public $sort;


    public $extraTitle;

    public $extraDescription;

    private $record = null;


    // Static Methods
    // =========================================================================

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        return Craft::t('museum-plus-for-craft-cms', 'Item');
    }

    /**
     * Returns whether elements of this type will be storing any data in the `content`
     * table (tiles or custom fields).
     *
     * @return bool Whether elements of this type will be storing any data in the `content` table.
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * Returns whether elements of this type have traditional titles.
     *
     * @return bool Whether elements of this type have traditional titles.
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasUris(): bool
    {
        return true;
    }


    public static function isLocalized(): bool
    {
        return true;
    }

    public static function refHandle(): ?string
    {
        return 'item';
    }

    /**
     * Defines the sources that elements of this type may belong to.
     *
     * @param string|null $context The context ('index' or 'modal').
     *
     * @return array The sources.
     * @see sources()
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('app', 'All'),
                'criteria' => [],
                'hasThumbs' => false
            ],
            [
                'heading' => 'Object Groups',
            ],
        ];

        $objectGroups = MuseumPlusForCraftCms::$plugin->collection->getAllObjectGroups();

        foreach ($objectGroups as $objectGroup) {
            $sources[] = [
                'key' => 'objectGroup:' . $objectGroup->id,
                'label' => $objectGroup->title,
                'criteria' => ['objectGroupId' => $objectGroup->id]
            ];
        }

        return $sources;
    }

    // Public Methods
    // =========================================================================
    /*public function rules(): array
    {
        return [];
    }*/


    /**
     * Returns whether the current user can edit the element.
     *
     * @return bool
     */
    public function getIsEditable(): bool
    {
        return true;
    }

    /**
     * Returns the field layout used by this element.
     *
     * @return FieldLayout|null
     */
    public function getFieldLayout(): FieldLayout
    {
        return \Craft::$app->fields->getLayoutByType(MuseumPlusItem::class);
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * Returns the HTML for the element’s editor HUD.
     *
     * @return string The HTML for the editor HUD
     */
    public function getEditorHtml(): string
    {
        $html = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Title'),
                'siteId' => $this->siteId,
                'id' => 'title',
                'name' => 'title',
                'value' => $this->title,
                'errors' => $this->getErrors('title'),
                'first' => true,
                'autofocus' => true,
                'required' => true
            ]
        ]);

        $html .= parent::getEditorHtml();

        return $html;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('museum-plus-for-craft-cms/collection/' . $this->id);
    }

    protected function cpEditUrl(): ?string
    {
        return $this->getCpEditUrl();
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('museum-plus-for-craft-cms/collection');
    }


    public static function hasStatuses(): bool
    {
        return true;
    }

    protected function uiLabel(): ?string
    {
        if (!isset($this->title) || trim($this->title) === '') {
            return '–';
        }

        return null;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * Performs actions before an element is saved.
     *
     * @param bool $isNew Whether the element is brand new
     *
     * @return bool Whether the element should be saved
     */
    public function beforeSave(bool $isNew): bool
    {

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->inventoryNumber)));
        $this->slug = $slug;

        return true;
    }

    /**
     * Performs actions after an element is saved.
     *
     * @param bool $isNew Whether the element is brand new
     *
     * @return void
     */
    public function afterSave(bool $isNew): void
    {

        if ($isNew) {
            $itemRecord = new MuseumPlusItemRecord();
            $itemRecord->id = $this->id;
        }
        else {
            $itemRecord = MuseumPlusItemRecord::findOne($this->id);
        }

        $itemRecord->collectionId = $this->collectionId;
        $itemRecord->data = $this->data;
        $itemRecord->assetId = $this->assetId;
        $itemRecord->inventoryNumber = $this->inventoryNumber;
        $itemRecord->sort = $this->sort;
        $itemRecord->extraTitle = $this->extraTitle;
        $itemRecord->extraDescription = $this->extraDescription;

        $itemRecord->save(false);

        parent::afterSave($isNew);

    }

    public function getMultimedia()
    {
        $assets = [];
        $multiMedia = (new Query())
            ->select(['assetId'])
            ->from('{{%museumplus_items_assets}}')
            ->where(['itemId' => $this->id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        foreach($multiMedia as $asset){
            $assets[] = Craft::$app->assets->getAssetById($asset['assetId']);
        }

        return $assets;
    }

    public function getAttachment()
    {
        if($this->assetId){
            return Craft::$app->assets->getAssetById($this->assetId);
        }
        return false;
    }

    public function addMultimedia($assetId){
        Craft::$app->db->createCommand()
            ->insert('{{%museumplus_items_assets}}', [
                'itemId' => $this->id,
                'assetId' => $assetId,
            ])->execute();
    }

    public function deleteMultimedia($assetId){
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_assets}}', ['itemId' => $this->id, 'assetId' => $assetId])->execute();
    }

    /**
     * Performs actions before an element is deleted.
     *
     * @return bool Whether the element should be deleted
     */
    public function beforeDelete(): bool
    {
        return true;
    }

    /**
     * Performs actions after an element is deleted.
     *
     * @return void
     */
    public function afterDelete(): void
    {
    }

    /**
     * Performs actions before an element is moved within a structure.
     *
     * @param int $structureId The structure ID
     *
     * @return bool Whether the element should be moved within the structure
     */
    public function beforeMoveInStructure(int $structureId): bool
    {
        return true;
    }

    public function canView(User $user): bool
    {
        return true;
    }

    /**
     * Performs actions after an element is moved within a structure.
     *
     * @param int $structureId The structure ID
     *
     * @return void
     */
    public function afterMoveInStructure(int $structureId): void
    {
    }

    public static function find(): ElementQueryInterface
    {
        return new MuseumPlusItemQuery(static::class);
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'assetId':
                if($this->assetId) {
                    $asset = Craft::$app->getAssets()->getAssetById($this->assetId);
                    if($asset){
                        return Cp::elementPreviewHtml([$asset], Cp::ELEMENT_SIZE_SMALL, false, true, true, false);
                    }
                    return $this->assetId;
                }
                return '-';
            case 'multimedia':
                $assets = $this->getMultimedia();
                if(count($assets)) {
                    return Cp::elementPreviewHtml($assets, Cp::ELEMENT_SIZE_SMALL, false, true, true, false);
                }
                return '-';
            case 'collectionId':
                return $this->collectionId;
            case 'inventoryNumber':
                //return $this->inventoryNumber;
                if($this->inventoryNumber) {
                    return $this->inventoryNumber;
                }
                return '--';
            case 'frontendLink':
                $url = $this->getUrl();
                if ($url !== null) {
                    return Html::a('', $url, [
                        'rel' => 'noopener',
                        'target' => '_blank',
                        'data-icon' => 'world',
                        'title' => Craft::t('app', 'Visit webpage'),
                        'aria-label' => Craft::t('app', 'View'),
                    ]);
                }
                return '';
        }
        return parent::tableAttributeHtml($attribute);
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'inventoryNumber' => 'Inventory Number',
            'collectionId' => 'MuseumPlus Id',
            'assetId' => 'Main Image',
            'multimedia' => 'Media',
            'id' => ['label' => Craft::t('app', 'ID')],
            'frontendLink' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['collectionId', 'assetId', 'multimedia','frontendLink'];
    }


    protected static function defineSortOptions(): array
    {
        return [
            'title' => \Craft::t('app', 'Title'),
            'collectionId' => 'MuseumPlus Id',
            'inventoryNumber' => 'Inventory Number'
        ];
    }


    protected static function defineSearchableAttributes(): array
    {
        return ['data'];
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function getUriFormat(): ?string {
        $settings = MuseumPlusForCraftCms::getInstance()->getSettings()->sites;

        return $settings[$this->site->handle]['uriFormat'];
    }

    protected function route(): array|string|null {
        $settings = MuseumPlusForCraftCms::getInstance()->getSettings()->sites;
        return [
            'templates/render', [
                'template' => $settings[$this->site->handle]['template'],
                'variables' => [
                    'entry' => $this,
                ],
            ],
        ];
    }

    public function syncMultimediaRelations($assetIds) {
        $this->getRecord()->syncMultimediaRelations($assetIds);
    }

    public function syncPeopleRelations($peopleIds, $type) {
        $this->getRecord()->syncPeopleRelations($peopleIds, $type);
    }

    public function syncOwnershipRelations($ownershipIds) {
        $this->getRecord()->syncOwnershipRelations($ownershipIds);
    }

    public function syncLiteratureRelations($literureIds) {
        $this->getRecord()->syncLiteratureRelations($literureIds);
    }

    public function syncItemRelations($itemIds) {
        $this->getRecord()->syncItemRelations($itemIds);
    }

    public function syncVocabularyRelations($syncData) {
        $this->getRecord()->syncVocabularyRelations($syncData);
    }

    public function getObjectGroups() {
        $rec = $this->getRecord();
        return $rec->getObjectGroups();
    }

    public function getLiterature() {
        $rec = $this->getRecord();
        return $rec->getLiterature();
    }

    public function getOwnerships() {
        $rec = $this->getRecord();
        return $rec->getOwnerships();
    }

    public function getAssociationPeople() {
        $rec = $this->getRecord();
        return $rec->getAssociationPeople();
    }

    public function getOwnerPeople() {
        $rec = $this->getRecord();
        return $rec->getOwnerPeople();
    }

    public function getAdministrationPeople() {
        $rec = $this->getRecord();
        return $rec->getAdministrationPeople();
    }

    public function getRelatedItems() {
        $rec = $this->getRecord();
        return $rec->getRelatedItems();
    }

    public function getVocabularyEntries() {
        $rec = $this->getRecord();
        return $rec->getVocabularyEntries();
    }

    public function getVocabularyEntriesByType($type) {
        $rec = $this->getRecord();
        $vcs = $rec->getVocabularyEntriesByType($type);
    }

    public function getRecord() {
        if (empty($this->record)) {
            $this->record = MuseumPlusItemRecord::findOne($this->id);
        }
        return $this->record;
    }

    public function getDating() {
        $rec = $this->getRecord();
        return $rec->getRepeatableGroupValues('ObjDateGrp', 'DateTxt');
    }

    public function getGeographicReferencesOld() {
        $rec = $this->getRecord();
        return $this->getVocabularyEntries()->where(['type' => 'GenPlaceVgr']);
    }

    public function getGeographicReferences() {
        $rec = $this->getRecord();
        return $this->getVocabularyEntries()->where(['type' => 'GenGeoPoliticalVgr']);
    }

    public function getGeographicReferencesHistory() {
        $rec = $this->getRecord();
        return $this->getVocabularyEntries()->where(['type' => 'GenGeoHistoryVgr']);
    }

    public function getGeographicReferencesGeography() {
        $rec = $this->getRecord();
        return $this->getVocabularyEntries()->where(['type' => 'GenGeoGeographyVgr']);
    }

    public function getGeographyCulture() {
        $rec = $this->getRecord();
        return $this->getVocabularyEntries()->where(['type' => 'GenGeoCultureVgr']);
    }

    public function getTags() {
        $rec = $this->getRecord();
        return $this->getVocabularyEntries()->where(['type' => 'ObjKeyWordVgr']);
    }

    public function getClassification() {
        $rec = $this->getRecord();
        return $this->getVocabularyEntries()->where(['type' => 'ObjClassificationVgr']);
    }

    public function getMaterial() {
        $rec = $this->getRecord();
        return $rec->getRepeatableGroupValues('ObjMaterialTechniqueGrp', 'DetailsTxt');
    }

    public function getDimensions() {
        $rec = $this->getRecord();
        return $rec->getRepeatableGroupValues('ObjDimAllGrp', 'PreviewVrt');
    }

    public function getCreditLine() {
        $rec = $this->getRecord();
        $creditLineEntries = $this->getVocabularyEntries()->where(['type' => 'ObjCreditlineVgr'])->all();

        $creditLines = [];
        foreach($creditLineEntries as $cle) {
            $creditLines[] = $cle->getDataAttribute('content');
        }
        return implode(PHP_EOL, $creditLines);
    }

    public function getDetailText() {
        $rec = $this->getRecord();
        $tmp = $rec->getRepeatableGroupValues('ObjLabelRaisonneTextGrp', 'TextClb', ['Objekttext', 'Jahresbericht RBG']);
        $tmp = implode(PHP_EOL, $tmp);

        $tmp .= $rec->getDataAttribute('ObjScopeContentClb');

        return $tmp;
    }

    public function getDataAttributes() {
        $rec = $this->getRecord();
        return $rec->getDataAttributes();
    }

    public function getDataAttribute($name) {
        $rec = $this->getRecord();
        return $rec->getDataAttribute($name);
    }

    public function getPrev($criteria = false): ?ElementInterface
    {
        if (empty($criteria)) {
            return parent::getPrev($this->getCriteria());
        } else {
            return parent::getPrev($criteria);
        }
    }

    public function getNext($criteria = false): ?ElementInterface
    {
        if (empty($criteria)) {
            return parent::getNext($this->getCriteria());
        } else {
            return parent::getNext($criteria);
        }
    }

    private function getCriteria()
    {
        $criteria = false;
        if(Craft::$app->session->get('museumPlusCriteria')) {
            $criteria = Craft::$app->session->get('museumPlusCriteria');
            $criteria->limit = -1;
            $criteria->offset = 0;
        }
        return $criteria;
    }

    public function getMetaText()
    {
        $metaDescription = "";
        $metaDescription .= $this->title . " ";
        $metaDescription .= implode(" ", $this->getDating()) . " ";
        $metaDescription .= implode(" ", $this->getMaterial()) . " ";
        return $metaDescription;
    }

    public function getMetaKeywords()
    {
        $metaKeywords = [];
        foreach ($this->getTags()->all() as $tag) {
            $metaKeywords[] = $tag->title;
        }
        return implode(", ", $metaKeywords);
    }


    public function getSupportedSites(): array
    {
        $sites = MuseumPlusForCraftCms::getInstance()->getSettings()->sites;
        $filteredSites = [];
        foreach ($sites as $siteHandle => $siteSettings) {
            if (!empty($siteSettings['uriFormat'])) {
                $site = \Craft::$app->sites->getSiteByHandle($siteHandle);
                $filteredSites[] = $site->id;
            }
        }
        return $filteredSites;
    }



}
