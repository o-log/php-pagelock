<?php

namespace OLOG\PageLock;

use OLOG\InterfaceAction;

class RefreshPageLockAction implements
    InterfaceAction
{
    public function url(){
        return '/page-lock/refresh';
    }

    public function action()
    {
        if (!array_key_exists("url", $_POST)) {
            echo json_encode(false);
            return;
        }

        $url = $_POST["url"];

        if (PageLock::acquirePageLockByUrlForCurrentAuthapiUser($url)) {
            echo json_encode(true);
        } else {
            echo json_encode(false);
        }
    }
}

