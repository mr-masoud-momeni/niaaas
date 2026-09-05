<?php
defined('ABSPATH') || exit;

/**
 * فرا پیامک — FaraPayamak.ir
 *
 * مستندات: https://docs.farapayamak.ir/webservice-rest/rest-shared-number/rest-base-service-number
 *
 * پیش از این از وب‌سرویس SOAP قدیمی (api.payamak-panel.com/post/send.asmx) استفاده
 * می‌شد که فقط متن آزاد می‌فرستاد و پترن نداشت. حالا از REST استفاده می‌شود:
 *
 *   • کد تایید  → /api/SendSMS/BaseServiceNumber  (ارسال با الگوی تایید‌شده)
 *   • متن آزاد  → /api/SendSMS/SendSMS            (پیامک وضعیت سفارش ووکامرس و مشابه)
 *
 * در روش الگو، شماره فرستنده لازم نیست؛ خط خدماتی خودِ سامانه استفاده می‌شود.
 */
class Nias_Gateway_Farapayamak extends Nias_Abstract_Gateway
{
    private const BASE = 'https://rest.payamak-panel.com/api/SendSMS';

    /**
     * کدهای وضعیت سرویس.
     *
     * سامانه دو خانواده کد دارد: مقادیر منفی در کلید Value برمی‌گردند و مقادیر
     * مثبت در RetStatus. چون با هم تداخل ندارند در یک نگاشت نگه داشته شده‌اند.
     */
    private const ERRORS = [
        -112 => 'دسترسی وب‌سرویس برای این حساب غیرفعال است',
        -111 => 'IP درخواست‌کننده مجاز نیست',
        -110 => 'استفاده از ApiKey الزامی است',
        -109 => 'تنظیم IP مجاز برای API الزامی است',
        -108 => 'IP درخواست‌کننده مسدود شده است',
        -10  => 'ارسال لینک در متغیرهای الگو مجاز نیست',
        -6   => 'خطای داخلی سرویس',
        -5   => 'متن ارسالی با متغیرهای الگو هم‌خوانی ندارد (تعداد متغیرها را بررسی کنید)',
        -4   => 'شناسه الگو صحیح نیست یا هنوز تایید نشده است',
        -3   => 'خط ارسال در سیستم تعریف نشده است',
        -2   => 'در هر فراخوانی فقط یک شماره موبایل قابل ارسال است',
        -1   => 'دسترسی به وب‌سرویس خدماتی غیرفعال است',
        0    => 'نام کاربری یا رمز عبور صحیح نیست',
        2    => 'اعتبار حساب کافی نیست',
        6    => 'سامانه در حال به‌روزرسانی است',
        7    => 'متن حاوی کلمه فیلترشده است',
        10   => 'کاربر فعال نیست',
        11   => 'ارسال انجام نشد',
        12   => 'مدارک کاربر کامل نیست',
        18   => 'شماره موبایل معتبر نیست',
        19   => 'سقف محدودیت روزانه API تکمیل شده است',
        35   => 'داده‌های ارسالی نامعتبر است',
    ];

    /**
     * ارسال کد تایید با الگو.
     *
     * $template شناسه الگوی تایید‌شده در پنل (bodyId) است و $params مقدار
     * متغیرهای همان الگو که با «;» به هم چسبانده می‌شوند — ترتیبشان باید با
     * ترتیب متغیرها در متن الگو یکی باشد.
     */
    public function send(string $phone, string $template, array $params)
    {
        $pattern = trim($template);

        if ($pattern === '') {
            return 'کد پترن فرا پیامک وارد نشده است';
        }

        $error = $this->validate_credentials();
        if ($error !== '') {
            return $error;
        }

        $values = [];
        foreach ($params as $value) {
            $values[] = (string) $value;
        }

        if (empty($values)) {
            return 'متغیرهای الگوی فرا پیامک تنظیم نشده است (فیلد «متغیر ۱» را پر کنید)';
        }

        return $this->request('/BaseServiceNumber', [
            'text'   => implode(';', $values),
            'to'     => $phone,
            'bodyId' => is_numeric($pattern) ? (int) $pattern : $pattern,
        ]);
    }

    /** ارسال متن آزاد — برای اعلان‌های وضعیت سفارش و مشابه آن */
    public function send_text(string $phone, string $text)
    {
        $error = $this->validate_credentials();
        if ($error !== '') {
            return $error;
        }

        if (trim((string) ($this->settings['local_number'] ?? '')) === '') {
            return 'شماره ارسال‌کننده فرا پیامک وارد نشده است';
        }

        if (trim($text) === '') {
            return 'متن پیامک خالی است';
        }

        return $this->request('/SendSMS', [
            'to'   => $phone,
            'from' => (string) $this->settings['local_number'],
            'text' => $text,
        ]);
    }

    // ── زیرساخت ─────────────────────────────────────────────────────────────

    private function validate_credentials(): string
    {
        if (trim((string) ($this->settings['username'] ?? '')) === '' || $this->password() === '') {
            return 'نام کاربری یا رمز عبور فرا پیامک وارد نشده است';
        }

        return '';
    }

    /** بعضی حساب‌ها به‌جای رمز عبور، کلید API می‌گیرند؛ هر کدام پر بود استفاده می‌شود */
    private function password(): string
    {
        $password = trim((string) ($this->settings['password'] ?? ''));

        return $password !== '' ? $password : trim((string) ($this->settings['api_key'] ?? ''));
    }

    private function request(string $path, array $body)
    {
        $response = wp_remote_post(self::BASE . $path, [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body'    => array_merge([
                'username' => (string) $this->settings['username'],
                'password' => $this->password(),
            ], $body),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return 'خطا در اتصال به فرا پیامک: ' . $response->get_error_message();
        }

        $http = (int) wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);
        $data = json_decode($raw, true);

        // پاسخ موفق: {"Value":"<شناسه رکورد>","RetStatus":1,"StrRetStatus":"Ok"}
        // پاسخ ناموفق: RetStatus کد خطای مثبت، یا Value کد خطای منفی
        if (is_array($data)) {
            $ret = isset($data['RetStatus']) ? (int) $data['RetStatus'] : null;

            if (1 === $ret) {
                return true;
            }

            // کدهای منفی داخل Value برمی‌گردند (Value موفق یک شناسه بلند عددی است)
            $value = isset($data['Value']) && is_numeric($data['Value']) ? (int) $data['Value'] : null;
            $code  = (null !== $value && $value < 0) ? $value : $ret;

            if (null !== $code) {
                $message = self::ERRORS[$code]
                    ?? (!empty($data['StrRetStatus']) ? (string) $data['StrRetStatus'] : 'خطای نامشخص در ارسال');
                return 'خطای فرا پیامک (' . $code . '): ' . $message;
            }
        }

        return $this->response_error('فرا پیامک', $http, $data, $raw);
    }
}
