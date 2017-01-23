<?php

$locked_page_url_arr = \OLOG\PageLock\PageLock::getPageUrlArrLockedByCurrentAuthapiUser();

// убираем текущий URL
$current_url = \OLOG\Url::getCurrentUrlNoGetForm();
$key = array_search($current_url, $locked_page_url_arr);
if ($key !== false) {
    unset($locked_page_url_arr[$key]);
}

?>
<div id="pageLockListContainer" class="alert alert-info" role="alert" <?php if (empty($locked_page_url_arr)) echo 'style="display:none;"' ?>>
    Сейчас вы блокируете эти страницы:
    <ul id="pageLockList">
        <?php
        foreach ($locked_page_url_arr as $page_url) {
            echo '<li>' . $page_url . '</li>';
        }
        ?>
    </ul>
    <small>Закройте соответствующие вкладки браузера, если они вам не нужны.</small>
</div>

<script>
(function($) {
    var update_lock_list_time_in_second_in_loaded_mode = <?php echo \OLOG\PageLock\PageLock::PAGE_LOCK_EXPIRE_TIME_IN_SECONDS; ?>;
    var update_lock_list_time_in_second_in_lite_mode = update_lock_list_time_in_second_in_loaded_mode * 6;
    var update_lock_list_time_in_second = update_lock_list_time_in_second_in_loaded_mode;
    var update_timer;

    var updatePageLockList = function() {
        $.ajax({
            url: '/page-lock/list',
            dataType: 'json',
            success: function(response) {
                if (Array.isArray(response)) {
                    var pageLockList = $('#pageLockList');
                    var pageLockListContainer = $('#pageLockListContainer');

                    pageLockList.empty();

                    // убираем текущий URL
                    var currentUrlIndex = response.indexOf(location.pathname);
                    if (currentUrlIndex > -1) {
                        response.splice(currentUrlIndex, 1);
                    }

                    if (response.length) {
                        for (var i = 0; i < response.length; i++) {
                            pageLockList.append('<li>' + response[i] + '</li>');
                        }
                        pageLockListContainer.show();
                        //update_lock_list_time_in_second = update_lock_list_time_in_second_in_loaded_mode;
                    } else {
                        pageLockListContainer.hide();
                        //update_lock_list_time_in_second = update_lock_list_time_in_second_in_lite_mode;
                    }
                }

                update_timer = setTimeout(updatePageLockList, (update_lock_list_time_in_second * 1000));
            }
        });
    };

    $(window).focus(function() {
        update_timer = setTimeout(updatePageLockList, (update_lock_list_time_in_second * 1000));
    });

    $(window).blur(function() {
        clearTimeout(update_timer);
    });
})(jQuery);
</script>
