<?php
/**
 * Page Cache plugin for Craft CMS 3.x
 *
 * This plugin utilizes the PageCache filter from yii2 into your craft 3 instance
 *
 * @link      https://cstudios.sk
 * @copyright Copyright (c) 2020 Gergely Horvath
 */

namespace cstudios\pagecache;


use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\App;
use craft\services\Plugins;
use craft\events\PluginEvent;

use craft\web\Application;
use craft\web\UrlManager;
use craft\web\View;
use cstudios\pagecache\controllers\SettingsController;
use cstudios\pagecache\models\Settings;
use yii\base\Event;

/**
 * Class PageCache
 *
 * @author    Gergely Horvath
 * @package   PageCache
 * @since     1.0.0
 *
 */
class PageCache extends Plugin
{
    /**
     * The default cache config path
     */
    const CACHE_CONFIG_PATH = CRAFT_BASE_PATH . '/page-cache.json';

    /**
     * @var PageCache
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    public $id = 'page-cache';

    public $handle = 'page-cache';

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * @var bool
     */
    public $hasCpSection = false;

    public $controllerMap = [
        'settings' => 'cstudios\pagecache\controllers\SettingsController'
    ];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'cstudios\\pagecache\\console\\controllers';
        } else {
            $this->controllerNamespace = 'cstudios\\pagecache\\controllers';
        }

        Event::on(
            Application::class,
            Application::EVENT_BEFORE_ACTION,
            function ($event){

                $enabled = $this->getSettings()->enabled;
                $cacheVersion = $this->getSettings()->cacheVersion;
                $durationInMinutes = $this->getSettings()->durationInMinutes;

                if ($enabled && !Craft::$app->request->isCpRequest){
                    Craft::$app->controller->attachBehavior('pagecache',[
                        'class' => 'yii\filters\PageCache',
                        'duration' => $durationInMinutes*60,
                        'variations' => [
                            $cacheVersion,
                            Craft::$app->request->fullPath,
                            Craft::$app->request->get(),
                        ],
                    ]);
                }


            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event){
                $event->rules['page-cache/<controller>/<action>'] = 'page-cache/<controller>/<action>';
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    if (!$this->createCacheConfig())
                        throw new \Exception('We could not create the config for some reason');
                }
            }
        );

        Craft::info(
            Craft::t(
                'page-cache',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * @return bool|Settings|null
     */
    public function getSettings()
    {
        return parent::getSettings();
    }

    /**
     * Checks if the cache config exists, and generates it if not
     * @return bool|false|int
     */
    protected function createCacheConfig()
    {
        if (!file_exists(self::CACHE_CONFIG_PATH)){
            $data = json_encode($this->getSettings());
            return file_put_contents(self::CACHE_CONFIG_PATH,$data);
        }

        return true;
    }

    protected function createSettingsModel()
    {
        return new Settings();
    }

    protected function settingsHtml()
    {
        return \Craft::$app->getView()->renderTemplate('page-cache/settings', [
            'settings' => $this->getSettings()
        ]);
    }

}
