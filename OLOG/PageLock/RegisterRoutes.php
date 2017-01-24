<?php

namespace OLOG\PageLock;

class RegisterRoutes
{
    static public function registerRoutes(){
        \OLOG\Router::processAction(RefreshPageLockAction::class, 0);
        \OLOG\Router::processAction(UnlockPageAction::class, 0);
    }
}