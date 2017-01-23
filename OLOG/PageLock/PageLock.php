<?php

namespace OLOG\PageLock;

use OLOG\Auth\Auth;
use OLOG\Auth\User;
use OLOG\Cache\CacheWrapper;
use OLOG\Url;

class PageLock {

    const PAGE_LOCK_EXPIRE_TIME_IN_SECONDS = 10;

    /**
     * Обёртка для проверки блокировки страницы, которая в зависимости от проверки добавляет обновлялку блокировки или сообщение о том, что страница используется другим authapi-пользователем
     * Должна вызываться из шаблона!
     *
     * @return bool
     */
    static public function acquirePageLockAndRenderResult()
    {
        $locked_page_url_no_get_params = Url::getCurrentUrlNoGetForm();

        if (!self::acquirePageLockByUrlForCurrentAuthapiUser($locked_page_url_no_get_params))
        {
            PageLock::renderAlertPageLocked();
            return false;
        }

        PageLock::renderRefreshPageLocked();

        return true;
    }

    public static function getLockedPagesListAction()
    {
        $locked_pages_arr = self::getPageUrlArrLockedByCurrentAuthapiUser();

        echo json_encode($locked_pages_arr);
    }

    static public function getNameOfUserLockedPage($locked_page_url_no_get_params = '')
    {
        if ($locked_page_url_no_get_params == '')
        {
            $locked_page_url_no_get_params = Url::getCurrentUrlNoGetForm();
        }

        $cache_key = self::getCacheKey($locked_page_url_no_get_params);

        $editor_user_id = CacheWrapper::get($cache_key);

        if (!$editor_user_id)
        {
            return '';
        }

        $user_obj = User::factory($editor_user_id);
        return 'Пользователь ' . $user_obj->getLogin() . ' (' . $user_obj->getId() . ')';
    }


    static protected function renderAlertPageLocked()
    {
        $page_unlock_permission = Auth::currentUserHasAnyOfPermissions([Permissions::PERMISSION_PAGELOCK_DROP]);

?>
<div class="alert alert-danger" role="alert">
    Эта страница используется другим оператором. <?= self::getNameOfUserLockedPage() ?>
    <button id="pageUnlock" class="btn btn-default" <?php if (!$page_unlock_permission) echo 'disabled="disabled"'; ?>>Разблокировать</button>
</div>

<?php if ($page_unlock_permission) { ?>
    <script>
        $('#pageUnlock').click(function() {
            $.ajax({
                url: '/page-lock/unlock',
                method: 'post',
                data: {
                    url: window.location.pathname
                },
                dataType: 'json',
                success: function(response) {
                    if (response === true) {
                        location.reload();
                    }
                }
            });
        });
    </script>
<?php }

}

    static protected function renderRefreshPageLocked()
    {

    ?>
<script>
    var pageLockInterval = setInterval(
    function(){
        $.ajax({
                url: "/page-lock/refresh",
                type: "POST",
                data: {
            url : window.location.pathname
                },
                dataType: "json",
                success: function(response) {
            if (response === false) {
                clearInterval(pageLockInterval);

                $('body *').prop({ onclick: '' }).unbind('click').click(function(e) {
                    e.preventDefault();
                    return false;
                });

                        $('button, input[type=button], input[type=submit]').prop({ disabled: true });

                        $('form').unbind('submit').submit(function(e) {
                            e.preventDefault();
                            return false;
                        });

                        alert('Эта страница была изменена другим оператором. Сохранение заблокировано.\nЧтобы продолжить работу, скопируйте свои изменения и обновите страницу.');
                    }
        }
            });
        },
        <?= (PageLock::PAGE_LOCK_EXPIRE_TIME_IN_SECONDS - 1) * 1000 ?>
);
</script>
<?php
    }

    /*
    static public function renderListPageLocked()
    {
        return Render::template2('page_lock_list.tpl.php');
    }
    */

    /**
     * Пытается зарезервировать использование страницы (по переданному урлу) за текущим пользователем
     * При успешном резервировании возвращает true
     * Если использование страницы уже зарезервировано другим пользователем - возвращает false
     *
     * @param string $locked_page_url_no_get_params - относительный путь страницы без гет параметров
     * @return bool
     */
    static public function acquirePageLockByUrlForCurrentAuthapiUser($locked_page_url_no_get_params)
    {
        $cache_key = self::getCacheKey($locked_page_url_no_get_params);

        /** @var User $current_user_obj */
        $current_user_obj = Auth::currentUserObj();
        if (!$current_user_obj)
        {
            return false;
        }

        $editor_user_id = CacheWrapper::get($cache_key);

        if (
            $editor_user_id
            && ($editor_user_id != $current_user_obj->getId())
        )
        {
            return false;
        }

        CacheWrapper::set($cache_key, $current_user_obj->getId(), self::PAGE_LOCK_EXPIRE_TIME_IN_SECONDS);

        self::addPageUrlLockedByUser($current_user_obj->getId(), $locked_page_url_no_get_params);

        return true;
    }

    static public function getCacheKey($key)
    {
        return '__PAGELOCK__' . md5($key);
    }

    /**
     * Получить список страниц, заблокированных текущим authapi-пользователем
     *
     * @return array
     */
    public static function getPageUrlArrLockedByCurrentAuthapiUser()
    {
        /** @var User $current_user_obj */
        $current_user_obj = Auth::currentUserObj();
        if (!$current_user_obj) {
            return array();
        }

        return self::getPageUrlArrLockedByUser($current_user_obj->getId());
    }

    /**
     * Получить список страниц, заблокированных указанным пользователем
     *
     * @param $user_id
     * @return array
     */
    public static function getPageUrlArrLockedByUser($user_id)
    {
        $page_url_arr_cache_key = self::getPageUrlArrLockedByUserCacheKey($user_id);

        $locked_page_url_arr = CacheWrapper::get($page_url_arr_cache_key);
        if (!is_array($locked_page_url_arr)) {
            $locked_page_url_arr = array();
        }

        foreach ($locked_page_url_arr as $key => $page_url) {
            $cache_key = self::getCacheKey($page_url);
            $editor_user_id = CacheWrapper::get($cache_key);

            if ($editor_user_id != $user_id) {
                unset($locked_page_url_arr[$key]);
            }
        }

        return array_values($locked_page_url_arr);
    }

    /**
     * Добавить URL в список страниц заблокированных указанным пользователем
     *
     * @param $user_id
     * @param $locked_page_url_no_get_params
     */
    protected static function addPageUrlLockedByUser($user_id, $locked_page_url_no_get_params)
    {
        $locked_page_url_arr = self::getPageUrlArrLockedByUser($user_id);

        if (!in_array($locked_page_url_no_get_params, $locked_page_url_arr)) {
            $locked_page_url_arr[] = $locked_page_url_no_get_params;
        }

        $locked_page_url_arr_cache_key = self::getPageUrlArrLockedByUserCacheKey($user_id);
        CacheWrapper::set($locked_page_url_arr_cache_key, $locked_page_url_arr, self::PAGE_LOCK_EXPIRE_TIME_IN_SECONDS * 3);
    }

    /**
     * Удалить URL из списка страниц заблокированных указанным пользователем
     *
     * @param $user_id
     * @param $locked_page_url_no_get_params
     */
    public static function removePageUrlLockedByUser($user_id, $locked_page_url_no_get_params)
    {
        $locked_page_url_arr = self::getPageUrlArrLockedByUser($user_id);

        $key = array_search($locked_page_url_no_get_params, $locked_page_url_arr);
        if ($key !== false) {
            unset($locked_page_url_arr[$key]);

            $locked_page_url_arr_cache_key = self::getPageUrlArrLockedByUserCacheKey($user_id);
            CacheWrapper::set($locked_page_url_arr_cache_key, $locked_page_url_arr, self::PAGE_LOCK_EXPIRE_TIME_IN_SECONDS * 3);
        }
    }

    /**
     * Получить ключ кэша для списка страниц, заблокированных указанным пользователем
     *
     * @param $user_id
     * @return string
     */
    protected static function getPageUrlArrLockedByUserCacheKey($user_id)
    {
        return '__PAGELOCK__UID_' . $user_id;
    }
}