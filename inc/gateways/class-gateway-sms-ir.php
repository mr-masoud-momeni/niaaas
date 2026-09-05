<?php
defined('ABSPATH') || exit;

class Nias_Gateway_Sms_Ir extends Nias_Abstract_Gateway
{
    public function send(string $phone, string $template, array $params)
    {
        if (empty($this->settings['api_key'])) {
            return 'API Key پیامک SMS.ir وارد نشده است';
        }

        $parameters = [];
        foreach ($params as $name => $value) {
            $parameters[] = ['name' => $name, 'value' => $value];
        }

        $response = wp_remote_post('https://api.sms.ir/v1/send/verify', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'text/plain',
                'x-api-key'    => $this->settings['api_key'],
            ],
            'body'    => wp_json_encode([
                'mobile'     => $phone,
                'templateId' => (int) $template,
                'parameters' => $parameters,
            ], JSON_UNESCAPED_UNICODE),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return 'خطا در اتصال به SMS.ir: ' . $response->get_error_message();
        }

        $status = wp_remote_retrieve_response_code($response);
        $body   = wp_remote_retrieve_body($response);
        $data   = json_decode($body, true);

        if ($status === 200 || $status === 201) {
            return true;
        }

        return $this->response_error('SMS.ir', $status, $data, $body);
    }

    public function send_text(string $phone, string $text)
    {
        if (empty($this->settings['api_key'])) {
            return 'API Key پیامک SMS.ir وارد نشده است';
        }
        if (empty($this->settings['local_number'])) {
            return 'شماره خط (lineNumber) برای ارسال متن آزاد SMS.ir وارد نشده است';
        }

        $response = wp_remote_post('https://api.sms.ir/v1/send/bulk', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'text/plain',
                'x-api-key'    => $this->settings['api_key'],
            ],
            'body'    => wp_json_encode([
                'lineNumber'  => (int) $this->settings['local_number'],
                'messageText' => $text,
                'mobiles'     => [$phone],
            ], JSON_UNESCAPED_UNICODE),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return 'خطا در اتصال به SMS.ir: ' . $response->get_error_message();
        }

        $status = wp_remote_retrieve_response_code($response);
        $body   = wp_remote_retrieve_body($response);
        $data   = json_decode($body, true);

        if ($status === 200 || $status === 201) {
            return true;
        }

        return $this->response_error('SMS.ir', $status, $data, $body);
    }
}
