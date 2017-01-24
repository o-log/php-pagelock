<?php

namespace PageLockDemo;

use OLOG\Auth\AuthConfig;
use OLOG\Auth\AuthConstants;
use OLOG\BT\LayoutBootstrap;
use OLOG\Cache\CacheConfig;
use OLOG\Cache\MemcacheServerSettings;
use OLOG\DB\DBConfig;
use OLOG\DB\DBSettings;
use OLOG\Layouts\LayoutsConfig;

class PageLockDemoInitConfig
{
    static public function initConfig(){
        header('Content-Type: text/html; charset=utf-8');
        date_default_timezone_set('Europe/Moscow');

        DBConfig::setDBSettingsObj(
            AuthConstants::DB_NAME_PHPAUTH,
            new DBSettings('localhost', 'db_pagelock', 'root', '1', 'vendor/o-log/php-pagelock/db_pagelock.sql')
        );

        CacheConfig::addServerSettingsObj(new MemcacheServerSettings('localhost', 11211));

        AuthConfig::setFullAccessCookieName('lkjdhfglkjdsgf');

        LayoutsConfig::setAdminLayoutClassName(LayoutBootstrap::class);
    }
}