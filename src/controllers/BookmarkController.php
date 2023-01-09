<?php

namespace furbo\museumplusforcraftcms\controllers;

use Craft;
use \craft\web\Controller;


class BookmarkController extends Controller
{
    protected array|int|bool $allowAnonymous = ['check', 'save'];

    public function actionCheck($objectId = null)
    {
        $bookmarks = Craft::$app->getSession()->get('bookmarks', []);
        return $this->asJson(in_array($objectId, $bookmarks));
    }
    public function actionSave()
    {
        $this->requirePostRequest();
        $params = \Craft::$app->getRequest()->getBodyParams();
        if(Craft::$app->session->has('bookmarks')) {
            $bookmarks = Craft::$app->session->get('bookmarks');
            if(!in_array($params["objectId"], $bookmarks)) {
                $bookmarks[] = $params["objectId"];
            }else{
                $key = array_search($params["objectId"], $bookmarks);
                unset($bookmarks[$key]);
            }
        } else {
            $bookmarks = [$params["objectId"]];
        }
        Craft::$app->session->set('bookmarks', $bookmarks);
        return $this->asJson($bookmarks);
    }
}