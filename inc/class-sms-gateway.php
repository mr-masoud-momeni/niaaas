<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/gateways/abstract-gateway.php';
require_once __DIR__ . '/gateways/class-gateway-new-adapter.php';
require_once __DIR__ . '/gateways/class-gateway-registry.php';

// Pre-load native gateway files so their classes are available.
require_once __DIR__ . '/gateways/class-gateway-kavenegar.php';
require_once __DIR__ . '/gateways/class-gateway-melipayamak.php';
require_once __DIR__ . '/gateways/class-gateway-farapayamak.php';
require_once __DIR__ . '/gateways/class-gateway-faraz-soap.php';
require_once __DIR__ . '/gateways/class-gateway-faraz-api.php';
require_once __DIR__ . '/gateways/class-gateway-api-payamak.php';
require_once __DIR__ . '/gateways/class-gateway-sms-ir.php';
require_once __DIR__ . '/gateways/class-gateway-bale.php';

class Nias_SMS_Gateway
{
    private string $gateway;
    private array  $settings;

    public function __construct(string $gateway = '')
    {
        $this->gateway = $gateway ?: (string) get_option('nias_operator', '');
        $this->load_settings();
    }

    // ── Public send methods ────────────────────────────────────────────────

    public function send_code($phone, $code)
    {
        $params = is_array($code)
            ? $code
            : $this->build_params($code, [$this->settings['var1'], $this->settings['var2']], ['code']);

        return $this->send($phone, $this->settings['pattern'], $params);
    }

    public function send_password($phone, $password)
    {
        $params = $this->build_params($password, [$this->settings['password_var']], ['password']);
        return $this->send($phone, $this->settings['password_pattern'], $params);
    }

    public function send($phone, $template, $params = [])
    {
        if (empty($phone))    return 'شماره موبایل وارد نشده است';

        $instance = Nias_Gateway_Registry::create_instance($this->gateway, $this->settings);

        if (!$instance) {
            return 'درگاه پیامک پشتیبانی نمی‌شود: ' . esc_html($this->gateway);
        }

        try {
            $response = $instance->send((string) $phone, (string) $template, (array) $params);
            return $this->normalize_response($response);
        } catch (Exception $e) {
            return 'خطا در ارسال پیامک: ' . $e->getMessage();
        }
    }

    /**
     * ارسال پیامک با متن آزاد از طریق درگاه تنظیم‌شده
     * (برای اعلان‌های وضعیت سفارش ووکامرس و موارد مشابه)
     */
    public function send_text($phone, $text)
    {
        if (empty($phone)) return 'شماره موبایل وارد نشده است';
        if (trim((string) $text) === '') return 'متن پیامک خالی است';

        $instance = Nias_Gateway_Registry::create_instance($this->gateway, $this->settings);

        if (!$instance) {
            return 'درگاه پیامک پشتیبانی نمی‌شود: ' . esc_html($this->gateway);
        }

        try {
            $response = $instance->send_text((string) $phone, (string) $text);
            return $this->normalize_response($response);
        } catch (Exception $e) {
            return 'خطا در ارسال پیامک: ' . $e->getMessage();
        }
    }

    public function send_bale($phone, $code)
    {
        $instance = new Nias_Gateway_Bale($this->settings);
        $response = $instance->send((string) $phone, '', ['code' => $code]);
        return $this->normalize_response($response);
    }

    public function get_gateway_info(): array
    {
        return ['gateway' => $this->gateway];
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function load_settings(): void
    {
        $var1 = (string) get_option('nias_var1', '');
        $var2 = (string) get_option('nias_var2', '');

        // اگر فقط یکی از متغیرها تنظیم شده باشد، همان مقدار برای دیگری هم استفاده می‌شود.
        if ($var1 === '' && $var2 !== '') {
            $var1 = $var2;
        } elseif ($var2 === '' && $var1 !== '') {
            $var2 = $var1;
        }

        $this->settings = [
            'username'         => (string) get_option('nias_username', ''),
            'password'         => (string) get_option('nias_password', ''),
            'api_key'          => (string) get_option('nias_api', ''),
            'local_number'     => (string) get_option('nias_localnumber', ''),
            'pattern'          => (string) get_option('nias_pattern', ''),
            'var1'             => $var1,
            'var2'             => $var2,
            'password_pattern' => (string) get_option('nias_password_pattern', ''),
            'password_var'     => (string) get_option('nias_password_var', ''),
            'sms_text'         => (string) get_option('nias_sms_text', ''),
            'nias_bale_api'    => (string) get_option('nias_bale_api', ''),
            'nias_bale_botid'  => (string) get_option('nias_bale_botid', ''),
        ];
    }

    /**
     * ساخت آرایهٔ پارامترهای پترن.
     *
     * هر فیلد متغیر می‌تواند چند نام جداشده با کاما/فاصله/سمی‌کالن داشته باشد
     * (مثلاً «code, otp»)؛ همهٔ نام‌ها با همان مقدار (کد تایید) پر می‌شوند تا
     * پترن‌های چندمتغیره هم کامل ارسال شوند و خطای «متغیر پر نشده» ندهند.
     */
    private function build_params($value, array $names, array $defaults = []): array
    {
        $expanded = [];
        foreach ($names as $name) {
            foreach (preg_split('/[\s,،;]+/u', (string) $name, -1, PREG_SPLIT_NO_EMPTY) as $part) {
                $expanded[] = $part;
            }
        }
        $expanded = array_values(array_unique($expanded));
        if (empty($expanded)) {
            $expanded = $defaults;
        }

        $params = [];
        foreach ($expanded as $name) {
            $params[$name] = $value;
        }
        return $params;
    }

    private function normalize_response($response)
    {
        if (is_wp_error($response)) return $response->get_error_message();
        if ($response === false || $response === null || $response === '') return 'ارسال پیامک ناموفق بود';
        return is_string($response) ? $response : true;
    }
}
