<?php
defined('ABSPATH') || exit;

function nias_get_table_charset_collate()
{
    global $wpdb;

    $charset_collate = trim($wpdb->get_charset_collate());
    if ($charset_collate !== '') {
        return $charset_collate;
    }

    $charset = !empty($wpdb->charset) ? $wpdb->charset : (defined('DB_CHARSET') && DB_CHARSET ? DB_CHARSET : 'utf8mb4');
    $collate = !empty($wpdb->collate) ? $wpdb->collate : (defined('DB_COLLATE') && DB_COLLATE ? DB_COLLATE : '');

    $charset_collate = 'DEFAULT CHARSET=' . $charset;
    if ($collate !== '') {
        $charset_collate .= ' COLLATE=' . $collate;
    }

    return $charset_collate;
}

/**
 * ایجاد جدول اصلی SMS/Email لاگین با فیلدهای بهبود یافته
 */
function nias_sms_login_activation()
{
    global $wpdb;

    $table = $wpdb->prefix . 'nias_sms_login';
    $charset_collate = nias_get_table_charset_collate();

    $sql = "
    CREATE TABLE `$table` (
        `ID` bigint unsigned NOT NULL AUTO_INCREMENT,
        `user_id` bigint unsigned NOT NULL DEFAULT '0',
        `phone` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
        `attempts` int unsigned NOT NULL DEFAULT '0',
        `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
        `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
        `user_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'unknown',
        `send_to_phone` tinyint(1) NOT NULL DEFAULT '0',
        `send_to_email` tinyint(1) NOT NULL DEFAULT '0',
        `verified` tinyint(1) NOT NULL DEFAULT '0',
        `created_at` datetime NOT NULL,
        `updated_at` datetime DEFAULT NULL,
        `verified_at` datetime DEFAULT NULL,
        `expired_at` datetime NOT NULL,
        PRIMARY KEY (`ID`),
        KEY `user_id` (`user_id`),
        KEY `phone` (`phone`),
        KEY `email` (`email`),
        KEY `status` (`status`),
        KEY `user_status` (`user_status`),
        KEY `verified` (`verified`),
        KEY `ip` (`ip`),
        KEY `expired_at` (`expired_at`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * ایجاد جدول IP های مسدود شده
 */
function nias_blockedip_sms_activation()
{
    global $wpdb;

    $table = $wpdb->prefix . 'nias_blockedip_sms';
    $charset_collate = nias_get_table_charset_collate();

    $sql = "
    CREATE TABLE `$table` (
        `ID` bigint unsigned NOT NULL AUTO_INCREMENT,
        `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
        `block_reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'تلاش بیش از حد',
        `block` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
        `blocked_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'system',
        `unblock_at` datetime DEFAULT NULL,
        `is_permanent` tinyint(1) NOT NULL DEFAULT '0',
        `time` datetime NOT NULL,
        `updated_at` datetime DEFAULT NULL,
        PRIMARY KEY (`ID`),
        UNIQUE KEY `ip_unique` (`ip`),
        KEY `time` (`time`),
        KEY `is_permanent` (`is_permanent`),
        KEY `unblock_at` (`unblock_at`)
    ) ENGINE=InnoDB $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * ایجاد جدول لاگ امنیتی
 */
function nias_security_log_activation()
{
    global $wpdb;

    $table = $wpdb->prefix . 'nias_security_log';
    $charset_collate = nias_get_table_charset_collate();

    $sql = "
    CREATE TABLE `$table` (
        `ID` bigint unsigned NOT NULL AUTO_INCREMENT,
        `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
        `identifier` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `user_id` bigint unsigned DEFAULT NULL,
        `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
        `user_agent` text COLLATE utf8mb4_unicode_ci,
        `details` text COLLATE utf8mb4_unicode_ci,
        `severity` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'info',
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`ID`),
        KEY `event_type` (`event_type`),
        KEY `identifier` (`identifier`),
        KEY `user_id` (`user_id`),
        KEY `ip_address` (`ip_address`),
        KEY `severity` (`severity`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * ایجاد جدول آمار و گزارشات
 */
function nias_statistics_activation()
{
    global $wpdb;

    $table = $wpdb->prefix . 'nias_statistics';
    $charset_collate = nias_get_table_charset_collate();

    $sql = "
    CREATE TABLE `$table` (
        `ID` bigint unsigned NOT NULL AUTO_INCREMENT,
        `stat_date` date NOT NULL,
        `stat_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
        `stat_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
        `stat_value` bigint unsigned NOT NULL DEFAULT '0',
        `additional_data` json DEFAULT NULL,
        `created_at` datetime NOT NULL,
        `updated_at` datetime DEFAULT NULL,
        PRIMARY KEY (`ID`),
        UNIQUE KEY `date_type_key_unique` (`stat_date`, `stat_type`, `stat_key`),
        KEY `stat_date` (`stat_date`),
        KEY `stat_type` (`stat_type`),
        KEY `stat_key` (`stat_key`)
    ) ENGINE=InnoDB $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * اجرای تمام فانکشن‌های ایجاد جدول
 */
function nias_create_all_tables()
{
    nias_sms_login_activation();
    nias_blockedip_sms_activation();
    nias_security_log_activation();
    nias_statistics_activation();
}

/**
 * حذف تمام جداول (برای غیرفعال‌سازی)
 */
function nias_drop_all_tables()
{
    global $wpdb;
    
    $tables = [
        $wpdb->prefix . 'nias_sms_login',
        $wpdb->prefix . 'nias_blockedip_sms',
        $wpdb->prefix . 'nias_security_log',
        $wpdb->prefix . 'nias_statistics'
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

/**
 * به‌روزرسانی جداول (برای ورژن‌های جدید)
 */
function nias_upgrade_tables($old_version, $new_version)
{
    global $wpdb;
    
    // مثال: اضافه کردن ستون جدید در ورژن 2.0
    if (version_compare($old_version, NIAS_LOGIN_VERSION, '<')) {
        $table = $wpdb->prefix . 'nias_sms_login';
        
        // بررسی وجود ستون
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'user_status'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN `user_status` varchar(20) DEFAULT 'unknown' AFTER `status`");
            $wpdb->query("ALTER TABLE $table ADD COLUMN `send_to_phone` tinyint(1) NOT NULL DEFAULT '0' AFTER `user_status`");
            $wpdb->query("ALTER TABLE $table ADD COLUMN `send_to_email` tinyint(1) NOT NULL DEFAULT '0' AFTER `send_to_phone`");
            $wpdb->query("ALTER TABLE $table ADD COLUMN `verified` tinyint(1) NOT NULL DEFAULT '0' AFTER `send_to_email`");
            $wpdb->query("ALTER TABLE $table ADD COLUMN `verified_at` datetime DEFAULT NULL AFTER `updated_at`");
        }
    }
}

/**
 * بررسی سلامت دیتابیس
 */
function nias_check_database_health()
{
    global $wpdb;
    
    $tables = [
        $wpdb->prefix . 'nias_sms_login',
        $wpdb->prefix . 'nias_blockedip_sms',
        $wpdb->prefix . 'nias_security_log',
        $wpdb->prefix . 'nias_statistics'
    ];
    
    $missing_tables = [];
    
    foreach ($tables as $table) {
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if ($table_exists != $table) {
            $missing_tables[] = $table;
        }
    }
    
    return [
        'healthy' => empty($missing_tables),
        'missing_tables' => $missing_tables,
        'total_tables' => count($tables),
        'existing_tables' => count($tables) - count($missing_tables)
    ];
}
