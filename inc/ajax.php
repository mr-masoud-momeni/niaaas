<?php
defined('ABSPATH') || exit;




// Initialize the login handler
Nias_Login_Handler::get_instance();

// Legacy functions for backward compatibility
function nias_kavenegar_lookUp($username, $code, $to, $templateName, $tokensParam) {
    $gateway = new Nias_SMS_Gateway('kavenegar');
    return $gateway->send_code($to, $code);
}

function nias_melipayamakpattern($username, $phone, $melipassword, $code, $bodyId) {
    $gateway = new Nias_SMS_Gateway('national_sms');
    return $gateway->send_code($phone, $code);
}

function nias_farazsendsmsm($from, $to, $user, $pass, $pattern_code, $input_data) {
    $gateway = new Nias_SMS_Gateway('faraz');
    return $gateway->send_code($to[0], $input_data[$pattern_code]);
}

function nias_aladdin($fromNum, $toNum, $Content, $patternID, $Type, $token) {
    $gateway = new Nias_SMS_Gateway('aladdin');
    return $gateway->send_code($toNum[0], json_decode($Content, true));
}

// Legacy functions for IP blocking (for backward compatibility)
function nias_is_ip_blocked($ip) {
    global $wpdb;
    $table = $wpdb->prefix . 'nias_blockedip_sms';

    $blocked_ip = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ip FROM $table WHERE ip = %s",
            $ip
        )
    );

    return !empty($blocked_ip);
}

function nias_should_block_ip($ip) {
    global $wpdb;
    $table = $wpdb->prefix . 'nias_sms_login';

    $last_three_expired_dates = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT expired_at FROM $table WHERE ip = %s ORDER BY expired_at DESC LIMIT 3",
            $ip
        )
    );

    if (count($last_three_expired_dates) < 3) {
        return false;
    }

    $current_time = current_time('timestamp');
    $last_expired_time = strtotime($last_three_expired_dates[0]);
    $oldest_expired_time = strtotime($last_three_expired_dates[2]);

    if ($current_time - $oldest_expired_time > 10) {
        return false;
    }

    $nias_login_expire = get_option('nias_login_expire', 60);
    return $last_expired_time - $oldest_expired_time < $nias_login_expire;
}

function nias_block_ip($ip) {
    global $wpdb;
    $table = $wpdb->prefix . 'nias_blockedip_sms';

    $wpdb->insert(
        $table,
        [
            'ip' => $ip,
            'block' => 'به دلیل تلاش بیش از حد بلاک شد',
            'time' => current_time('mysql'),
        ]
    );
}

// Legacy functions for endpoints (for backward compatibility)
function nias_add_custom_login_endpoint() {
    // This is now handled by the class
}

function nias_add_login_query_var($vars) {
    // This is now handled by the class
    return $vars;
}

function nias_handle_login_request() {
    // This is now handled by the class
}

function nias_sms_login() {
    // This is now handled by the class
}

function nias_sms_verify() {
    // This is now handled by the class
}
