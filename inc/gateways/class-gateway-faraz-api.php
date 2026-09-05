<?php
defined('ABSPATH') || exit;

class Nias_Gateway_Faraz_Api extends Nias_Abstract_Gateway
{
    public function send(string $phone, string $template, array $params)
    {
        if (empty($this->settings['api_key']) || empty($this->settings['local_number'])) {
            return 'API Key یا شماره خط فراز جدید وارد نشده است';
        }

        $response = wp_remote_post('https://api.iranpayamak.com/ws/v1/sms/pattern', [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
                'Api-Key'      => $this->settings['api_key'],
            ],
            'body'    => wp_json_encode([
                'code'          => $template,
                'attributes'    => $params,
                'recipient'     => $phone,
                'line_number'   => $this->settings['local_number'],
                'number_format' => 'english',
            ], JSON_UNESCAPED_UNICODE),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return 'خطا در اتصال به فراز جدید: ' . $response->get_error_message();
        }

        $status = wp_remote_retrieve_response_code($response);
        $body   = wp_remote_retrieve_body($response);
        $data   = json_decode($body, true);

        if ($status >= 200 && $status < 300) {
            return true;
        }

        return $this->response_error('فراز جدید', $status, $data, $body);
    }

    public function send_text(string $phone, string $text)
    {
        if (empty($this->settings['api_key']) || empty($this->settings['local_number'])) {
            return 'API Key یا شماره خط فراز جدید وارد نشده است';
        }

        $response = wp_remote_post('https://api.iranpayamak.com/ws/v1/sms/send', [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
                'Api-Key'      => $this->settings['api_key'],
            ],
            'body'    => wp_json_encode([
                'message'       => $text,
                'recipients'    => [$phone],
                'line_number'   => $this->settings['local_number'],
                'number_format' => 'english',
            ], JSON_UNESCAPED_UNICODE),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return 'خطا در اتصال به فراز جدید: ' . $response->get_error_message();
        }

        $status = wp_remote_retrieve_response_code($response);
        $body   = wp_remote_retrieve_body($response);
        $data   = json_decode($body, true);

        if ($status >= 200 && $status < 300) {
            return true;
        }

        return $this->response_error('فراز جدید', $status, $data, $body);
    }
}
