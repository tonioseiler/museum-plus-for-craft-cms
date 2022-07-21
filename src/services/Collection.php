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

    const QUERY_LIMIT = 10000;

    private $client = null;

    private $classifier = null;
    private $hostname = null;
    private $requestHeaders = null;

    public function getObjectDetail($objectId)
    {
        $this->init();
        $request = new Request('GET', 'https://'.$this->hostname.'/'.$this->classifier.'/ria-ws/application/module/Object/'.$objectId.'/', $this->requestHeaders);
        $res = $this->client->sendAsync($request)->wait();
        dd($res->getBody());
    }

    public function getObjectsByObjectGroup($groupId)
    {

        $this->init();

        $body = '<?xml version="1.0" encoding="UTF-8"?>
            <application xmlns="http://www.zetcom.com/ria/ws/module/search" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.zetcom.com/ria/ws/module/search http://www.zetcom.com/ria/ws/module/search/search_1_1.xsd">
              <modules>
              <module name="Object">
                <search limit="'.self::QUERY_LIMIT.'" offset="0">
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
        $objects = $this->createDataFromResponse($res);
        return $objects;
    }

    public function getObjectsByExhibition($exhibitionId)
    {

        dd('sorry, not implpemented');

        $this->init();

        // find exhibition title first
        $exhibitions = $this->getExhibitions();
        dd($exhibitions);
        $title = null;
        foreach ($exhibitions as $exhibition) {
            if ($exhibition->id == $exhibitionId) {
                $title = $exhibition->ExhExhibitionTitleVrt;
            }
        }

        if (empty($title)) {
            return [];
        }

        $body = '<?xml version="1.0" encoding="UTF-8"?>
            <application xmlns="http://www.zetcom.com/ria/ws/module/search" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.zetcom.com/ria/ws/module/search http://www.zetcom.com/ria/ws/module/search/search_1_1.xsd">
              <modules>
              <module name="Object">
                <search limit="'.self::QUERY_LIMIT.'" offset="0">
                  <expert>
                    <and>
                      <equalsField fieldPath="ObjExhibitionsVrt" operand="'.$title.'" />
                    </and>
                  </expert>
                </search>
              </module>
            </modules>
            </application>';
        $request = new Request('POST', 'https://'.$this->hostname.'/'.$this->classifier.'/ria-ws/application/module/Object/search/', $this->requestHeaders, $body);
        $res = $this->client->sendAsync($request)->wait();
        $objects = $this->createDataFromResponse($res);
        return $objects;

    }


    public function getObjectGroups()
    {
        $this->init();

        $body = '<?xml version="1.0" encoding="UTF-8"?>
            <application xmlns="http://www.zetcom.com/ria/ws/module/search" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.zetcom.com/ria/ws/module/search http://www.zetcom.com/ria/ws/module/search/search_1_1.xsd">
              <modules>
                <module name="ObjectGroup">
                  <search limit="'.self::QUERY_LIMIT.'" offset="0">
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

        $objectGroups = $this->createDataFromResponse($res);

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

        $body = '<?xml version="1.0" encoding="UTF-8"?>
            <application xmlns="http://www.zetcom.com/ria/ws/module/search" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.zetcom.com/ria/ws/module/search http://www.zetcom.com/ria/ws/module/search/search_1_1.xsd">
              <modules>
                <module name="Exhibition">
                  <search limit="'.self::QUERY_LIMIT.'" offset="0">
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

        $exhibitions = $this->createDataFromResponse($res);

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
                'timeout'  => 30.0,
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
        $responseXml = simplexml_load_string($res->getBody()->getContents());
        $totalSize = intval($responseXml->modules->module->attributes()->{'totalSize'}->__toString());
        if (empty($totalSize)) {
            return [];
        } else {
            $ret = [];
            foreach($responseXml->modules->module->moduleItem as $moduleItem) {
                $obj = $this->createDataObjectFromXML($moduleItem);
                $ret[] = $obj;
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
}
