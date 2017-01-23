<?php

require_once "../vendor/autoload.php";

\PageLockDemo\PageLockDemoInitConfig::initConfig();

\OLOG\Auth\RegisterRoutes::registerRoutes();
\OLOG\PageLock\RegisterRoutes::registerRoutes();

\OLOG\Router::processAction(\PageLockDemo\Pages\MainPageAction::class, 0);

