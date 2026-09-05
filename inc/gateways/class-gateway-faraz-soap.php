<?php
defined('ABSPATH') || exit;

class Nias_Gateway_Faraz_Soap extends Nias_Abstract_Gateway
{
    public function send(string $phone, string $template, array $params)
    {
        $username   = $this->settings['username'];
        $password   = $this->settings['password'];
        $from       = $this->settings['local_number'];
        $input_data = [];

        foreach ($params as $key => $value) {
            $input_data[$key] = (string) $value;
        }

        try {
            $client = new SoapClient(
                'https://ippanel.com/class/sms/wsdlservice/server.php?wsdl',
                [
                    'encoding'           => 'UTF-8',
                    'cache_wsdl'         => WSDL_CACHE_NONE,
                    'trace'              => true,
                    'exceptions'         => true,
                    'connection_timeout' => 30,
                    'stream_context'     => stream_context_create([
                        'ssl' => [
                            'verify_peer'      => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                    ]),
                ]
            );

            $result = $client->sendPatternSms($from, [$phone], $username, $password, $template, $input_data);

            if (is_numeric($result)) {
                return true;
            }

            // برخی پاسخ‌های خطا از سرویس به‌صورت UTF-8 برمی‌گردند؛ تبدیل از Windows-1256
            // در این حالت متن را به‌هم می‌ریزد، بنابراین فقط در صورتی تبدیل می‌کنیم
            // که رشته از قبل UTF-8 معتبر نباشد.
            if (mb_check_encoding($result, 'UTF-8')) {
                return $result;
            }

            return @iconv('Windows-1256', 'UTF-8//IGNORE', $result);
        } catch (\Exception $e) {
            return 'SOAP Error: ' . $e->getMessage();
        }
    }

    public function send_text(string $phone, string $text)
    {
        $username = $this->settings['username'];
        $password = $this->settings['password'];
        $from     = $this->settings['local_number'];

        if (empty($username) || empty($password) || empty($from)) {
            return 'نام کاربری، رمز یا شماره ارسال‌کننده فراز وارد نشده است';
        }

        try {
            $client = new SoapClient(
                'https://ippanel.com/class/sms/wsdlservice/server.php?wsdl',
                [
                    'encoding'           => 'UTF-8',
                    'cache_wsdl'         => WSDL_CACHE_NONE,
                    'trace'              => true,
                    'exceptions'         => true,
                    'connection_timeout' => 30,
                    'stream_context'     => stream_context_create([
                        'ssl' => [
                            'verify_peer'      => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                    ]),
                ]
            );

            $result = $client->sendSms($from, [$phone], $username, $password, $text);

            if (is_numeric($result)) {
                return true;
            }

            if (mb_check_encoding($result, 'UTF-8')) {
                return $result;
            }

            return @iconv('Windows-1256', 'UTF-8//IGNORE', $result);
        } catch (\Exception $e) {
            return 'SOAP Error: ' . $e->getMessage();
        }
    }
}
