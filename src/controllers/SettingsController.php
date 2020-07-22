<?php

namespace cstudios\pagecache\controllers;

use cstudios\pagecache\PageCache;
use craft\helpers\ArrayHelper;
use craft\web\Controller;

class SettingsController extends Controller
{

    public function actionIncreaseCacheVersion()
    {
        $plugin = PageCache::$plugin;
        $settings = $plugin->getSettings();
        $settings->cacheVersion++;

        \Craft::$app->plugins->savePluginSettings($plugin,ArrayHelper::toArray($settings));
        return $this->goBack(\Craft::$app->request->referrer);
    }
}