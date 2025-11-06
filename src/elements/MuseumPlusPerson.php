<?php

namespace furbo\museumplusforcraftcms\elements;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\User;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\CpScreenResponseBehavior;
use furbo\museumplusforcraftcms\elements\conditions\MuseumPlusPersonCondition;
use furbo\museumplusforcraftcms\elements\db\MuseumPlusPersonQuery;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\records\LiteratureRecord;
use furbo\museumplusforcraftcms\records\OwnershipRecord;
use furbo\museumplusforcraftcms\records\PersonRecord;
use yii\web\Response;

/**
 * Museum Plus Person element type
 */
class MuseumPlusPerson extends Element
{

    public $data = null;

    public $collectionId = null;

    private $record = null;


    public static function displayName(): string
    {
        return Craft::t('museum-plus-for-craft-cms', 'Person');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('museum-plus-for-craft-cms', 'person');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('museum-plus-for-craft-cms', 'People');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('museum-plus-for-craft-cms', 'people');
    }

    public static function refHandle(): ?string
    {
        return 'person';
    }

    public static function hasContent(): bool
    {
        return true;
    }

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

    public static function hasStatuses(): bool
    {
        return true;
    }

    public function getIsEditable(): bool
    {
        return true;
    }

    public static function find(): ElementQueryInterface
    {
        return new MuseumPlusPersonQuery(static::class);
    }

    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('museum-plus-for-craft-cms', 'All'),
            ],
        ];
    }

    protected static function defineActions(string $source): array
    {
        // List any bulk element actions here
        return [];
    }

    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'slug' => Craft::t('app', 'Slug'),
            'uri' => Craft::t('app', 'URI'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
            // ...
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'slug' => ['label' => Craft::t('app', 'Slug')],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            // ...
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'link',
            'dateCreated',
            // ...
        ];
    }

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }

    public function getUriFormat(): ?string
    {
        $settings = MuseumPlusForCraftCms::getInstance()->getSettings()->peoplesites;
        return $settings[$this->site->handle]['uriFormat'];
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('museum-plus-for-craft-cms/people/' . $this->id);
    }

    protected function cpEditUrl(): ?string
    {
        return $this->getCpEditUrl();
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('museum-plus-for-craft-cms/people');
    }

    protected function route(): array|string|null
    {
        $settings = MuseumPlusForCraftCms::getInstance()->getSettings()->peoplesites;
        return [
            'templates/render', [
                'template' => $settings[$this->site->handle]['template'],
                'variables' => [
                    'person' => $this,
                ],
            ],
        ];
    }

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('viewMuseumPlusPeople');
    }

    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('saveMuseumPlusPeople');
    }

    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    public function getFieldLayout(): FieldLayout
    {
        return \Craft::$app->fields->getLayoutByType(MuseumPlusPerson::class);
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => self::pluralDisplayName(),
                'url' => UrlHelper::cpUrl('museum-plus-people'),
            ],
        ]);
    }

    public function afterSave(bool $isNew): void
    {
        if ($isNew) {
            $personRecord = new PersonRecord();
            $personRecord->id = $this->id;
        } else{
            $personRecord = PersonRecord::findOne($this->id);
        }

        $personRecord->collectionId = $this->collectionId;
        $personRecord->data = $this->data;
        $personRecord->save(false);

        parent::afterSave($isNew);
    }

    public function getItems(): array
    {
        return MuseumPlusItem::find()
            ->innerJoin(
                '{{%museumplus_items_people}} mip',
                '[[mip.itemId]] = [[elements.id]]'
            )
            ->where(['mip.personId' => $this->id])
            ->groupBy('mip.itemId')
            ->limit(12)
            ->all();
    }

    public function getOwnerships($limit = 24, $offset = 0)
    {
        // Create cache key
        $cacheKey = "person_ownerships_{$this->collectionId}_limit_{$limit}_offset_{$offset}";
        $cacheDuration = 86400; // 1 day

        $cachedData = \Craft::$app->getCache()->get($cacheKey);
        if ($cachedData !== false) {
            return $cachedData;
        }

        $person = PersonRecord::find()->where(['collectionId' => $this->collectionId])->one();

        // Get all ownership records at once (this relationship works)
        $ownerships = $person->getOwnerships()->all();

        if (empty($ownerships)) {
            $result = [];
            \Craft::$app->getCache()->set($cacheKey, $result, $cacheDuration);
            return $result;
        }

        // Extract all ownership IDs
        $ownershipIds = array_map(function($ownership) {
            return $ownership->id;
        }, $ownerships);

        // Single query to get all ownership objects with pagination
        // This uses the existing working relationship but more efficiently
        $ownershipItems = MuseumPlusItem::find()
            ->innerJoin(
                '{{%museumplus_items_ownerships}} mio',
                '[[mio.itemId]] = [[elements.id]]'
            )
            ->where(['in', 'mio.ownershipId', $ownershipIds])
            ->groupBy('elements.id')
            ->offset($offset)
            ->limit($limit)
            ->all();

        \Craft::$app->getCache()->set($cacheKey, $ownershipItems, $cacheDuration);
        return $ownershipItems;
    }

// Optimized count method using existing relationships
    public function getTotalOwnershipsCount()
    {
        $cacheKey = "person_ownerships_total_{$this->collectionId}";
        $cacheDuration = 86400; // 1 day

        $cachedCount = \Craft::$app->getCache()->get($cacheKey);
        if ($cachedCount !== false) {
            return $cachedCount;
        }

        $person = PersonRecord::find()->where(['collectionId' => $this->collectionId])->one();
        $ownerships = $person->getOwnerships()->all();

        if (empty($ownerships)) {
            \Craft::$app->getCache()->set($cacheKey, 0, $cacheDuration);
            return 0;
        }

        $ownershipIds = array_map(function($ownership) {
            return $ownership->id;
        }, $ownerships);

        // Count total items across all ownerships
        $totalCount = (new \craft\db\Query())
            ->select(['COUNT(DISTINCT mio.itemId)'])
            ->from('{{%museumplus_items_ownerships}} mio')
            ->where(['in', 'mio.ownershipId', $ownershipIds])
            ->scalar();

        \Craft::$app->getCache()->set($cacheKey, (int)$totalCount, $cacheDuration);
        return (int)$totalCount;
    }


// Add this method to check if there are more ownerships (for backward compatibility)
    public function hasMoreOwnerships($currentCount = 24)
    {
        return $this->getTotalOwnershipsCount() > $currentCount;
    }

    public function getData(): array
    {
        $person = PersonRecord::find()->where(['collectionId' => $this->collectionId])->one();

        $personData = [];

        $personDataAttributesRepeatableGroups = $person->getDataAttribute('repeatableGroups');
        $personDataAttributesVocabularyReferences = $person->getDataAttribute('vocabularyReferences');
        $personDataAttributesModuleReferences = $person->getDataAttribute('moduleReferences');

        $personData = [];

        foreach ($personDataAttributesRepeatableGroups as $group) {
            if ($group['name'] === 'PerFunctionsGrp') {
                foreach ($group['items'] as $item) {
                    if (!empty($item['TypeVoc'])) {
                        $personData['function'][] = $item['TypeVoc'];
                    }
                }
            } elseif ($group['name'] === 'PerDateGrp') {
                // Assuming there's only one date item per person.
                foreach ($group['items'] as $item) {
                    $bornDate = null;
                    $bornPlace = null;
                    $bornCountry = null;
                    $diedDate = null;
                    $diedPlace = null;
                    $diedCountry = null;
                    if (!empty($item['DateFromTxt'])) {
                        $bornDate = $item['DateFromTxt'];
                    }
                    if (!empty($item['PlaceTxt'])) {
                        $bornPlace = trim($item['PlaceTxt']);
                    }
                    if (!empty($item['CountryTxt'])) {
                        $bornCountry = trim($item['CountryTxt']);
                    }
                    if (!empty($item['DateToTxt'])) {
                        $diedDate = $item['DateToTxt'];
                    }
                    if (!empty($item['PlaceToTxt'])) {
                        $diedPlace = trim($item['PlaceToTxt']);
                    }
                    if (!empty($item['CountryToTxt'])) {
                        $diedCountry = trim($item['CountryToTxt']);
                    }
                    $born = null;
                    $died = null;
                    $prefixFrom = null;
                    $prefixTo = null;
                    if (!empty($item['PrefixFromVoc']) && $item['PrefixFromVoc']!='aktiv' && $item['PrefixFromVoc']!='*') {
                        $prefixFrom = trim($item['PrefixFromVoc']);
                    }
                    if (!empty($item['PrefixToVoc']) && $item['PrefixToVoc']!='aktiv') {
                        $prefixTo = trim($item['PrefixToVoc']);
                    }
                    if (!empty($item['PrefixFromVoc']) && $item['PrefixFromVoc']=='aktiv') {
                        $personData['dates']='aktiv '.$bornDate.'-'.$diedDate;
                    } else {
                        if(!$prefixFrom && !$prefixTo ){
                            if ($bornDate) {
                                $born = '*' . $bornDate;
                                // If location data is present, append it.
                                if ($bornPlace || $bornCountry) {
                                    $born .= ' in';
                                    if ($bornPlace) {
                                        $born .= ' ' . $bornPlace;
                                    }
                                    if ($bornCountry) {
                                        $born .= ', ' . $bornCountry;
                                    }
                                }
                            }
                            if ($diedDate) {
                                $died = '†' . $diedDate;
                                if ($diedPlace || $diedCountry) {
                                    $died .= ' in';
                                    if ($diedPlace) {
                                        $died .= ' ' . $diedPlace;
                                    }
                                    if ($diedCountry) {
                                        $died .= ', ' . $diedCountry;
                                    }
                                }
                            }
                            $personData['dates']=$born.'<br>'.$died;
                        } else {
                            if($prefixFrom != 'aktiv'){
                                if ($bornDate) {
                                    $born = '* ' . $prefixFrom .' '. $bornDate;
                                    if ($bornPlace || $bornCountry) {
                                        $born .= ' in';
                                        if ($bornPlace) {
                                            $born .= ' ' . $bornPlace;
                                        }
                                        if ($bornCountry) {
                                            $born .= ', ' . $bornCountry;
                                        }
                                    }
                                    $personData['dates']=$born;
                                }
                                if($prefixTo){
                                    if ($bornDate) {
                                        $born = '* ' . $prefixFrom .' '. $bornDate;
                                        if ($bornPlace || $bornCountry) {
                                            $born .= ' in';
                                            if ($bornPlace) {
                                                $born .= ' ' . $bornPlace;
                                            }
                                            if ($bornCountry) {
                                                $born .= ', ' . $bornCountry;
                                            }
                                        }
                                    }
                                    $personData['dates']=$born.'<br>'.$died;
                                }
                            }
                        }
                    }
                }
            } elseif ($group['name'] === 'PerURLGrp') {
                // Process the PerURLGrp items.
                foreach ($group['items'] as $item) {
                    // Only add entries that have a non-empty AddressTxt.
                    if (!empty($item['AddressTxt'])) {
                        $personData['weblinks'][] = [
                            'url' => trim($item['AddressTxt']),
                            'value' => !empty($item['TypeVoc']) ? trim($item['TypeVoc']) : null,
                        ];
                    }
                }
            } elseif ($group['name'] === 'PerBiographicalNoteGrp') {
                // Process the PerBiographicalNoteGrp items.
                foreach ($group['items'] as $item) {
                    if (isset($item['TypeVoc']) && $item['TypeVoc'] === 'Kurzbiografie') {
                        if (
                            !isset($item['StatusVoc'])
                            ||
                            (isset($item['StatusVoc']) && $item['StatusVoc'] == 'aktuell')
                        ) {
                            $noteText = !empty($item['TextClb']) ? trim($item['TextClb']) : null;
                            $noteDate = !empty($item['DateFromTxt']) ? trim($item['DateFromTxt']) : null;
                            $noteSource = !empty($item['SourceTxt']) ? trim($item['SourceTxt']) : null;
                            if ($noteText) {
                                $personData['biographicalNotes'][] = [
                                    'text' => $noteText,
                                    'date' => $noteDate,
                                    'source' => $noteSource,
                                ];
                            }
                        }
                    }
                }
            }
        }

        foreach ($personDataAttributesVocabularyReferences as $vocab) {
            if ($vocab['name'] === 'PerGNDVoc') {
                foreach ($vocab['items'] as $item) {
                    // Make sure both the URL and display value are available.
                    if (!empty($item['name']) && !empty($item['value'])) {
                        $personData['weblinks'][] = [
                            'url' => trim($item['name']),
                            'value' => 'GND',
                        ];
                    }
                }
            }
        }

        foreach ($personDataAttributesModuleReferences as $moduleRef) {
            if (($moduleRef['name'] === 'PerPersonARef') || ($moduleRef['name'] === 'PerPersonBRef')) {
                foreach ($moduleRef['items'] as $item) {
                    if (!empty($item['value'])) {
                        // check if a person with the id exists
                        if ($item['id']) {
                            $networkPersonId = trim($item['id']);
                            $networkPerson = PersonRecord::find()->where(['collectionId' => $networkPersonId])->one();
                            if ($networkPerson) {
                                $personData['network'][] = [
                                    'id' => $networkPersonId,
                                    'name' => trim($item['value']),
                                ];
                            } else {
                                $personData['network'][] = [
                                    'id' => null,
                                    'name' => trim($item['value']),
                                ];
                            }
                        }
                    }
                }
            } else if ($moduleRef['name'] === 'PerLiteratureRef') {
                foreach ($moduleRef['items'] as $item) {
                    if (!empty($item['value'])) {
                        $literature = LiteratureRecord::find()->where(['collectionId' => $item['id']])->one();
                        if (isset($literature) && $literature->title) {
                            $personData['literature'][] = [
                                'name' => trim($literature->title),
                                'id' => $literature->id,
                            ];
                        }
                    }
                }
            } else if ($moduleRef['name'] === 'PerMultimediaRef') {
                $multimediaAssets = $person->getMultimedia();
                foreach ($multimediaAssets as $asset) {
                    if (!empty($asset['id'])) {
                        $personData['download'][] = [
                            'id' => $asset['id'],
                            'text' => $asset->title,
                            'url' => $asset->url,
                        ];
                    }
                }
            }
        }

        return $personData;
    }

    public function getAssets(): array
    {
        $assets = [];
        $multiMedia = (new Query())
            ->select(['assetId'])
            ->from('{{%museumplus_people_assets}}')
            ->where(['peopleId' => $this->id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        foreach($multiMedia as $asset){
            $assets[] = Craft::$app->assets->getAssetById($asset['assetId']);
        }

        return $assets;
    }

    public function getDataAttributes() {
        $rec = $this->getRecord();
        return $rec->getDataAttributes();
    }

    public function getDataAttribute($name) {
        $rec = $this->getRecord();
        return $rec->getDataAttribute($name);
    }

    public function getRecord() {
        if (empty($this->record)) {
            $this->record = PersonRecord::findOne($this->id);
        }
        return $this->record;
    }
    public function getSupportedSites(): array
    {
        $sites = MuseumPlusForCraftCms::getInstance()->getSettings()->peoplesites;
        $filteredSites = [];
        foreach ($sites as $siteHandle => $siteSettings) {
            if (!empty($siteSettings['uriFormat'])) {
                $site = \Craft::$app->sites->getSiteByHandle($siteHandle);
                $filteredSites[] = $site->id;
            }
        }
        return $filteredSites;
    }

    public function syncPersonMultimediaRelations($assetIds)
    {
        return $this->getRecord()->syncPersonMultimediaRelations($assetIds);
    }

    public function getMultimedia()
    {
        return $this->getRecord()->getMultimedia();
    }

}
