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

/**
 * Live Connect strategy for Opauth
 * 
 * @package			Opauth.Live
 */ 
class LiveStrategy extends OpauthStrategy {
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
		'redirect_uri' => '{complete_url_to_strategy}oauth2callback',
		'scope' => 'wl.basic'
	);
	
	/**
	 * Auth request
	 */
	public function request(){
		$url = 'https://login.live.com/oauth20_authorize.srf';
		
		$params = array(
			'client_id' => $this->strategy['client_id'],
			'redirect_uri' => $this->strategy['redirect_uri'],
			'response_type' => 'code',
			'scope' => $this->strategy['scope']
		);

		foreach ($this->optionals as $key) {
			if (!empty($this->strategy[$key])) $params[$key] = $this->strategy[$key];
		}
		
		// redirect to generated url
		$this->clientGet($url, $params);
	}
	
	/**
	 * Internal callback, after Live Connect's request
	 */
	public function oauth2callback(){
		$callbackTime = time();
		if (array_key_exists('code', $_GET) && !empty($_GET['code'])){
			$url = 'https://login.live.com/oauth20_token.srf';
			
			$params = array(
				'client_id' =>$this->strategy['client_id'],
				'client_secret' => $this->strategy['client_secret'],
				'redirect_uri'=> $this->strategy['redirect_uri'],
				'grant_type' => 'authorization_code',
				'code' => trim($_GET['code'])
			);
			if (!empty($this->strategy['state'])) $params['state'] = $this->strategy['state'];
			$response = $this->serverPost($url, $params, null, $headers);
			
			$results = json_decode($response);
			
			if (!empty($results) && !empty($results->access_token)) {
				$me = $this->me($results->access_token);
				
				$this->auth = array(
					'uid' => $me['id'],
					'info' => array(
						'image' => 'https://apis.live.net/v5.0/'.$me['id'].'/picture'
					),
					'credentials' => array(
						'token' => $results->access_token,
						'authentication_token' => $results->authentication_token,
						'expires' => date('c', $callbackTime + $results->expires_in)
					),
					'raw' => $me
				);
				
				$this->mapProfile($me, 'name', 'info.name');
				$this->mapProfile($me, 'first_name', 'info.first_name');
				$this->mapProfile($me, 'last_name', 'info.last_name');
				$this->mapProfile($me, 'link', 'info.urls.live_profile');
				$this->mapProfile($me, 'emails.preferred', 'info.email');
				$this->callback();
			}
			else {
				$error = array(
					'code' => 'access_token_error',
					'message' => 'Failed when attempting to obtain access token',
					'raw' => array(
						'response' => $response,
						'headers' => $headers
					)
				);

				$this->errorCallback($error);
			}
		}
		else {
			$error = array(
				'code' => 'oauth2callback_error',
				'raw' => $_GET
			);
			
			$this->errorCallback($error);
		}
	}
	
	/**
	 * Queries Live Connect API for user info
	 *
	 * @param string $access_token 
	 * @return array Parsed JSON results
	 */
	private function me($access_token) {
		$me = $this->serverGet('https://apis.live.net/v5.0/me', array('access_token' => $access_token), null, $headers);

		if (!empty($me)) {
			return $this->recursiveGetObjectVars(json_decode($me));
		}
		else {
			$error = array(
				'code' => 'userinfo_error',
				'message' => 'Failed when attempting to query Live Connect API for user information',
				'raw' => array(
					'response' => $me,
					'headers' => $headers
				)
			);

			$this->errorCallback($error);
		}
	}
}