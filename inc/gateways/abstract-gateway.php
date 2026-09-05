<?php
defined('ABSPATH') || exit;

abstract class Nias_Abstract_Gateway
{
    protected $settings = [];

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    abstract public function send(string $phone, string $template, array $params);

    /**
     * ارسال پیامک با متن آزاد (بدون پترن) — برای اعلان‌هایی مثل وضعیت سفارش ووکامرس.
     * درگاه‌هایی که ارسال متن آزاد دارند این متد را override می‌کنند.
     */
    public function send_text(string $phone, string $text)
    {
        return 'درگاه انتخاب‌شده از ارسال پیامک با متن آزاد پشتیبانی نمی‌کند';
    }

    protected function response_error(string $gateway, int $status, $data, string $raw_body = ''): string
    {
        $data    = is_array($data) ? $data : null;
        $message = '';
        if (is_array($data)) {
            $message = $data['message'] ?? $data['error'] ?? $data['Message'] ?? '';
        }
        if (empty($message) && !empty($raw_body)) {
            $message = wp_strip_all_tags((string) $raw_body);
        }
        return 'خطای ' . $gateway . ' (' . $status . '): ' . ($message ?: 'پاسخ نامعتبر از سرویس');
    }
}
