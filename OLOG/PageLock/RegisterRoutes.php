<?php

namespace OLOG\PageLock;

class RegisterRoutes
{
    static public function registerRoutes(){
        \OLOG\Router::processAction(\OLOG\PageLock\RefreshPageLockAction::class, 0);
        UrlHandler::match3('@^/page-lock/list$@', 'PageLock', 'getLockedPagesListAction', 0);
        UrlHandler::match3('@^/page-lock/unlock$@', 'PageLock', 'unlockPageAction', 0);
    }
}