<?php
// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

global $wpdb;

// حذف جداول افزونه — فقط هنگام «حذف» افزونه، نه غیرفعال‌سازی.
// هر چهار جدولی که nias_create_all_tables() می‌سازد اینجا هم پاک می‌شوند تا
// چیزی در دیتابیس جا نماند.
$nias_tables = array(
    $wpdb->prefix . 'nias_sms_login',
    $wpdb->prefix . 'nias_blockedip_sms',
    $wpdb->prefix . 'nias_security_log',
    $wpdb->prefix . 'nias_statistics',
);

foreach ( $nias_tables as $nias_table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$nias_table}" );
}

// Unschedule the event
wp_unschedule_event( wp_next_scheduled( 'nias_login_plugin_event' ), 'nias_login_plugin_event' );

// Unschedule license check cron
$license_cron_hook = 'nlmw_nias-login-plugin_license_check';
wp_unschedule_event( wp_next_scheduled( $license_cron_hook ), $license_cron_hook );
