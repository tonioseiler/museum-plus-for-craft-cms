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

use furbo\museumplusforcraftcms\MuseumplusForCraftcms;
use furbo\museumplusforcraftcms\elements\MuseumplusItem;

use Craft;
use craft\base\Component;
use craft\helpers\App;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
* Collection Service
*
* From any other plugin file, call it like this:
*
*     MuseumplusForCraftcms::$plugin->collection->someMethod()
*
*
* @author    Furbo GmbH
* @package   MuseumplusForCraftcms
* @since     1.0.0
*/
class Collection extends Component
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
        $res = $this->client->sendAsync($request)->wait();
        //dd($res->getBody());
    }

    public function getObjectsByObjectGroup($groupId)
    {

      $this->init();
      
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
            $request = new Request('POST', 'https://'.$this->hostname.'/'.$this->classifier.'/ria-ws/application/module/Object/search/', $this->requestHeaders, $body);
            $res = $this->client->sendAsync($request)->wait();
            $tmp = $this->createDataFromResponse($res);
            foreach($tmp['data'] as $d) {
                $objects[] = $d;
            }
            $size = $tmp['size'];
            $offset += self::QUERY_LIMIT;
            echo "groupId: " . $groupId . " / " . count($objects).' / '.$size." downloaded\n";
        }

        echo count($objects);

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

    public function init():void {
        parent::init();
        App::maxPowerCaptain();
        if (empty($this->client)) {

            $settings = MuseumplusForCraftcms::$plugin->getSettings();

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
        foreach ($tmp['@attributes'] as $key => $value) {
            $object->{$key} = $value;
        }

        $this->addFieldValuesToObject($object, $tmp, 'systemField');
        $this->addFieldValuesToObject($object, $tmp, 'dataField');
        $this->addFieldValuesToObject($object, $tmp, 'virtualField');
        $this->addVocabularyValuesToObject($object, $tmp);
        $this->addRepeatableGroupValuesToObject($object, $tmp);
        $this->addMultimediaReferences($object, $tmp);
        $this->addObjectGroupReferences($object, $tmp);
        $this->addLiteratureReferences($object, $tmp);
        $this->addObjectRelations($object, $tmp);

        //$object->rawData = $xmlObject->asXML();

        return $object;
    }

    private function addFieldValuesToObject(&$obj, $arr, $fieldName) {
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

    private function addVocabularyValuesToObject(&$obj, $arr) {
        if (isset($arr['vocabularyReference'])) {
            foreach ($arr['vocabularyReference'] as $field) {
                $sn = $field['@attributes']['name'];
                $sv = $field['vocabularyReferenceItem']['formattedValue'];
                $obj->{$sn} = $sv;
            }
        }
    }

    private function addRepeatableGroupValuesToObject(&$obj, $arr) {
        if (isset($arr['repeatableGroup'])) {
            foreach ($arr['repeatableGroup'] as $group) {
                $gn = $group['@attributes']['name'];
                $groupValues = [];
                foreach ($group['repeatableGroupItem'] as $groupItem) {
                    $groupItemObject = new \stdClass();

                    $this->addFieldValuesToObject($groupItemObject, $groupItem, 'systemField');
                    $this->addFieldValuesToObject($groupItemObject, $groupItem, 'dataField');
                    $this->addFieldValuesToObject($groupItemObject, $groupItem, 'virtualField');
                    if ($groupItemObject != new \stdClass())
                        $groupValues[] = $groupItemObject;
                }
                if (!empty($groupValues))
                    $obj->{$gn} = $groupValues;
            }
        }
    }

    private function getModuleReferencesByName($arr, $type) {
        $ret = [];
        if (isset($arr['moduleReference'])) {
            foreach ($arr['moduleReference'] as $ref) {
                if ($ref['@attributes']['name'] == $type) {
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


    private function addMultimediaReferences(&$obj, $arr) {
        $obj->multiMediaObjects = $this->getModuleReferencesByName($arr, 'ObjMultimediaRef');

        //neu
        // - objekte in der zwischentabelle löschen mit diesem name
        // - bei bedarf referenzobjekte erstellen (inkl typ) und eintrag in zwischentabelle machen.
        // - am schluss muss noch überprüft werden, welche referenzobjekt gar keinen Eintrag mehr in der pivot tabelle haben.
        

    }

    private function addObjectGroupReferences(&$obj, $arr) {
        $obj->objectGroups = $this->getModuleReferencesByName($arr, 'ObjObjectGroupsRef');
    }

    private function addLiteratureReferences(&$obj, $arr) {
        $obj->literature = $this->getModuleReferencesByName($arr, 'ObjLiteratureRef');
    }
}
