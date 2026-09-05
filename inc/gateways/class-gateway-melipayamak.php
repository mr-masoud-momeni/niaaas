<?php
defined('ABSPATH') || exit;

class Nias_Gateway_Melipayamak extends Nias_Abstract_Gateway
{
    public function send(string $phone, string $template, array $params)
    {
        if (empty($this->settings['username']) || empty($this->settings['api_key'])) {
            return 'نام کاربری یا رمز API ملی پیامک وارد نشده است';
        }

        $response = wp_remote_post('https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body'    => [
                'username' => $this->settings['username'],
                'password' => $this->settings['api_key'],
                'text'     => implode(';', array_values($params)),
                'to'       => $phone,
                'bodyId'   => $template,
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return 'خطا در اتصال به ملی پیامک: ' . $response->get_error_message();
        }

        $status = wp_remote_retrieve_response_code($response);
        if ($status >= 200 && $status < 300) {
            return true;
        }

        return $this->response_error('ملی پیامک', $status, null, wp_remote_retrieve_body($response));
    }

    public function send_text(string $phone, string $text)
    {
        if (empty($this->settings['username']) || empty($this->settings['api_key'])) {
            return 'نام کاربری یا رمز API ملی پیامک وارد نشده است';
        }
        if (empty($this->settings['local_number'])) {
            return 'شماره ارسال‌کننده برای ارسال متن آزاد ملی پیامک وارد نشده است';
        }

        $response = wp_remote_post('https://rest.payamak-panel.com/api/SendSMS/SendSMS', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body'    => [
                'username' => $this->settings['username'],
                'password' => $this->settings['api_key'],
                'to'       => $phone,
                'from'     => $this->settings['local_number'],
                'text'     => $text,
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return 'خطا در اتصال به ملی پیامک: ' . $response->get_error_message();
        }

        $status = wp_remote_retrieve_response_code($response);
        $body   = wp_remote_retrieve_body($response);
        $data   = json_decode($body, true);

        if ($status >= 200 && $status < 300) {
            // RetStatus=1 یعنی موفق؛ اگر ساختار پاسخ ناشناخته بود همان HTTP 2xx را موفق بگیر
            if (!is_array($data) || !isset($data['RetStatus']) || (int) $data['RetStatus'] === 1) {
                return true;
            }
            return 'خطای ملی پیامک: ' . ($data['StrRetStatus'] ?? ('کد ' . $data['RetStatus']));
        }

        return $this->response_error('ملی پیامک', $status, $data, $body);
    }
}
