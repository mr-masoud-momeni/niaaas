<?php
defined('ABSPATH') || exit;

/**
 * Wraps any new-format gateway (PW\PWSMS\Gateways namespace + GatewayTrait)
 * into the Nias_Abstract_Gateway interface.
 *
 * We intentionally do NOT define a PWSMS() stub here.  The persian-woocommerce-sms
 * plugin owns that function; shadowing it with our own anonymous class causes
 * "Call to undefined method class@anonymous::get_sms_gateways()" on line 120
 * of its Settings.php.
 *
 * Instead we bypass the GatewayTrait constructor entirely by using
 * ReflectionClass::newInstanceWithoutConstructor() and then setting all
 * required properties manually.  This means our code never touches PWSMS().
 */
class Nias_New_Gateway_Adapter extends Nias_Abstract_Gateway
{
    private string $gateway_file;
    private string $gateway_class;

    public function __construct(array $settings, string $gateway_file, string $gateway_class)
    {
        parent::__construct($settings);
        $this->gateway_file  = $gateway_file;
        $this->gateway_class = $gateway_class;
    }

    public function send(string $phone, string $template, array $params)
    {
        if (!empty($template)) {
            // Pattern-based: build the "patterncode:ID\nKEY:VALUE" string.
            $msg = 'patterncode:' . $template;
            foreach ($params as $k => $v) {
                $msg .= "\n" . $k . ':' . $v;
            }
        } else {
            // Plain-text fallback: substitute {code} in the configured template.
            $text_tpl = !empty($this->settings['sms_text'])
                ? $this->settings['sms_text']
                : 'کد تایید: {code}';
            $code = (string) (reset($params) ?: '');
            $msg  = str_replace('{code}', $code, $text_tpl);
        }

        return $this->dispatch($phone, $msg);
    }

    /** ارسال متن آزاد — درگاه‌های این گروه همگی متنی هستند و مستقیم پشتیبانی می‌کنند. */
    public function send_text(string $phone, string $text)
    {
        return $this->dispatch($phone, $text);
    }

    private function dispatch(string $phone, string $message)
    {
        static $bootstrapped = false;
        static $loaded       = [];

        // Load the interface + trait once so gateway class declarations succeed.
        if (!$bootstrapped) {
            $base = NIAS_LOGIN_INC . 'new-Gateways/';
            require_once $base . 'GatewayInterface.php';
            require_once $base . 'GatewayTrait.php';
            $bootstrapped = true;
        }

        if (!isset($loaded[$this->gateway_file])) {
            $path = NIAS_LOGIN_INC . 'new-Gateways/' . $this->gateway_file;
            if (!file_exists($path)) {
                return 'فایل درگاه پیامک یافت نشد: ' . esc_html($this->gateway_file);
            }
            require_once $path;
            $loaded[$this->gateway_file] = true;
        }

        $fqcn = 'Nias\\PWSMS\\Gateways\\' . $this->gateway_class;
        if (!class_exists($fqcn)) {
            return 'کلاس درگاه پیامک یافت نشد: ' . esc_html($this->gateway_class);
        }

        try {
            // Skip __construct (which calls PWSMS() from the other plugin) and
            // initialise all GatewayTrait properties ourselves.
            $rc   = new \ReflectionClass($fqcn);
            $inst = $rc->newInstanceWithoutConstructor();

            // For API-key gateways the trait reads the key from ->username,
            // so we use nias_api when nias_username is empty.
            $inst->username     = $this->settings['username'] ?: $this->settings['api_key'];
            $inst->password     = $this->settings['password'];
            $inst->senderNumber = $this->settings['local_number'];
            $inst->mobile       = [$phone];
            $inst->message      = $message;

            $result = $inst->send();

            if ($result === true)  return true;
            if ($result === false) return 'ارسال پیامک ناموفق';
            return (string) $result;

        } catch (\Throwable $e) {
            return 'خطا در ارسال پیامک: ' . $e->getMessage();
        }
    }
}
