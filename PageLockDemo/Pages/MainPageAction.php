<?php

namespace PageLockDemo\Pages;

use OLOG\HTML;
use OLOG\InterfaceAction;
use OLOG\Layouts\AdminLayoutSelector;
use OLOG\Layouts\InterfacePageTitle;

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
		$html = '';

		// ...

		AdminLayoutSelector::render($html);
	}
}