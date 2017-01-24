<?php

namespace OLOG\PageLock;

use OLOG\Auth\Auth;
use OLOG\Cache\CacheWrapper;
use OLOG\Exits;
use OLOG\InterfaceAction;
use OLOG\PageLock\PageLock;
use OLOG\PageLock\Permissions;

class UnlockPageAction implements InterfaceAction
{
    public function url()
    {
        return '/page-lock/unlock';
    }

    public function action() {
        Exits::exit403If(!Auth::currentUserHasAnyOfPermissions([Permissions::PERMISSION_PAGELOCK_DROP]));

        // TODO: rewrite with postaccess
        if (!array_key_exists("url", $_POST)) {
            echo json_encode(false);
            return;
        }

        $url = $_POST["url"];

        $cache_key = PageLock::getCacheKey($url);

        $editor_user_id = CacheWrapper::get($cache_key);

        CacheWrapper::delete($cache_key);

        PageLock::removePageUrlLockedByUser($editor_user_id, $url);

        if (PageLock::acquirePageLockByUrlForCurrentAuthapiUser($url)) {
            echo json_encode(true);
        } else {
            echo json_encode(false);
        }
    }
}