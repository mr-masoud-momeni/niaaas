<?php

namespace Nias\PWSMS\Gateways;

class PanelChi implements GatewayInterface {
	use GatewayTrait;

	/**
	 * پایهٔ REST API جدید پنل‌چی.
	 *
	 * @var string
	 */
	public string $api_url = 'https://api.panelchi.com';

	/**
	 * @var array
	 */
	public array $failed_numbers = [];

	public static function id() {
		return 'panelchi';
	}

	public static function name() {
		return 'panelchi.com';
	}

	public function send() {

		$message_content = trim( $this->message );
		$sender_number   = trim( $this->senderNumber );
		$recipients      = $this->mobile;

		// توکن از فیلد نام کاربری (یا در نبودِ آن، رمز عبور) خوانده می‌شود.
		$token = ! empty( $this->username ) ? trim( $this->username ) : trim( $this->password );

		$this->failed_numbers = []; // Reset the property for each send operation

		if ( empty( $token ) ) {
			foreach ( $recipients as $recipient ) {
				$this->failed_numbers[ $recipient ] = 'توکن درگاه پنل‌چی وارد نشده است.';
			}

			return $this->build_error_message();
		}

		// سازگاری با فرمت قدیمی: pcode → patterncode
		$message_content = str_replace( 'pcode', 'patterncode', $message_content );

		if ( substr( $message_content, 0, 11 ) === 'patterncode' ) {

			[ $pattern_slug, $variables ] = $this->parse_pattern_message( $message_content );

			if ( empty( $pattern_slug ) ) {
				foreach ( $recipients as $recipient ) {
					$this->failed_numbers[ $recipient ] = 'اسلاگ پترن نامعتبر است.';
				}

				return $this->build_error_message();
			}

			foreach ( $recipients as $recipient ) {
				$body = [
					'pattern'      => $pattern_slug,
					'variables'    => (object) $variables, // تضمین تولید {} به‌جای [] در JSON
					'recipient'    => $recipient,
					'sourceNumber' => $sender_number,
				];

				$response = $this->post( '/sms/pattern', $token, $body );
				$this->handle_response( $response, $recipient );
			}

		} else {
			// REST API جدید پنل‌چی برای این پلاگین فقط ارسال پترن را مستند کرده است.
			foreach ( $recipients as $recipient ) {
				$this->failed_numbers[ $recipient ] = 'ارسال پیامک متنی آزاد از طریق درگاه پنل‌چی پشتیبانی نمی‌شود؛ لطفاً از قالب (پترن) استفاده کنید.';
			}
		}

		return $this->build_error_message();
	}

	/**
	 * پیامِ ساخته‌شده توسط آداپتر (patterncode:SLUG\nKEY:VALUE...) را به
	 * اسلاگ پترن و آرایهٔ متغیرها تجزیه می‌کند.
	 *
	 * @param string $message_content
	 *
	 * @return array{0:string,1:array} [ اسلاگ پترن، متغیرها ]
	 */
	private function parse_pattern_message( string $message_content ): array {
		$normalized    = str_replace( [ "\r\n", "\n" ], ';', $message_content );
		$message_parts = explode( ';', $normalized );
		if ( count( $message_parts ) === 1 ) {
			$message_parts = explode( ' ', $normalized );
		}

		$pattern_slug = explode( ':', $message_parts[0] )[1] ?? '';
		$pattern_slug = trim( $pattern_slug );
		unset( $message_parts[0] );

		$variables = [];
		foreach ( $message_parts as $parameter ) {
			$split_parameter = explode( ':', $parameter, 2 ); // فقط روی اولین ':' جدا شود
			if ( count( $split_parameter ) === 2 ) {
				$variables[ trim( $split_parameter[0] ) ] = trim( $split_parameter[1] );
			}
		}

		return [ $pattern_slug, $variables ];
	}

	/**
	 * ارسال درخواست POST با بدنهٔ JSON و احراز هویت Bearer.
	 *
	 * @param string $path
	 * @param string $token
	 * @param array  $body
	 *
	 * @return array|\WP_Error
	 */
	private function post( string $path, string $token, array $body ) {
		return wp_remote_post( $this->api_url . $path, [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $token,
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
		] );
	}

	/**
	 * پاسخِ هر گیرنده را بررسی و در صورت خطا آن را ثبت می‌کند.
	 *
	 * @param array|\WP_Error $response
	 * @param string          $recipient
	 */
	private function handle_response( $response, $recipient ) {

		if ( is_wp_error( $response ) ) {
			$this->failed_numbers[ $recipient ] = $response->get_error_message();

			return;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );

		// 200/201 → موفق
		if ( $status === 200 || $status === 201 ) {
			return;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		$domain_code = ( is_array( $data ) && isset( $data['code'] ) && is_numeric( $data['code'] ) )
			? (int) $data['code']
			: null;

		$domain_messages = [
			1003 => 'وضعیت حساب کاربری شما فعال نمی‌باشد.',
			2001 => 'موجودی کیف پول شما کافی نمی‌باشد.',
			3002 => 'این پترن نسخهٔ فعالی ندارد.',
			3011 => 'ارسال پیامک موقتاً غیرفعال می‌باشد.',
			3013 => 'سرشمارهٔ انتخاب‌شده برای ارسال از طریق وب‌سرویس نامعتبر است.',
			3014 => 'مقداری برای یکی از متغیرهای پترن داده نشده است.',
			3015 => 'مقدار یکی از متغیرهای پترن نامعتبر است.',
			4004 => 'سرشمارهٔ انتخاب‌شده برای ارسال از طریق وب‌سرویس نامعتبر است.',
		];

		if ( $domain_code !== null && isset( $domain_messages[ $domain_code ] ) ) {
			$this->failed_numbers[ $recipient ] = $domain_messages[ $domain_code ];

			return;
		}

		// خطاهای احراز هویت/دسترسی
		if ( $status === 401 ) {
			$this->failed_numbers[ $recipient ] = 'توکن احراز هویت نامعتبر است (۴۰۱).';

			return;
		}
		if ( $status === 403 ) {
			$this->failed_numbers[ $recipient ] = 'دسترسی مجاز نیست (۴۰۳).';

			return;
		}

		// در غیر این صورت از detail استاندارد (RFC 7807) یا کد وضعیت استفاده می‌کنیم.
		$detail = ( is_array( $data ) && ! empty( $data['detail'] ) ) ? $data['detail'] : null;

		$this->failed_numbers[ $recipient ] = $detail ?: ( 'خطای وب‌سرویس (کد ' . $status . ').' );
	}

	/**
	 * در صورت نبودِ خطا true برمی‌گرداند؛ در غیر این صورت خطاها را
	 * بر اساس پیام گروه‌بندی کرده و به‌صورت رشته برمی‌گرداند.
	 *
	 * @return true|string
	 */
	private function build_error_message() {

		if ( empty( $this->failed_numbers ) ) {
			return true;
		}

		// گروه‌بندی شماره‌ها بر اساس پیام خطا
		$grouped = [];
		foreach ( $this->failed_numbers as $number => $message ) {
			if ( ! isset( $grouped[ $message ] ) ) {
				$grouped[ $message ] = [];
			}
			$grouped[ $message ][] = $number;
		}

		return implode( ', ', array_map(
			function ( string $message, array $numbers ) {
				return implode( ',', $numbers ) . ': ' . $message;
			},
			array_keys( $grouped ),
			$grouped
		) );
	}
}
