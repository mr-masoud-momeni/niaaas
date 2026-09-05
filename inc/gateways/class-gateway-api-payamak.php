<?php
defined('ABSPATH') || exit;

/**
 * پایه‌ی درگاه‌های مبتنی بر سرویس api-payamak.com
 *
 * مستندات: https://api-payamak.com/help/rest.php
 *
 * سه پنل «سفیرک»، «ایده پیام» و «علاءالدین مارکتینگ» روی همین زیرساخت کار
 * می‌کنند و API یکسانی دارند، پس منطق ارسال یک‌بار اینجا نوشته شده و هر برند
 * فقط نامش را برای پیام‌های خطا مشخص می‌کند.
 *
 * دو حالت ارسال:
 *   • کد تایید      → /sms/pattern-send  (با شناسه الگو و متغیرهای آن)
 *   • متن آزاد      → /sms/send          (پیامک وضعیت سفارش ووکامرس و مشابه)
 *
 * احراز هویت: اگر «کلید API» پر باشد به‌صورت هدر توکن فرستاده می‌شود، وگرنه
 * نام کاربری و رمز عبور پنل.
 */
abstract class Nias_Gateway_Api_Payamak extends Nias_Abstract_Gateway
{
    private const BASE = 'https://api-payamak.com/api/v1/rest';

    /** کدهای خطای سرویس (کلید result.status). صفر یعنی موفق. */
    private const ERRORS = [
        21 => 'تعداد گیرنده‌ها از حد مجاز بیشتر است',
        22 => 'اعتبار حساب کافی نیست',
        23 => 'شماره فرستنده نامعتبر است',
        24 => 'متن پیامک خالی است',
        25 => 'گیرنده‌ای انتخاب نشده است',
        26 => 'زمان ارسال نامعتبر است',
        27 => 'خطای نامشخص در ارسال (اپراتور)',
        28 => 'داده‌های ارسالی با هم هم‌خوانی ندارند',
        29 => 'الگوی انتخاب‌شده فعال نیست',
    ];

    /** نام برند برای نمایش در پیام‌های خطا */
    abstract protected function brand(): string;

    /**
     * ارسال کد تایید با الگو.
     *
     * $template شناسه الگوی تعریف‌شده در پنل است و $params نام متغیرهای همان
     * الگو (از فیلدهای «متغیر ۱/۲» تنظیمات) به همراه مقدارشان.
     */
    public function send(string $phone, string $template, array $params)
    {
        $pattern = trim($template);

        if ($pattern === '') {
            return 'کد پترن ' . $this->brand() . ' وارد نشده است';
        }

        $error = $this->validate_credentials();
        if ($error !== '') {
            return $error;
        }

        $message = [];
        foreach ($params as $name => $value) {
            $name = trim((string) $name);
            if ($name !== '') {
                $message[$name] = (string) $value;
            }
        }

        if (empty($message)) {
            return 'متغیرهای الگوی ' . $this->brand() . ' تنظیم نشده است (فیلد «متغیر ۱» را پر کنید)';
        }

        return $this->request('/sms/pattern-send', [
            'from'       => (string) $this->settings['local_number'],
            'recipients' => [$phone],
            // باید آبجکت جیسون باشد نه آرایه، پس صریحاً به object تبدیل می‌شود
            'message'    => (object) $message,
            'patternId'  => is_numeric($pattern) ? (int) $pattern : $pattern,
            'type'       => 0,
        ]);
    }

    /** ارسال متن آزاد — برای اعلان‌های وضعیت سفارش ووکامرس و مشابه آن */
    public function send_text(string $phone, string $text)
    {
        $error = $this->validate_credentials();
        if ($error !== '') {
            return $error;
        }

        if (trim($text) === '') {
            return 'متن پیامک خالی است';
        }

        return $this->request('/sms/send', [
            'from'       => (string) $this->settings['local_number'],
            'recipients' => [$phone],
            'message'    => $text,
            'type'       => 0,
        ]);
    }

    // ── زیرساخت ─────────────────────────────────────────────────────────────

    private function validate_credentials(): string
    {
        $api_key  = trim((string) ($this->settings['api_key'] ?? ''));
        $username = trim((string) ($this->settings['username'] ?? ''));
        $password = trim((string) ($this->settings['password'] ?? ''));

        if ($api_key === '' && ($username === '' || $password === '')) {
            return 'کلید API یا نام کاربری و رمز عبور ' . $this->brand() . ' وارد نشده است';
        }

        if (trim((string) ($this->settings['local_number'] ?? '')) === '') {
            return 'شماره ارسال‌کننده ' . $this->brand() . ' وارد نشده است';
        }

        return '';
    }

    private function headers(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        $api_key = trim((string) ($this->settings['api_key'] ?? ''));

        if ($api_key !== '') {
            // سرویس هر دو هدر را می‌پذیرد؛ بعضی هاست‌ها هدر Authorization را
            // حذف می‌کنند، پس Token هم فرستاده می‌شود
            $headers['Authorization'] = $api_key;
            $headers['Token']         = $api_key;
        } else {
            $headers['Username'] = (string) $this->settings['username'];
            $headers['Password'] = (string) $this->settings['password'];
        }

        return $headers;
    }

    private function request(string $path, array $body)
    {
        $response = wp_remote_post(self::BASE . $path, [
            'headers' => $this->headers(),
            'body'    => wp_json_encode($body, JSON_UNESCAPED_UNICODE),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return 'خطا در اتصال به ' . $this->brand() . ': ' . $response->get_error_message();
        }

        $http = (int) wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);
        $data = json_decode($raw, true);

        // ساختار پاسخ:  موفق  → {"status":200,"result":{"status":0,"id":...}}
        //               ناموفق → {"status":<http-like>,"message":"..."}
        $outer  = isset($data['status']) ? (int) $data['status'] : 0;
        $result = isset($data['result']['status']) ? (int) $data['result']['status'] : null;

        if ($outer === 200 && $result === 0) {
            return true;
        }

        // خطای خودِ ارسال (اعتبار، شماره فرستنده، الگوی غیرفعال و ...)
        if (null !== $result && 0 !== $result) {
            $message = self::ERRORS[$result] ?? 'خطای نامشخص در ارسال';
            return 'خطای ' . $this->brand() . ' (' . $result . '): ' . $message;
        }

        // خطای سطح درخواست (احراز هویت، اندپوینت، سرور و ...)
        if (!empty($data['message']) && is_string($data['message'])) {
            return 'خطای ' . $this->brand() . ' (' . ($outer ?: $http) . '): ' . trim($data['message']);
        }

        return $this->response_error($this->brand(), $outer ?: $http, $data, $raw);
    }
}

/** سفیرک — safirak.com */
class Nias_Gateway_Safirak extends Nias_Gateway_Api_Payamak
{
    protected function brand(): string
    {
        return 'سفیرک';
    }
}

/** ایده پیام — idehpayam.com */
class Nias_Gateway_Idehpayam extends Nias_Gateway_Api_Payamak
{
    protected function brand(): string
    {
        return 'ایده پیام';
    }
}

/** علاءالدین مارکتینگ */
class Nias_Gateway_Aladdin extends Nias_Gateway_Api_Payamak
{
    protected function brand(): string
    {
        return 'علاءالدین مارکتینگ';
    }
}
