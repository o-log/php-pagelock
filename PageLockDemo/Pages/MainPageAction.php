<?php

namespace PageLockDemo\Pages;

use OLOG\InterfaceAction;
use OLOG\Layouts\AdminLayoutSelector;
use OLOG\Layouts\InterfacePageTitle;
use OLOG\PageLock\PageLock;

class MainPageAction implements
    InterfaceAction,
    InterfacePageTitle
{
	public function url()
	{
		return "/";
	}

	public function pageTitle()
	{
		return 'pagelock demo';
	}

	public function action()
	{
        ob_start();
        if (!PageLock::acquirePageLockAndRenderResult()) {
            // страница уже заблокирована другим пользователем
            $page_lock_html = ob_get_clean();
            AdminLayoutSelector::render($page_lock_html);
            return;
        }
        // текущий пользователь заблокировал страницу.
        $page_lock_html = ob_get_clean();


		AdminLayoutSelector::render($page_lock_html);
	}
}