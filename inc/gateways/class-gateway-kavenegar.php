<?php
defined('ABSPATH') || exit;

class Nias_Gateway_Kavenegar extends Nias_Abstract_Gateway
{
    public function send(string $phone, string $template, array $params)
    {
        if (empty($this->settings['api_key'])) {
            return 'API Key کاوه نگار وارد نشده است';
        }

        $body = ['receptor' => $phone, 'template' => $template] + $this->build_tokens($params);

        $response = wp_remote_post(
            'https://api.kavenegar.com/v1/' . $this->settings['api_key'] . '/verify/lookup.json',
            ['body' => $body, 'timeout' => 15]
        );

        if (is_wp_error($response)) {
            return 'خطای اتصال به کاوه نگار: ' . $response->get_error_message();
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['return']['status']) && (int) $data['return']['status'] === 200) {
            return true;
        }

        return 'خطای کاوه نگار: ' . ($data['return']['message'] ?? 'پاسخ نامعتبر از سرویس');
    }

    /**
     * نگاشت پارامترها به توکن‌های مجاز VerifyLookup کاوه‌نگار.
     *
     * طبق مستندات کاوه‌نگار:
     *   - token, token2, token3 : به‌هیچ‌وجه نباید «فاصله» داشته باشند.
     *   - token10 : حداکثر ۵ فاصله مجاز است.
     *   - token20 : حداکثر ۸ فاصله مجاز است.
     * خط جدید، Tab و زیرخط (_) در هیچ توکنی مجاز نیست و باعث خطای 431
     * «ساختار کد صحیح نمی‌باشد» می‌شود (همان دلیلی که کد تایید عددی هرگز خطا
     * نمی‌دهد ولی متغیرهای ووکامرس مثل نام و مبلغ خطا می‌دهند).
     *
     * منطق:
     *   - اگر کلید پارامتر صراحتاً یکی از توکن‌های معتبر باشد (مثلاً در فیلد
     *     «متغیرهای پترن» نوشته شود token20:{billing_full_name})، همان رعایت می‌شود.
     *   - در غیر این صورت مقادیر بدون فاصله به token/token2/token3 و مقادیر
     *     دارای فاصله به token10/token20 (که فاصله را می‌پذیرند) اختصاص می‌یابند.
     *
     * @return array<string,string> body پارامترهای توکن
     */
    private function build_tokens(array $params): array
    {
        $valid        = ['token', 'token2', 'token3', 'token10', 'token20'];
        $plain_slots  = ['token', 'token2', 'token3']; // بدون فاصله
        $spaced_slots = ['token10', 'token20'];        // پذیرای فاصله
        $plain_i      = 0;
        $spaced_i     = 0;
        $tokens       = [];

        foreach ($params as $key => $value) {
            $value = $this->sanitize_token((string) $value);

            // کلید صریحِ معتبر → همان توکن استفاده می‌شود
            if (is_string($key) && in_array(strtolower($key), $valid, true)) {
                $tokens[strtolower($key)] = $value;
                continue;
            }

            // توزیع خودکار بر اساس وجود فاصله
            if (strpos($value, ' ') !== false && isset($spaced_slots[$spaced_i])) {
                $tokens[$spaced_slots[$spaced_i++]] = $value;
            } elseif (isset($plain_slots[$plain_i])) {
                $tokens[$plain_slots[$plain_i++]] = $value;
            }
        }

        return $tokens;
    }

    /**
     * پاک‌سازی مقدار توکن: حذف کاراکترهایی که کاوه‌نگار در هیچ توکنی نمی‌پذیرد.
     *
     * خط جدید، Tab و زیرخط (_) و چند فاصلهٔ پشت‌سرهم به یک «فاصلهٔ تکی» تبدیل
     * می‌شوند. فاصلهٔ تکی حفظ می‌شود چون مقدار دارای فاصله در build_tokens به
     * token10/token20 (که تا ۵ و ۸ فاصله را می‌پذیرند) فرستاده می‌شود.
     */
    private function sanitize_token($value): string
    {
        $value = (string) $value;
        $value = preg_replace('/[\s_]+/u', ' ', $value);
        return trim($value);
    }

    public function send_text(string $phone, string $text)
    {
        if (empty($this->settings['api_key'])) {
            return 'API Key کاوه نگار وارد نشده است';
        }

        $body = ['receptor' => $phone, 'message' => $text];
        if (!empty($this->settings['local_number'])) {
            $body['sender'] = $this->settings['local_number'];
        }

        $response = wp_remote_post(
            'https://api.kavenegar.com/v1/' . $this->settings['api_key'] . '/sms/send.json',
            ['body' => $body, 'timeout' => 15]
        );

        if (is_wp_error($response)) {
            return 'خطای اتصال به کاوه نگار: ' . $response->get_error_message();
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['return']['status']) && (int) $data['return']['status'] === 200) {
            return true;
        }

        return 'خطای کاوه نگار: ' . ($data['return']['message'] ?? 'پاسخ نامعتبر از سرویس');
    }
}
