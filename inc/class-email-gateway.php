<?php
defined('ABSPATH') || exit;

class Nias_Email {
    private $email_gateway;
    private $smtp_host;
    private $smtp_user;
    private $smtp_pass;
    private $smtp_port;
    private $smtp_secure;
    
    // کانستراکتور
    public function __construct($email_gateway = '') {
        // اگر gateway خالی باشه از تنظیمات وردپرس بگیره
        $this->email_gateway = $email_gateway ?: get_option('nias_email_gateway');
        $this->load_settings();
    }
    
    // لود تنظیمات (از تنظیمات وردپرس)
    private function load_settings() {
        $this->smtp_host   = get_option('nias_smtp_host');
        $this->smtp_user   = get_option('nias_smtp_user');
        $this->smtp_pass   = get_option('nias_smtp_pass');
        $this->smtp_port   = get_option('nias_smtp_port');
        $this->smtp_secure = get_option('nias_smtp_secure');
    }
    
    // داخل کلاس
    public function send_email_gateway($email_phone, $email, $code, $subject = null, $message = null, $headers = []) {
        // اگر subject و message داده نشده، از قالب پیش‌فرض استفاده کن
        if (!$subject || !$message) {
            $default_code_template = function_exists('nias_default_email_code_template')
                ? nias_default_email_code_template()
                : '<p>کد ورود شما: <strong>{code}</strong></p>';
            $template = get_option('nias_email_message_template', $default_code_template);
            
            if (!mb_check_encoding($template, 'UTF-8')) {
                $template = mb_convert_encoding($template, 'UTF-8');
            }
            
            $replacements = [
                '{code}'        => $code,
                '{email}'       => esc_html($email),
                '{email_phone}' => esc_html($email_phone),
                '{site_name}'   => esc_html(get_bloginfo('name')),
                '{site_url}'    => esc_url(home_url('/')),
            ];
            
            $message = strtr($template, $replacements);
            $message = mb_convert_encoding($message, 'UTF-8', 'UTF-8');
            $subject = $subject ?: 'کد ورود';
        }
    
        switch ($this->email_gateway) {
            case 'wp_mail':
                return $this->send_wp_mail($email ?? $email_phone, $subject, $message, $headers);
            case 'phpmailer':
            case 'smtp':
                return $this->send_phpmailer($email ?? $email_phone, $subject, $message, $headers);
            default:
                return 'Email gateway not supported: ' . $this->email_gateway;
        }
    }
    
    private function send_wp_mail($to, $subject, $message, $headers = []) {
        // فقط Content-Type لازم است؛ PHPMailer خودش Content-Transfer-Encoding را تنظیم می‌کند.
        // افزودن دستی «Content-Transfer-Encoding: 8bit» باعث هدر تکراری و مشکل تحویل/اسپم می‌شود.
        $default_headers = ['Content-Type: text/html; charset=UTF-8'];
        $headers = array_merge($default_headers, $headers);

        $subject = html_entity_decode($subject, ENT_QUOTES, 'UTF-8');

        // ثبت دلیل خطای احتمالی wp_mail برای پیام دقیق‌تر
        $error_message = '';
        $capture = function ($wp_error) use (&$error_message) {
            if (is_wp_error($wp_error)) {
                $error_message = $wp_error->get_error_message();
            }
        };
        add_action('wp_mail_failed', $capture);

        $sent = wp_mail($to, $subject, $message, $headers);

        remove_action('wp_mail_failed', $capture);

        if ($sent) {
            return true;
        }

        return 'ارسال ایمیل با wp_mail انجام نشد' . ($error_message ? ' - ' . $error_message : '');
    }
    
    private function send_phpmailer($to, $subject, $message, $headers = []) {
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
            
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible">
                        <p>خطا: کلاس PHPMailer روی هاست شما فعال نیست.</p>
                    </div>';
                });
                return "خطا: PHPMailer روی هاست فعال نیست";
            }
        }
    
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isSMTP();
            $mail->Host       = $this->smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtp_user;
            $mail->Password   = $this->smtp_pass;
            $mail->SMTPSecure = $this->smtp_secure ?: 'tls';
            $mail->Port       = $this->smtp_port ?: 587;
    
            // From
            $from_name = get_bloginfo('name');
            $from_email = $this->smtp_user ?: get_option('admin_email');
            $mail->setFrom($from_email, $from_name);
    
            // To
            $mail->addAddress($to);
    
            // Headers سفارشی (مثل Reply-To)
            foreach ($headers as $header) {
                if (stripos($header, 'Reply-To:') === 0) {
                    $reply_to = trim(str_replace('Reply-To:', '', $header));
                    if (strpos($reply_to, '<') !== false) {
                        preg_match('/<(.*)>/', $reply_to, $matches);
                        $email = $matches[1] ?? $reply_to;
                        $name = trim(str_replace("<$email>", '', $reply_to));
                        $mail->addReplyTo($email, $name);
                    } else {
                        $mail->addReplyTo($reply_to);
                    }
                }
            }
    
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
    
            $mail->send();
            return "ایمیل با موفقیت ارسال شد";
    
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
            return "ارسال ایمیل ناموفق بود: " . $e->getMessage();
        }
    }
}