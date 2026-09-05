<?php
defined('ABSPATH') || exit;

class Nias_Gateway_Bale extends Nias_Abstract_Gateway
{
    /**
     * Send an OTP via Bale messenger.
     *
     * Note: $template is unused for Bale (it uses a fixed OTP endpoint),
     * and $params must contain a 'code' key with the OTP value.
     * This signature stays compatible with the abstract contract while
     * also supporting the legacy send_bale($phone, $code) call pattern
     * through Nias_SMS_Gateway::send_bale().
     */
    public function send(string $phone, string $template, array $params)
    {
        $api_key = $this->settings['nias_bale_api'];
        $bot_id  = $this->settings['nias_bale_botid'];

        if (empty($api_key) || empty($bot_id)) {
            return 'تنظیمات بله وارد نشده است';
        }

        $code = $params['code'] ?? reset($params);

        $response = wp_remote_post('https://safir.bale.ai/api/v3/send_message', [
            'headers' => [
                'api-access-key' => $api_key,
                'Content-Type'   => 'application/json',
            ],
            'body'    => wp_json_encode([
                'bot_id'       => (int) $bot_id,
                'phone_number' => '98' . ltrim($phone, '0'),
                'message_data' => ['otp_message' => ['otp' => $code]],
            ], JSON_UNESCAPED_UNICODE),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return 'خطا در اتصال به بله: ' . $response->get_error_message();
        }

        $status = wp_remote_retrieve_response_code($response);
        $body   = wp_remote_retrieve_body($response);
        $data   = json_decode($body, true);

        if ($status === 200 && isset($data['balance']) && is_numeric($data['balance'])) {
            return true;
        }

        return $this->response_error('بله', $status, $data, $body);
    }
}
