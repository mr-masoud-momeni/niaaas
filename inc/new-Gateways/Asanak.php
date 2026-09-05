<?php

namespace Nias\PWSMS\Gateways;

use PW\PWSMS\PWSMS;
use SoapClient;
use SoapFault;

class Asanak implements GatewayInterface {
	use GatewayTrait;

	public string $api_url = 'https://sms.asanak.ir/webservice/v2rest';

	public static function id() {
		return 'asanak';
	}

	public static function name() {
		return 'asanak.ir';
	}

	public function send() {
		$username = $this->username;
		$password = $this->password;
		$from     = $this->senderNumber;
		$to       = $this->mobile;
		$massage  = $this->message;

		if ( empty( $username ) || empty( $password ) ) {
			return false;
		}

		$to  = implode( ',', $to );
		$to  = str_ireplace( '+98', '0', $to );
		$url = $this->api_url . '/sendsms';

		$data = [
			'username'    => $username,
			'password'    => $password,
			'destination' => $to,
			'source'      => $from,
			'message'     => $massage,
		];

		$args = [
			'body'    => $data,
			'headers' => [
				'cache-control' => 'no-cache',
				'Content-Type'  => 'application/x-www-form-urlencoded'
			],
		];

		$remote = wp_remote_post( $url, $args );

		if ( is_wp_error( $remote ) ) {
			return "خطا: " . $remote->get_error_message();
		}

		$response = wp_remote_retrieve_body( $remote );
		$response = json_decode( $response );

		if ( json_last_error() ) {
			return 'پاسخ نامعتبر از سمت وبسرویس.';
		}

		if ( isset( $response->meta ) && isset( $response->meta->status ) && $response->meta->status == 200 ) {
			return true;
		}

		return $response;
	}
}
