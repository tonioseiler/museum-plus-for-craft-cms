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

use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;

use Craft;
use craft\base\Component;
use craft\helpers\App;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
* MuseumPlus Service
*
* From any other plugin file, call it like this:
*
*     MuseumPlusForCraftCms::$plugin->museumPlus->someMethod()
*
*
* @author    Furbo GmbH
* @package   MuseumPlusForCraftCms
* @since     1.0.0
*/
class MuseumPlusService extends Component
{

    const QUERY_LIMIT = 100;
    const MAX_ITEMS = 10000000;

    private $client = null;

    private $classifier = null;
    private $hostname = null;
    private $requestHeaders = null;

    public function getObjectDetail($objectId)
    {
        $this->init();
        $request = new Request('GET', 'https://'.$this->hostname.'/'.$this->classifier.'/ria-ws/application/module/Object/'.$objectId.'/', $this->requestHeaders);
        return $this->getDetail($request);
    }

    public function getVocabularyNode($groupName, $nodeId)
    {

        $that = $this;
        $cacheKey = Craft::$app->cache->buildKey('museumplus.vocabulary.'.$groupName.'.'.$nodeId);
        $seconds = 24*60*60;
        $tmp = Craft::$app->cache->getOrSet($cacheKey, function ($cache) use ($that, $groupName, $nodeId) {
            $this->init();
            $request = new Request('GET', 'https://'.$this->hostname.'/'.$this->classifier.'/ria-ws/application//vocabulary/instances/'.$groupName.'/nodes/'.$nodeId, $this->requestHeaders);
            $res = $that->client->sendAsync($request)->wait();
            $responseXml = simplexml_load_string($res->getBody()->getContents());

            $terms = json_decode(json_encode($responseXml->terms), true);
            $parents = json_decode(json_encode($responseXml->parents), true);

            $parentId = 0;
            if(!empty($parents)) {
                $parentId = $parents[0]['parent']['@attributes']['nodeId'];
            }

            $ret = [];

            if (isset($terms['term']) && !isset($terms['term']['@attributes'])) {
                $terms = $terms['term'];
            }

            foreach($terms as $term) {
                $object = new \stdClass();
                $object->parentId = $parentId;
                $tmp = json_decode(json_encode($term), true);
                if (isset($tmp['@attributes'])) {
                    foreach ($tmp['@attributes'] as $key => $value) {
                        $object->{$key} = $value;
                    }
                }
                foreach ($tmp as $key => $value) {
                    $object->{$key} = $value;
                }
                $ret[] = $object;
            }
            return $ret;

        });
        return $tmp;
    }



    private function getDetail(Request $request)
    {
        $res = $this->client->sendAsync($request)->wait();
        $responseXml = simplexml_load_string($res->getBody()->getContents());
        return $responseXml->modules->module->moduleItem;
    }

    public function getObjectLastModified($objectId)
    {
        $object = $this->getObjectDetail($objectId);
        try{
            return $object->systemField[2]->value->__toString();
        }catch (\Exception $e){
            return null;
        }
    }

    public function getObjectsByObjectGroup($groupId)
    {

      $this->init();

      $that = $this;
      $cacheKey = Craft::$app->cache->buildKey('museumplus.objects-by-object-group.'.$groupId);
      $seconds = 24*60*60;
      $objects = Craft::$app->cache->getOrSet($cacheKey, function ($cache) use ($that, $groupId) {
          $offset = 0;
          $size = self::MAX_ITEMS;
          $objects = [];

        while ($offset <= $size) {
            $body = '<?xml version="1.0" encoding="UTF-8"?>
                <application xmlns="http://www.zetcom.com/ria/ws/module/search" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.zetcom.com/ria/ws/module/search http://www.zetcom.com/ria/ws/module/search/search_1_1.xsd">
                  <modules>
                  <module name="Object">
                    <search limit="'.self::QUERY_LIMIT.'" offset="'.$offset.'">
                      <expert>
                        <and>
                          <equalsField fieldPath="ObjObjectGroupsRef" operand="'.$groupId.'" />
                        </and>
                      </expert>
                    </search>
                  </module>
                </modules>
                </application>';
            $request = new Request('POST', 'https://'.$that->hostname.'/'.$that->classifier.'/ria-ws/application/module/Object/search/', $that->requestHeaders, $body);
            $res = $that->client->sendAsync($request)->wait();
            $tmp = $that->createDataFromResponse($res);
            foreach($tmp['data'] as $d) {
                $objects[] = $d;
            }
            $size = $tmp['size'];
            $offset += self::QUERY_LIMIT;
            echo "groupId: " . $groupId . " / " . count($objects).' / '.$size." downloaded\n";
        }

        echo count($objects);

        return $objects;
      });

      return $objects;

    }

    public function getAttachmentByObjectId($objectId)
    {
        $this->init();
        try {
            $request = new Request('GET', 'https://' . $this->hostname . '/' . $this->classifier . '/ria-ws/application/module/Object/' . $objectId . '/attachment', $this->requestHeaders);
            return $this->responseFile($request);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getMultimediaById($multimediaId)
    {
        $this->init();
        try {
            $request = new Request('GET', 'https://' . $this->hostname . '/' . $this->classifier . '/ria-ws/application/module/Multimedia/' . $multimediaId . '/attachment', $this->requestHeaders);
            return $this->responseFile($request);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getMultimediaLastModified($multimediaId)
    {
        $this->init();
        try{
            $request = new Request('GET', 'https://' . $this->hostname . '/' . $this->classifier . '/ria-ws/application/module/Multimedia/' . $multimediaId, $this->requestHeaders);
            $object = $this->getDetail($request);
            return $object->systemField[2]->value->__toString();
        }catch (\Exception $e){
            return null;
        }
    }

    private function responseFile(Request $request)
    {
        $res = $this->client->sendAsync($request)->wait();
        $responseXml = simplexml_load_string($res->getBody()->getContents());
        if ($responseXml->modules->module->moduleItem->attachment->attributes()->{"name"}) {
            $fileName = $responseXml->modules->module->moduleItem->attachment->attributes()->{"name"}->__toString();
        } else {
            return false;
        }
        if ($responseXml->modules->module->moduleItem->attachment->value) {
            $base64 = $responseXml->modules->module->moduleItem->attachment->value->__toString();
        } else {
            return false;
        }

        return $this->base64_to_file($base64, $fileName);
    }

    private function base64_to_file($base64_string, $output_file) {
        $output_file = Craft::$app->getPath()->getTempPath()."/".$output_file;
        // open the output file for writing
        $ifp = fopen( $output_file, 'wb' );

        fwrite( $ifp, base64_decode( $base64_string ) );

        // clean up the file resource
        fclose( $ifp );

        return $output_file;
    }

    public function getObjectsByExhibition($exhibitionId)
    {

        dd('sorry, not implpemented');

    }


    public function getObjectGroups()
    {
        $this->init();

        $offset = 0;
        $size = self::MAX_ITEMS;
        $objectGroups = [];

        while ($offset <= $size) {
            $body = '<?xml version="1.0" encoding="UTF-8"?>
                <application xmlns="http://www.zetcom.com/ria/ws/module/search" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.zetcom.com/ria/ws/module/search http://www.zetcom.com/ria/ws/module/search/search_1_1.xsd">
                  <modules>
                    <module name="ObjectGroup">
                      <search limit="'.self::QUERY_LIMIT.'" offset="'.$offset.'">
                        <select>
                          <field fieldPath="__id"/>
                          <field fieldPath="OgrNameTxt"/>
                        </select>
                        <fulltext>*</fulltext>
                      </search>
                    </module>
                  </modules>
                </application>';
            $request = new Request('POST', 'https://'.$this->hostname.'/'.$this->classifier.'/ria-ws/application/module/ObjectGroup/search', $this->requestHeaders, $body);
            $res = $this->client->sendAsync($request)->wait();
            $tmp = $this->createDataFromResponse($res);
            $objectGroups = $objectGroups + $tmp['data'];
            $size = $tmp['size'];
            $offset += self::QUERY_LIMIT;
        }

        //filter out ampty
        $objectGroups = array_filter($objectGroups, function($a) {
            return !empty($a->OgrNameTxt);
        });

        //sort
        usort($objectGroups, function($a, $b) {
             return strcmp($a->OgrNameTxt, $b->OgrNameTxt);
        });
        return $objectGroups;

    }

    public function getExhibitions()
    {

        $this->init();

        $offset = 0;
        $size = self::MAX_ITEMS;
        $objectGroups = [];

        while ($offset <= $size) {
            '<?xml version="1.0" encoding="UTF-8"?>
                <application xmlns="http://www.zetcom.com/ria/ws/module/search" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.zetcom.com/ria/ws/module/search http://www.zetcom.com/ria/ws/module/search/search_1_1.xsd">
                  <modules>
                    <module name="Exhibition">
                      <search limit="'.self::QUERY_LIMIT.'" offset="'.$offset.'">
                        <select>
                          <field fieldPath="__id"/>
                          <field fieldPath="ExhExhibitionTitleVrt"/>
                          <field fieldPath="ExhBeginDateDat"/>
                          <field fieldPath="ExhDateTxt"/>
                        </select>
                        <fulltext>*</fulltext>
                      </search>
                    </module>
                  </modules>
                </application>';
            $request = new Request('POST', 'https://'.$this->hostname.'/'.$this->classifier.'/ria-ws/application/module/Exhibition/search', $this->requestHeaders, $body);
            $res = $this->client->sendAsync($request)->wait();
            $tmp = $this->createDataFromResponse($res);
            $exhibitions = $tmp['data'];
            $size = $tmp['size'];
            $offset += self::QUERY_LIMIT;
        }

        //filter out ampty
        $exhibitions = array_filter($exhibitions, function($a) {
            return !empty($a->ExhExhibitionTitleVrt);
        });

        //sort
        usort($exhibitions, function($a, $b) {
             return strcmp($a->ExhExhibitionTitleVrt, $b->ExhExhibitionTitleVrt);
        });
        return $exhibitions;
    }

    public function getPerson($personId)
    {
        $that = $this;
        $cacheKey = Craft::$app->cache->buildKey('museumplus.people.'.$personId);
        $seconds = 5*60;
        $tmp = Craft::$app->cache->getOrSet($cacheKey, function ($cache) use ($that, $personId) {
            $that->init();
            //normal get is very slow, so do a search to limit fields
            $body = '<?xml version="1.0" encoding="UTF-8"?>
                <application xmlns="http://www.zetcom.com/ria/ws/module/search" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.zetcom.com/ria/ws/module/search http://www.zetcom.com/ria/ws/module/search/search_1_1.xsd">
                  <modules>
                    <module name="Person">
                      <search limit="10" offset="0">
                        <select>
                          <field fieldPath="__id"/>
                          <field fieldPath="__lastModifiedUser"/>
                          <field fieldPath="__lastModified"/>
                          <field fieldPath="__createdUser"/>
                          <field fieldPath="__created"/>
                          <field fieldPath="__orgUnit"/>
                          <field fieldPath="__uuid"/>
                          <field fieldPath="__attachment"/>
                          <field fieldPath="PerDeathDateDat"/>
                          <field fieldPath="PerBirthDateDat"/>
                          <field fieldPath="PerAuthorityIdTxt"/>
                          <field fieldPath="PerDatesExistenceTxt"/>
                          <field fieldPath="PerCorporateBodiesIdTxt"/>
                          <field fieldPath="PerInitialsTxt"/>
                          <field fieldPath="PerGeneralContextClb"/>
                          <field fieldPath="PerAdditionsToNameTxt"/>
                          <field fieldPath="PerLanguageScriptClb"/>
                          <field fieldPath="PerAttributeTxt"/>
                          <field fieldPath="PerInstitutionIdTxt"/>
                          <field fieldPath="PerAwardClb"/>
                          <field fieldPath="PerBiographicalNoteClb"/>
                          <field fieldPath="PerLegalStatusTxt"/>
                          <field fieldPath="PerBirthDateTxt"/>
                          <field fieldPath="PerRuleConventionClb"/>
                          <field fieldPath="PerMandatesClb"/>
                          <field fieldPath="PerCollectionClb"/>
                          <field fieldPath="PerSourceClb"/>
                          <field fieldPath="PerDateFromTxt"/>
                          <field fieldPath="PerSalutationTxt"/>
                          <field fieldPath="PerDateToTxt"/>
                          <field fieldPath="PerDatingTxt"/>
                          <field fieldPath="PerStandardFormNameTxt"/>
                          <field fieldPath="PerDeathDateTxt"/>
                          <field fieldPath="PerDepartmentTxt"/>
                          <field fieldPath="PerStructuresGenealogyClb"/>
                          <field fieldPath="PerExhibitionClb"/>
                          <field fieldPath="PerForeNameTxt"/>
                          <field fieldPath="PerPersonTxt"/>
                          <field fieldPath="PerFoundationPlaceTxt"/>
                          <field fieldPath="PerFunctionTxt"/>
                          <field fieldPath="PerNameTxt"/>
                          <field fieldPath="PerNationalityTxt"/>
                          <field fieldPath="PerNotesClb"/>
                          <field fieldPath="PerOccupationTxt"/>
                          <field fieldPath="PerOrganisationMainBodyTxt"/>
                          <field fieldPath="PerPlaceBirthTxt"/>
                          <field fieldPath="PerPlaceDeathTxt"/>
                          <field fieldPath="PerPlaceTxt"/>
                          <field fieldPath="PerReferenceNumTxt"/>
                          <field fieldPath="PerRightsNotesClb"/>
                          <field fieldPath="PerSchoolStyleTxt"/>
                          <field fieldPath="PerSortNameTxt"/>
                          <field fieldPath="PerSurNameTxt"/>
                          <field fieldPath="PerTitleTxt"/>
                          <field fieldPath="PerNameVrt"/>
                          <field fieldPath="PerBiographicalNoteOnlineVrt"/>
                          <field fieldPath="PerUuidVrt"/>
                          <field fieldPath="PerPersonVrt"/>
                          <field fieldPath="PerURLGrp"/>
                          <field fieldPath="PerGeographyGrp"/>
                          <field fieldPath="PerFunctionsGrp"/>
                          <field fieldPath="PerBiographicalNoteGrp"/>
                          <field fieldPath="PerDateGrp"/>
                          <field fieldPath="PerGroupsGrp"/>
                          <field fieldPath="PerNameOtherGrp"/>
                          <field fieldPath="PerRightsGrp"/>
                        </select>
                        <expert>
                            <and>
                              <equalsField fieldPath="__id" operand="'.$personId.'" />
                            </and>
                            </expert>
                          </search>
                        </module>
                      </modules>
                    </application>';
            $request = new Request('POST', 'https://'.$that->hostname.'/'.$that->classifier.'/ria-ws/application/module/Person/search', $that->requestHeaders, $body);
            $res = $that->client->sendAsync($request)->wait();
            $tmp = $that->createDataFromResponse($res);
            if ($tmp['size'] >= 1)
                return $tmp['data'][0];
            return null;
        }, $seconds);

        return $tmp;
    }

    public function getOwnership($ownershipId)
    {

        $that = $this;
        $cacheKey = Craft::$app->cache->buildKey('museumplus.ownerships.'.$ownershipId);
        $seconds = 5*60;
        $tmp = Craft::$app->cache->getOrSet($cacheKey, function ($cache) use ($that, $ownershipId) {
            $that->init();
            $request = new Request('GET', 'https://'.$that->hostname.'/'.$that->classifier.'/ria-ws/application/module/Ownership/'.$ownershipId.'/', $that->requestHeaders);
            $res = $that->client->sendAsync($request)->wait();
            $tmp = $that->createDataFromResponse($res);
            if ($tmp['size'] >= 1)
                return $tmp['data'][0];
            return null;
        });
        return $tmp;
    }

    public function getLiterature($literatureId)
    {
        $cacheKey = Craft::$app->cache->buildKey('museumplus.literature.'.$literatureId);
        $that = $this;
        $seconds = 5*60;
        $tmp = Craft::$app->cache->getOrSet($cacheKey, function ($cache) use ($that, $literatureId) {
            $that->init();
            $request = new Request('GET', 'https://'.$that->hostname.'/'.$that->classifier.'/ria-ws/application/module/Literature/'.$literatureId.'/', $that->requestHeaders);
            $res = $that->client->sendAsync($request)->wait();
            $tmp = $that->createDataFromResponse($res);
            if ($tmp['size'] >= 1)
                return $tmp['data'][0];
            return null;
        }, $seconds);
    }

    public function init():void {
        parent::init();
        App::maxPowerCaptain();
        if (empty($this->client)) {

            $settings = MuseumPlusForCraftCms::$plugin->getSettings();

            $username = $settings['username'];
            $password = $settings['password'];

            $options = [
                'timeout'  => 300.0,
                'verify' => false,
                'content-type' => 'application/xml',
                'auth' => [$username, $password]
            ];
            $this->client = new Client($options);
            $this->classifier = $settings['classifier'];
            $this->hostname = $settings['hostname'];
            $this->requestHeaders = [];
        }
    }

    private function createDataFromResponse($res) {
        $ret = [
            'size' => 0,
            'data' => []
        ];
        $responseXml = simplexml_load_string($res->getBody()->getContents());
        $totalSize = intval($responseXml->modules->module->attributes()->{'totalSize'}->__toString());
        if (empty($totalSize)) {
            return $ret;
        } else {
            $ret['size'] = $totalSize;
            foreach($responseXml->modules->module->moduleItem as $moduleItem) {
                $obj = $this->createDataObjectFromXML($moduleItem);
                $ret['data'][] = $obj;
            }
            return $ret;
        }
    }

    private function createDataObjectFromXML($xmlObject) {
        $object = new \stdClass();
        $tmp = json_decode(json_encode($xmlObject), true);
        //dd($tmp);
        foreach ($tmp['@attributes'] as $key => $value) {
            $object->{$key} = $value;
        }

        $this->addFieldValuesToObject($object, $tmp, 'systemField');
        $this->addFieldValuesToObject($object, $tmp, 'dataField');
        $this->addFieldValuesToObject($object, $tmp, 'virtualField');
        $this->addVocabularyRefsToObject($object, $tmp);
        $this->addModuleRefsToObject($object, $tmp);

        $this->addRepeatableGroupValuesToObject($object, $tmp);


        //$object->rawData = $xmlObject->asXML();

        return $object;
    }

    private function addFieldValuesToObject(&$obj, $arr, $fieldName, $dd = false) {

        if (isset($arr[$fieldName])) {
            if (isset($arr[$fieldName]['@attributes'])) {
                $sn = $arr[$fieldName]['@attributes']['name'];
                $sv = $arr[$fieldName]['value'];
                $obj->{$sn} = $sv;
            } else {
                foreach ($arr[$fieldName] as $field) {
                    $sn = $field['@attributes']['name'];
                    $sv = $field['value'];
                    $obj->{$sn} = $sv;
                }
            }
        }
    }

    private function addModuleRefsToObject(&$obj, $arr) {
        //set vocabulary refs on object as array
        if (!property_exists($obj, 'moduleReferences'))
            $obj->moduleReferences = [];

        $moduleReferences = $this->extractArrayValues($arr, 'moduleReference');
        if (!empty($moduleReferences)) {

            foreach ($moduleReferences as $field) {
                $mr = new \stdClass();
                $mr->targetModule = $field['@attributes']['targetModule'];
                $mr->name = $field['@attributes']['name'];

                $mr->items = [];

                $mrItems = $field['moduleReferenceItem'];
                if (isset($mrItems['@attributes']))
                    $mrItems = [$field['moduleReferenceItem']];

                foreach($mrItems as $mrItem) {
                    $mri = new \stdClass();
                    $mri->id = $mrItem['@attributes']['moduleItemId'];

                    if (isset($mrItem['formattedValue'])) {
                        $mri->value = $mrItem['formattedValue'];
                        if (isset($mrItem['formattedValue']['@attributes']['language']))
                            $mri->language = $mrItem['formattedValue']['@attributes']['language'];
                    }
                    $mr->items[] = $mri;
                }
                $obj->moduleReferences[$mr->name] = $mr;
            }
        }
    }

    private function addVocabularyRefsToObject(&$obj, $arr) {
        //set vocabulary refs on object as array
        if (!property_exists($obj, 'vocabularyReferences'))
            $obj->vocabularyReferences = [];

        $vocabularyReferences = $this->extractArrayValues($arr, 'vocabularyReference');
        if (!empty($vocabularyReferences)) {

            foreach ($vocabularyReferences as $field) {
                $vr = new \stdClass();
                $vr->name = $field['@attributes']['name'];
                $vr->instanceName = $field['@attributes']['instanceName'];
                $vr->id = $field['@attributes']['id'];
                $vr->items = [];

                $vrItems = $field;
                if (isset($field['vocabularyReferenceItem']))
                    $vrItems = [$field['vocabularyReferenceItem']];

                foreach($vrItems as $vrItem) {
                    $vri = new \stdClass();
                    $vri->id = $vrItem['@attributes']['id'];
                    if (isset($vrItem['@attributes']['name'])){
                        $vri->name = $vrItem['@attributes']['name'];
                    }
                    $vri->value = $vrItem['formattedValue'];
                    if (isset($vrItem['formattedValue']['@attributes']['language']))
                        $vri->language = $vrItem['formattedValue']['@attributes']['language'];
                    $vr->items[] = $vri;
                }

                $obj->vocabularyReferences[] = $vr;
            }
        }
    }

    private function addRepeatableGroupValuesToObject(&$obj, $arr) {

        //set vocabulary refs on object as array
        if (!property_exists($obj, 'repeatableGroups'))
            $obj->repeatableGroups = [];

        $repeatableGroups = $this->extractArrayValues($arr, 'repeatableGroup');
        if (!empty($repeatableGroups)) {

            foreach ($repeatableGroups as $repeatableGroup) {
                $gr = new \stdClass();
                $gr->name = $repeatableGroup['@attributes']['name'];
                $gr->items = [];

                $grItems = [];
                if (isset($repeatableGroup['repeatableGroupItem'])) {
                    $grItems = [$repeatableGroup['repeatableGroupItem']];
                }

                foreach($grItems as $grItem) {
                    $gri = new \stdClass();
                    $groupItemObject = new \stdClass();

                    $this->addFieldValuesToObject($groupItemObject, $grItem, 'systemField');
                    $this->addFieldValuesToObject($groupItemObject, $grItem, 'dataField');
                    $this->addFieldValuesToObject($groupItemObject, $grItem, 'virtualField');

                    $gr->items[] = $groupItemObject;

                }
                $obj->repeatableGroups[] = $gr;


            }
        }
    }

    private function getModuleReferencesByName($arr, $type) {
        $ret = [];
        if (isset($arr['moduleReference'])) {

            if (isset($arr['moduleReference']['@attributes']))
                $tmp = [$arr['moduleReference']];
            else
                $tmp = $arr['moduleReference'];

            foreach ($tmp as $ref) {
                if (isset($ref['@attributes']) && isset($ref['@attributes']['name']) && $ref['@attributes']['name'] == $type) {
                    if (isset($ref['moduleReferenceItem'])){
                        if ($ref['@attributes']['size'] == '1') {
                            if (isset($ref['moduleReferenceItem']) && isset($ref['moduleReferenceItem']['@attributes']['moduleItemId'])) {
                                $id = $ref['moduleReferenceItem']['@attributes']['moduleItemId'];
                                $title = $ref['moduleReferenceItem']['formattedValue'];
                                $ret[$id] = $title;
                            }
                        } else {
                            foreach ($ref['moduleReferenceItem'] as $moduleReferenceItem) {
                                if (isset($moduleReferenceItem['@attributes']) && isset($moduleReferenceItem['@attributes']['moduleItemId'])) {
                                    $id = $moduleReferenceItem['@attributes']['moduleItemId'];
                                    $title = $moduleReferenceItem['formattedValue'];
                                    $ret[$id] = $title;
                                } else {
                                    //dd($moduleReferenceItem);
                                }

                            }
                        }
                    }
                }
            }
        }
        return $ret;
    }

    private function addObjectRelations(&$obj, $arr) {

        // related objects
        if (isset($arr['composite'])) {
            foreach ($arr['composite'] as $composite) {
                $relatedObjects = new \stdClass();
                if (isset($composite['moduleReference'])){
                    foreach ($composite['moduleReference']['moduleReferenceItem'] as $moduleReferenceItem) {
                        if (isset($moduleReferenceItem['@attributes']) && isset($moduleReferenceItem['@attributes']['moduleItemId'])) {
                            $id = $moduleReferenceItem['@attributes']['moduleItemId'];
                            $fv = $moduleReferenceItem['formattedValue'];
                            $relatedObjects->{$id} = $fv;
                        } else {
                            //dd($moduleReferenceItem);
                        }
                    }
                } else {
                    if (isset($composite['name'])) {
                        $cn = $composite['name'];
                        if ($cn == 'ObjObjectCre') {
                            if (isset($composite['compositeItem'])) {
                                foreach ($composite['compositeItem'] as $compositeItem) {
                                    if (isset($compositeItem['moduleReference'])) {
                                        foreach ($compositeItem['moduleReference']['moduleReferenceItem'] as $moduleReferenceItem) {
                                            $id = $moduleReferenceItem['@attributes']['moduleItemId'];
                                            $fv = $moduleReferenceItem['formattedValue'];
                                            $relatedObjects->{$id} = $fv;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $obj->relatedObjects = $relatedObjects;
        }

        // obecjt groups
    }

    private function extractArrayValues(array $array, $needle)
    {
        $iterator  = new \RecursiveArrayIterator($array);
        $recursive = new \RecursiveIteratorIterator($iterator,\RecursiveIteratorIterator::SELF_FIRST);
        $return1 = [];
        foreach ($recursive as $key => $value) {
            if ($key === $needle) {
                $return1[] = $value;
            }
        }

        //strange, sometimes its ann array with 2 domensions, lets flatten
        $return2 = [];
        foreach($return1 as $value) {
            if (!isset($value['@attributes'])) {
                foreach($value as $v) {
                    $return2[] = $v;
                }
            } else {
                $return2[] = $value;
            }

        }
        return $return2;
    }
}
