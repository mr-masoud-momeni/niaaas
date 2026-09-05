<?php
defined('ABSPATH') || exit;

/**
 * Central registry for all SMS gateways.
 *
 * Each entry stores:
 *  'id'     – unique slug used in nias_operator option
 *  'name'   – human-readable label shown in the admin dropdown
 *  'class'  – PHP class name
 *  'file'   – for 'native' type: full path; for 'new' type: filename inside new-Gateways/
 *  'type'   – 'native' (Nias_Abstract_Gateway subclass) | 'new' (Nias\PWSMS\Gateways class)
 *  'fields' – which admin fields to show when this gateway is selected
 *
 * Field tokens: username | password | api | localnumber | pattern | var1 | var2 | smstext
 */
class Nias_Gateway_Registry
{
    // ── Reusable field groups ───────────────────────────────────────────────
    const F_USER_PASS_SENDER         = ['username', 'password', 'localnumber', 'smstext'];
    const F_USER_PASS_SENDER_PATTERN = ['username', 'password', 'localnumber', 'pattern', 'var1', 'var2'];
    const F_API_PATTERN              = ['api', 'pattern', 'var1', 'var2'];
    const F_API_SENDER_PATTERN       = ['api', 'localnumber', 'pattern', 'var1', 'var2'];
    const F_API_SENDER               = ['api', 'localnumber', 'smstext'];

    private static array $gateways    = [];
    private static bool  $initialized = false;

    // ── Public API ──────────────────────────────────────────────────────────

    public static function init(): void
    {
        if (self::$initialized) return;
        self::$initialized = true;
        self::register_all();
    }

    /** Return every registered gateway (keyed by id). */
    public static function get_all(): array
    {
        self::init();
        return self::$gateways;
    }

    /** Return a single gateway entry or null. */
    public static function get(string $id): ?array
    {
        self::init();
        return self::$gateways[$id] ?? null;
    }

    /**
     * Build the <option> HTML for the admin dropdown.
     * Native gateways appear first in one optgroup; new gateways in a second.
     */
    public static function get_options_html(string $selected_id): string
    {
        self::init();
        $native = array_filter(self::$gateways, static fn($g) => $g['type'] === 'native');
        $new    = array_filter(self::$gateways, static fn($g) => $g['type'] === 'new');

        $html  = '<optgroup label="درگاه‌های اصلی">';
        foreach ($native as $gw) {
            $html .= '<option value="' . esc_attr($gw['id']) . '" '
                   . selected($selected_id, $gw['id'], false) . '>'
                   . esc_html($gw['name']) . '</option>';
        }
        $html .= '</optgroup><optgroup label="سایر درگاه‌ها">';
        foreach ($new as $gw) {
            $html .= '<option value="' . esc_attr($gw['id']) . '" '
                   . selected($selected_id, $gw['id'], false) . '>'
                   . esc_html($gw['name']) . '</option>';
        }
        $html .= '</optgroup>';
        return $html;
    }

    /**
     * Instantiate the correct gateway class for the given id and settings array.
     * Returns null when the id is unknown.
     */
    public static function create_instance(string $id, array $settings): ?Nias_Abstract_Gateway
    {
        $gw = self::get($id);
        if (!$gw) return null;

        if ($gw['type'] === 'native') {
            if (!class_exists($gw['class'])) {
                require_once $gw['file'];
            }
            $class = $gw['class'];
            return new $class($settings);
        }

        // New-format gateway: use the adapter
        $class = $gw['class'];
        return new Nias_New_Gateway_Adapter($settings, $gw['file'], $class);
    }

    /**
     * Return the field list for a given gateway id.
     * Falls back to username+password+localnumber+smstext for unknown ids.
     */
    public static function fields_for(string $id): array
    {
        $gw = self::get($id);
        return $gw ? $gw['fields'] : self::F_USER_PASS_SENDER;
    }

    // ── Private registration ────────────────────────────────────────────────

    private static function add(array $entry): void
    {
        self::$gateways[$entry['id']] = $entry;
    }

    private static function register_all(): void
    {
        $inc = NIAS_LOGIN_INC;

        // ── Native gateways (already-ported Nias_Abstract_Gateway subclasses) ──
        self::add(['id' => 'national_sms', 'name' => 'ملی پیامک',                   'class' => 'Nias_Gateway_Melipayamak', 'file' => $inc . 'gateways/class-gateway-melipayamak.php', 'type' => 'native', 'fields' => ['username', 'api', 'localnumber', 'pattern', 'var1', 'var2']]);
        self::add(['id' => 'negar_payam',  'name' => 'نگار پیام',                    'class' => 'Nias_Gateway_Melipayamak', 'file' => $inc . 'gateways/class-gateway-melipayamak.php', 'type' => 'native', 'fields' => ['username', 'api', 'localnumber', 'pattern', 'var1', 'var2']]);
        self::add(['id' => 'payamito',     'name' => 'پیامیتو',                       'class' => 'Nias_Gateway_Melipayamak', 'file' => $inc . 'gateways/class-gateway-melipayamak.php', 'type' => 'native', 'fields' => ['username', 'api', 'localnumber', 'pattern', 'var1', 'var2']]);
        self::add(['id' => 'faraz',        'name' => 'آیپی پنل',                      'class' => 'Nias_Gateway_Faraz_Soap',  'file' => $inc . 'gateways/class-gateway-faraz-soap.php',  'type' => 'native', 'fields' => ['username', 'password', 'localnumber', 'pattern', 'var1', 'var2']]);
        self::add(['id' => 'new_faraz',    'name' => 'فراز جدید',                     'class' => 'Nias_Gateway_Faraz_Api',   'file' => $inc . 'gateways/class-gateway-faraz-api.php',   'type' => 'native', 'fields' => ['localnumber', 'api', 'pattern', 'var1', 'var2']]);
        self::add(['id' => 'kavenegar',    'name' => 'کاوه‌نگار',                      'class' => 'Nias_Gateway_Kavenegar',   'file' => $inc . 'gateways/class-gateway-kavenegar.php',   'type' => 'native', 'fields' => ['api', 'pattern']]);
        self::add(['id' => 'aladdin',      'name' => 'علاءالدین مارکتینگ',            'class' => 'Nias_Gateway_Aladdin',     'file' => $inc . 'gateways/class-gateway-api-payamak.php', 'type' => 'native', 'fields' => ['username', 'password', 'api', 'localnumber', 'pattern', 'var1', 'var2']]);
        self::add(['id' => 'sms_ir',       'name' => 'SMS.ir',                        'class' => 'Nias_Gateway_Sms_Ir',      'file' => $inc . 'gateways/class-gateway-sms-ir.php',      'type' => 'native', 'fields' => ['api', 'localnumber', 'pattern', 'var1', 'var2']]);
        // سه پنل زیر روی سرویس api-payamak.com کار می‌کنند و API یکسانی دارند،
        // پس هر سه از یک کلاس پایه ارث می‌برند (ارسال کد تایید با الگو)
        self::add(['id' => 'safirak',      'name' => 'سفیرک (safirak.com)',           'class' => 'Nias_Gateway_Safirak',     'file' => $inc . 'gateways/class-gateway-api-payamak.php', 'type' => 'native', 'fields' => ['username', 'password', 'api', 'localnumber', 'pattern', 'var1', 'var2']]);
        self::add(['id' => 'idehpayam',    'name' => 'ایده پیام (idehpayam.com)',      'class' => 'Nias_Gateway_Idehpayam',   'file' => $inc . 'gateways/class-gateway-api-payamak.php', 'type' => 'native', 'fields' => ['username', 'password', 'api', 'localnumber', 'pattern', 'var1', 'var2']]);

        // ── New gateways via adapter (Nias\PWSMS\Gateways namespace) ──────────
        // Pattern + API key (username field acts as API key)
        self::n('ghasedak',           'قاصدک (ghasedak.me)',                        'Ghasedak',          'Ghasedak.php',          self::F_API_SENDER_PATTERN);
        self::n('maxsms',             'MaxSMS (maxsms.co)',                         'MaxSMS',            'MaxSMS.php',            self::F_API_SENDER_PATTERN);
        self::n('mediana',            'مدیانا (mediana.ir)',                         'Mediana',           'Mediana.php',           self::F_API_SENDER_PATTERN);
        self::n('payamresan-pattern', 'پیام‌رسان - پترن (payam-resan.com)',          'PayamResanPattern', 'PayamResanPattern.php', self::F_API_SENDER_PATTERN);
        // Pattern + username/password
        self::n('modirpayamak',       'مدیر پیامک (Modirpayamak.com)',              'Modirpayamak',      'ModirPayamak.php',      self::F_USER_PASS_SENDER_PATTERN);
        self::n('panelchi',           'پنل چی (panelchi.com)',                      'PanelChi',          'PanelChi.php',          self::F_USER_PASS_SENDER_PATTERN);
        // Simple + API key
        self::n('araditc',            'آراد ITC',                                   'AradITC',           'AradITC.php',           self::F_API_SENDER);
        self::n('msgway',             'MSGWay (msgway.com)',                        'MSGWay',            'MSGWay.php',            self::F_API_SENDER);
        self::n('parsgreen',          'پارس گرین (parsgreen.com)',                  'ParsGreen',         'ParsGreen.php',         self::F_API_SENDER);
        self::n('sabanovin',          'صبا نوین (sabanovin.com)',                   'SabaNovin',         'SabaNovin.php',         self::F_API_SENDER);
        // Simple + username/password (SOAP-based gateways)
        self::n('afe',                'Afe (afe.ir)',                               'Afe',               'Afe.php',               self::F_USER_PASS_SENDER);
        self::n('afeir',              'Afe.ir',                                     'AfeIR',             'AfeIR.php',             self::F_USER_PASS_SENDER);
        self::n('aradpayamak',        'آراد پیامک (aradpayamak.net)',               'AradPayamak',       'AradPayamak.php',       self::F_USER_PASS_SENDER);
        self::n('aradsms',            'آراد SMS (Arad-SMS.ir)',                     'AradSMS',           'AradSMS.php',           self::F_USER_PASS_SENDER);
        self::n('aryana',             'آریانا (payamkotah.com)',                    'Aryana',            'Aryana.php',            self::F_USER_PASS_SENDER);
        self::n('asanak',             'آسانک (asanak.ir)',                          'Asanak',            'Asanak.php',            self::F_USER_PASS_SENDER);
        self::n('asiasms',            'آسیا SMS (asiasms.ir)',                      'AsiaSMS',           'AsiaSMS.php',           self::F_USER_PASS_SENDER);
        self::n('atlaspayamak',       'اطلس پیامک (atlaspayamak.ir)',               'AtlasPayamak',      'AtlasPayamak.php',      self::F_USER_PASS_SENDER);
        self::n('avalpayam',          'اول پیام (avalpayam.com)',                   'AvalPayam',         'AvalPayam.php',         self::F_USER_PASS_SENDER);
        self::n('azaranpayamak',      'آذران پیامک (Azaranpayamak.ir)',             'AzaranPayamak',     'AzaranPayamak.php',     self::F_USER_PASS_SENDER);
        self::n('bakhtarpanel',       'بختر پنل (bakhtar.xyz)',                     'BakhtarPanel',      'BakhtarPanel.php',      self::F_USER_PASS_SENDER);
        self::n('behsadade',          'بهسا داده (sms.behsadade.com)',              'BehsaDade',         'BehsaDade.php',         self::F_USER_PASS_SENDER);
        self::n('berandet',           'بران‌دت (berandet.ir)',                       'Berandet',          'Berandet.php',          self::F_USER_PASS_SENDER);
        self::n('b1ir',               '1B1 (1b1.ir)',                               'BOne',              'BOne.php',              self::F_USER_PASS_SENDER);
        self::n('candoo',             'Candoo SMS (CandooSMS.com)',                 'CandooSMS',         'CandooSMS.php',         self::F_USER_PASS_SENDER);
        self::n('chaparpanel',        'چاپار پنل (chaparpanel.ir)',                 'ChaparPanel',       'ChaparPanel.php',       self::F_USER_PASS_SENDER);
        self::n('chapargah',          'چاپارگاه (chapargah.com)',                   'Chapargah',         'Chapargah.php',         self::F_USER_PASS_SENDER);
        self::add(['id' => 'farapayamak', 'name' => 'فرا پیامک (FaraPayamak.ir)', 'class' => 'Nias_Gateway_Farapayamak', 'file' => $inc . 'gateways/class-gateway-farapayamak.php', 'type' => 'native', 'fields' => self::F_USER_PASS_SENDER_PATTERN]);
        self::n('farstech',           'فارس تک (sms.FarsTech.ir)',                  'FarsTech',          'FarsTech.php',          self::F_USER_PASS_SENDER);
        self::n('firstpayamak',       'فرست پیامک (firstpayamak.ir)',               'FirstPayamak',      'FirstPayamak.php',      self::F_USER_PASS_SENDER);
        self::n('flashsms',           'فلش SMS (flashsms.ir)',                      'FlashSMS',          'FlashSMS.php',          self::F_USER_PASS_SENDER);
        self::n('gamapayamak',        'گاما پیامک (GAMAPayamak.com)',               'GamaPayamak',       'GamaPayamak.php',       self::F_USER_PASS_SENDER);
        self::n('gamasystems',        'گاما سیستمز (gama.systems)',                 'GamaSystems',       'GamaSystems.php',       self::F_USER_PASS_SENDER);
        self::n('hadafwp',            'هدف WP (sms.hadafwp.com)',                   'HadafWP',           'HadafWP.php',           self::F_USER_PASS_SENDER);
        self::n('hafezpayam',         'حافظ پیام (hafezpayam.com)',                 'HafezPayam',        'HafezPayam.php',        self::F_USER_PASS_SENDER);
        self::n('hiro_sms',           'هیرو SMS (hiro-sms.com)',                    'HiroSMS',           'HiroSMS.php',           self::F_USER_PASS_SENDER);
        self::n('hostiran',           'هاست ایران (Hostiran.com)',                  'HostIran',          'HostIran.php',          self::F_USER_PASS_SENDER);
        self::n('irpayamak',          'آی‌آر پیامک (irpayamak.com)',                'IRPayamak',         'IRPayamak.php',         self::F_USER_PASS_SENDER);
        self::n('isms',               'آی‌اس‌ام‌اس (isms.ir)',                        'ISMS',              'ISMS.php',              self::F_USER_PASS_SENDER);
        self::n('iransms',            'ایران SMS (iransms.co)',                     'IranSMS',           'IranSMS.php',           self::F_USER_PASS_SENDER);
        self::n('iransmsserver',      'ایران SMS سرور (iransmsserver.com)',         'IranSMSServer',     'IranSMSServer.php',     self::F_USER_PASS_SENDER);
        self::n('jarin',              'جارین (w.jarin.ir)',                          'Jarin',             'Jarin.php',             self::F_USER_PASS_SENDER);
        self::n('karenkart',          'کارن‌کارت (karenkart.com)',                   'KarenKart',         'KarenKart.php',         self::F_USER_PASS_SENDER);
        self::n('kianartpanel',       'کیان آرت (kianartpanel.ir)',                 'KianArtPanel',      'KianArtPanel.php',      self::F_USER_PASS_SENDER);
        self::n('loginpanel',         'لاگین پنل (loginpanel.ir)',                  'LoginPanel',        'LoginPanel.php',        self::F_USER_PASS_SENDER);
        self::n('logistic-sms',       'لجستیک SMS (logisticsms.ir)',                'LogisticSMS',       'LogisticSMS.php',       self::F_USER_PASS_SENDER);
        self::n('manirani',           'مانی ایرانی (Manirani.ir)',                  'ManiIrani',         'ManiIrani.php',         self::F_USER_PASS_SENDER);
        self::n('mehrafraz',          'مهر افراز (mehrafraz.com)',                  'MehrAfraz',         'MehrAfraz.php',         self::F_USER_PASS_SENDER);
        self::n('mehrpanel',          'مهر پنل (mehrpanel.ir)',                     'MehrPanel',         'MehrPanel.php',         self::F_USER_PASS_SENDER);
        self::n('nh1ir',              'NHOne (nh1.ir)',                             'NHOne',             'NHOne.php',             self::F_USER_PASS_SENDER);
        self::n('nmtsms',             'NMT SMS (nmtsms.ir)',                        'NMTSMS',            'NMTSMS.php',            self::F_USER_PASS_SENDER);
        self::n('npsms',              'NP SMS (npsms.com)',                         'NPSMS',             'NPSMS.php',             self::F_USER_PASS_SENDER);
        self::n('negins',             'نگینس (negins.com)',                         'Negins',            'Negins.php',            self::F_USER_PASS_SENDER);
        self::n('netpaydar',          'نت پایدار (sms.netpaydar.com)',              'NetPaydar',         'NetPaydar.php',         self::F_USER_PASS_SENDER);
        self::n('newsms',             'نیو SMS (newsms.ir)',                        'NewSMS',            'NewSMS.php',            self::F_USER_PASS_SENDER);
        self::n('niazpardaz',         'نیاز پرداز (SMS.NiazPardaz.com)',            'NiazPardazCOM',     'NiazPardazCOM.php',     self::F_USER_PASS_SENDER);
        self::n('niazpardaz_ir',      'نیاز پرداز IR (Login.NiazPardaz.ir)',        'NiazPardazIR',      'NiazPardazIR.php',      self::F_USER_PASS_SENDER);
        self::n('nicsms',             'نیک SMS (niksms.com)',                       'NikSMS',            'NikSMS.php',            self::F_USER_PASS_SENDER);
        self::n('paaz',               'پاز (paaz.ir)',                              'Paaz',              'Paaz.php',              self::F_USER_PASS_SENDER);
        self::n('panelsms20',         'پنل SMS ۲۰ (panelsms20.ir)',                 'PanelSMS20',        'PanelSMS20.php',        self::F_USER_PASS_SENDER);
        self::n('parandsms',          'پرند SMS (parandsms.ir)',                    'ParandSMS',         'ParandSMS.php',         self::F_USER_PASS_SENDER);
        self::n('pardissms',          'پردیس SMS (pardis.ssmss.ir)',                'PardisSMS',         'PardisSMS.php',         self::F_USER_PASS_SENDER);
        self::n('parsianpayam',       'پارسیان پیام (parsianpayam.ir)',             'ParsianPayam',      'ParsianPayam.php',      self::F_USER_PASS_SENDER);
        self::n('parsiansms',         'پارسیان SMS (parsian-sms.ir)',               'ParsianSMS',        'ParsianSMS.php',        self::F_USER_PASS_SENDER);
        self::n('parsiantd',          'پارسیان TD (sms.parsiantd.com)',             'ParsianTD',         'ParsianTD.php',         self::F_USER_PASS_SENDER);
        self::n('payamafraz',         'پیام افراز (payamafraz.com)',                'PayamAfraz',        'PayamAfraz.php',        self::F_USER_PASS_SENDER);
        self::n('payamresan',         'پیام‌رسان (payam-resan.com)',                'PayamResan',        'PayamResan.php',        self::F_USER_PASS_SENDER);
        self::n('payamsms',           'پیام SMS (payamsms.com)',                    'PayamSMS',          'PayamSMS.php',          self::F_USER_PASS_SENDER);
        self::n('payamakyab',         'پیامک یاب (payamakyab.com)',                 'PayamakYab',        'PayamakYab.php',        self::F_USER_PASS_SENDER);
        self::n('persian-sms',        'پرشین SMS (persian-sms.com)',                'PersianSMS',        'PersianSMS.php',        self::F_USER_PASS_SENDER);
        self::n('postgah',            'پستگاه (postgah.info)',                      'Postgah',           'Postgah.php',           self::F_USER_PASS_SENDER);
        self::n('raygansms',          'رایگان SMS (raygansms.com)',                 'RayganSMS',         'RayganSMS.php',         self::F_USER_PASS_SENDER);
        self::n('razpayamak',         'راز پیامک (razpayamak.com)',                 'RazPayamak',        'RazPayamak.php',        self::F_USER_PASS_SENDER);
        self::n('relax',              'ریلکس (relax.ir)',                            'Relax',             'Relax.php',             self::F_USER_PASS_SENDER);
        self::n('smsbefrest',         'SMS بفرست (SmsBefrest.ir)',                  'SMSBefrest',        'SMSBefrest.php',        self::F_USER_PASS_SENDER);
        self::n('smsfa',              'SMS فا (SMSFa.ir)',                          'SMSFa',             'SMSFa.php',             self::F_USER_PASS_SENDER);
        self::n('smsfor',             'SMS For (smsfor.ir)',                        'SMSFor',            'SMSFor.php',            self::F_USER_PASS_SENDER);
        self::n('smshooshmand',       'SMS هوشمند (smshooshmand.com)',              'SMSHooshmand',      'SMSHooshmand.php',      self::F_USER_PASS_SENDER);
        self::n('smsir_old',          'SMS.ir قدیمی',                               'SMSIR',             'SMSIR.php',             self::F_USER_PASS_SENDER);
        self::n('smsmeli',            'SMS ملی (SMS-Meli.com)',                     'SMSMeli',           'SMSMeli.php',           self::F_USER_PASS_SENDER);
        self::n('smsmelli',           'SMS ملی ایران (SMSMelli.com)',               'SMSMelli',          'SMSMelli.php',          self::F_USER_PASS_SENDER);
        self::n('smsnegar',           'SMS نگار (sms.smsnegar.com)',                'SMSNegarCOM',       'SMSNegarCOM.php',       self::F_USER_PASS_SENDER);
        self::n('smsnegarir',         'SMS نگار IR (smsnegar.ir)',                  'SMSNegarIR',        'SMSNegarIR.php',        self::F_USER_PASS_SENDER);
        self::n('smspishgaman',       'SMS پیشگامان (Panel.SmsPishgaman.com)',      'SMSPishgaman',      'SMSPishgaman.php',      self::F_USER_PASS_SENDER);
        self::n('ssmss',              'SSMSS (ssmss.ir)',                           'SSMSS',             'SSMSS.php',             self::F_USER_PASS_SENDER);
        self::n('sahandsms',          'سهند SMS (sahandsms.com)',                   'SahandSMS',         'SahandSMS.php',         self::F_USER_PASS_SENDER);
        self::n('samait',             'ساما IT (samait.ir)',                        'SamaIT',            'SamaIT.php',            self::F_USER_PASS_SENDER);
        self::n('satsms',             'ست SMS (satsms.ir)',                         'SatSMS',            'SatSMS.php',            self::F_USER_PASS_SENDER);
        self::n('sefidsms',           'سفید SMS (sefidsms.ir)',                     'SefidSMS',          'SefidSMS.php',          self::F_USER_PASS_SENDER);
        self::n('sepahansms',         'سپاهان SMS (sepahansms.com)',                'SepahanSMS',        'SepahanSMS.php',        self::F_USER_PASS_SENDER);
        self::n('signalads',          'سیگنال ادز (panel.signalads.com)',           'SignalAds',         'SignalAds.php',         self::F_USER_PASS_SENDER);
        self::n('sornasms',           'سرنا SMS (sornasms.net)',                    'SornaSMS',          'SornaSMS.php',          self::F_USER_PASS_SENDER);
        self::n('sunwaysms',          'سان‌وی SMS (sunwaysms.com)',                 'SunwaySMS',         'SunwaySMS.php',         self::F_USER_PASS_SENDER);
        self::n('tjp',                'TJP (TJP.ir)',                               'TJPIR',             'TJPIR.php',             self::F_USER_PASS_SENDER);
        self::n('tsms',               'تی SMS (tsms.ir)',                           'TSMS',              'TSMS.php',              self::F_USER_PASS_SENDER);
        self::n('trez',               'ترز (smspanel.trez.ir)',                     'Trez',              'Trez.php',              self::F_USER_PASS_SENDER);
        self::n('webone',             'وب‌وان (webone-sms.com)',                    'WebOne',            'WebOne.php',            self::F_USER_PASS_SENDER);
        self::n('websms',             'وب SMS (s1.websms.ir)',                      'WebSMS',            'WebSMS.php',            self::F_USER_PASS_SENDER);
        self::n('wikipayam',          'ویکی پیام (wikipayam.ir)',                   'WikiPayam',         'WikiPayam.php',         self::F_USER_PASS_SENDER);
        self::n('yektasms',           'یکتاتک (Yektatech.ir)',                      'YektaTech',         'YektaTech.php',         self::F_USER_PASS_SENDER);
        self::n('sms0098',            '0098 SMS (0098sms.com)',                     '_0098',             '_0098.php',             self::F_USER_PASS_SENDER);
        self::n('sms3300',            '3300 SMS (sms.3300.ir)',                     '_3300',             '_3300.php',             self::F_USER_PASS_SENDER);
    }

    /** Shorthand for registering a new-format gateway. */
    private static function n(
        string $id,
        string $name,
        string $class,
        string $file,
        array  $fields
    ): void {
        self::add([
            'id'     => $id,
            'name'   => $name,
            'class'  => $class,
            'file'   => $file,
            'type'   => 'new',
            'fields' => $fields,
        ]);
    }
}
