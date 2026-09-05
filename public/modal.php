<?php
defined('ABSPATH')  || exit;
add_action('wp_footer', 'nias_login_signup_modal');
function nias_login_signup_modal()
{
    if (is_user_logged_in()) {
        return;
    }

    // خالی → پیش‌فرض my-account؛ مقدار واردشده → همان مقدار
    $click_links  = nias_login_trigger_value('nias_login_click_links');
    $locked_pages = nias_login_trigger_value('nias_login_locked_pages');

    // Always render the modal when redirected here by the auth blocker
    // (e.g. from wp-login.php or wp-admin for unauthenticated users).
    if (!empty($_GET['ns-login'])) {
        include(NIAS_LOGIN_VIEW . 'nias_login_modal.php');
        return;
    }

    // Don't include modal if neither triggers are configured
    if (empty($click_links) && empty($locked_pages)) {
        return;
    }

    // Check locked pages
    if (!empty($locked_pages)) {
        $current_url        = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $locked_pages_array = array_map('trim', explode(',', $locked_pages));
        $is_locked_page     = in_array($current_url, $locked_pages_array);

        if (empty($click_links)) {
            if ($is_locked_page) {
                include(NIAS_LOGIN_VIEW . 'nias_login_modal.php');
            }
            return;
        }

        if ($is_locked_page) {
            include(NIAS_LOGIN_VIEW . 'nias_login_modal.php');
            return;
        }
    }

    if (!empty($click_links)) {
        include(NIAS_LOGIN_VIEW . 'nias_login_modal.php');
    }
}

/**
 * Hide the close button and auto-open the modal when the user was
 * redirected here by the auth blocker (ns-login=1 in the URL).
 */
add_action('wp_head', 'nias_login_page_custom_styles');
function nias_login_page_custom_styles()
{
    if (empty($_GET['ns-login'])) {
        return;
    }
?>
    <style>
        button.nias-close-modal {
            display: none !important;
        }
    </style>
<?php
}

// Auto-open the modal as soon as the DOM is ready.
// Runs at priority 99 so it fires after the modal HTML is in the footer.
add_action('wp_footer', 'nias_auto_open_modal_on_redirect', 99);
function nias_auto_open_modal_on_redirect()
{
    if (is_user_logged_in() || empty($_GET['ns-login'])) {
        return;
    }
    ?>
    <script>
    (function () {
        function niasAutoOpen() {
            var box = document.querySelector('.nias-modal-box');
            if (box) { box.classList.add('open'); }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', niasAutoOpen);
        } else {
            niasAutoOpen();
        }
    })();
    </script>
    <?php
}


/* -------------------------------------------------------------------------- */
/*                   افزودن کلاس به body برای صفحات قفل شده                   */
/* -------------------------------------------------------------------------- */
// هنگام ذخیره تنظیمات، URL رو به ID تبدیل کنید
add_filter('body_class', 'nias_add_locked_body_class');
function nias_add_locked_body_class($classes) {
    if (is_user_logged_in()) {
        return $classes;
    }
    // فقط برای صفحات واقعی اجرا بشه، نه برای AJAX یا asset ها
    if (!is_singular() && !is_page()) {
        return $classes;
    }
    
    // خالی → پیش‌فرض my-account؛ مقدار واردشده → همان مقدار
    $locked_pages = nias_login_trigger_value('nias_login_locked_pages');

    if (!empty($locked_pages)) {
        $current_page_id = get_queried_object_id();

        // اگر Page ID صفر بود، برگرد
        if (!$current_page_id) {
            return $classes;
        }

        // اگر آرایه نیست، به آرایه تبدیلش کن
        if (!is_array($locked_pages)) {
            if (is_string($locked_pages)) {
                $locked_pages = array_map('trim', explode(',', $locked_pages));
            } else {
                $locked_pages = array($locked_pages);
            }
        }

        foreach ($locked_pages as $page) {
            $page = trim($page);
            if ($page === '') {
                continue;
            }

            // اگر عدد باشه، شناسه (ID) برگه هست
            if (is_numeric($page)) {
                if ($page == $current_page_id) {
                    $classes[] = 'nias-locked';
                    break;
                }
                continue;
            }

            // اگر لینک کامل باشه (http/https)
            if (stripos($page, 'http') === 0) {
                $page_id = url_to_postid($page);
                if ($page_id && $page_id == $current_page_id) {
                    $classes[] = 'nias-locked';
                    break;
                }
                continue;
            }

            // در غیر این صورت نامک/شناسایی برگه (slug)
            $page_obj = get_page_by_path($page);
            if ($page_obj && $page_obj->ID == $current_page_id) {
                $classes[] = 'nias-locked';
                break;
            }
        }
    }

    return $classes;
}