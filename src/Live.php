<?php

/**
 * Live Connect strategy for Opauth
 *
 * More information on Opauth: http://opauth.org
 *
 * @copyright    Copyright © 2012 U-Zyn Chua (http://uzyn.com)
 * @link         http://opauth.org
 * @package      Opauth.LiveStrategy
 * @license      MIT License
 */

namespace Opauth\Live\Strategy;

use Opauth\Opauth\AbstractStrategy;

/**
 * Live Connect strategy for Opauth
 *
 * @package			Opauth.Live
 */
class Live extends AbstractStrategy {

	/**
	 * Compulsory config keys, listed as unassociative arrays
	 * eg. array('app_id', 'app_secret');
	 */
	public $expects = array('client_id', 'client_secret');

	/**
	 * Optional config keys, without predefining any default values.
	 */
	public $optionals = array('redirect_uri', 'scope', 'state');

	/**
	 * Optional config keys with respective default values, listed as associative arrays
	 * eg. array('scope' => 'email');
	 */
	public $defaults = array(
		'scope' => 'wl.basic'
	);

	public $responseMap = array(
		'uid' => 'id',
		'name' => 'name',
		'info.name' => 'name',
		'info.first_name' => 'first_name',
		'info.last_name' => 'last_name',
		'info.urls.live_profile' => 'link',
		'info.email' => 'emails.preferred'

	);

	/**
	 * Auth request
	 */
	public function request() {
		$url = 'https://login.live.com/oauth20_authorize.srf';

		$params = array(
			'redirect_uri' => $this->callbackUrl(),
			'response_type' => 'code',
		);
		$params = $this->addParams(array('client_id', 'scope'), $params);
		$params = $this->addParams($this->optionals, $params);


		$this->http->redirect($url, $params);
	}

	/**
	 * Internal callback, after Live Connect's request
	 */
	public function callback() {
		$callbackTime = time();
		if (!array_key_exists('code', $_GET) || empty($_GET['code'])) {
			return $this->response($_GET, array('code' => 'oauth2callback_error'));
		}

		$url = 'https://login.live.com/oauth20_token.srf';
		$params = array(
			'redirect_uri' => $this->callbackUrl(),
			'grant_type' => 'authorization_code',
			'code' => trim($_GET['code'])
		);
		$params = $this->addParams(array('client_id', 'client_secret', 'state'), $params);

		$response = $this->http->post($url, $params);

		$results = json_decode($response);

		if (empty($results->access_token)) {
			$error = array(
				'code' => 'access_token_error',
				'message' => 'Failed when attempting to obtain access token',
			);
			return $this->response($response, $error);
		}

		$data = array('access_token' => $results->access_token);
		$user = $this->http->get('https://apis.live.net/v5.0/me', $data);
		$user = $this->recursiveGetObjectVars(json_decode($user));

		if (empty($user) || isset($user['message'])) {
			$error = array(
				'code' => 'userinfo_error',
				'message' => 'Failed when attempting to query Live Connect API for user information'
			);
			if (isset($user['message'])) {
				$error['message'] = $user['message'];
			}
			return $this->response($user, $error);
		}

		$response = $this->response($user);
		$response->credentials = array(
			'token' => $results->access_token,
			'authentication_token' => $results->authentication_token,
			'expires' => date('c', $callbackTime + $results->expires_in)
		);
		$response->info['image'] = 'https://apis.live.net/v5.0/' . $user['id'] . '/picture';

		return $response;
	}

}