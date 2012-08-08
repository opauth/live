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
		'redirect_uri' => '{complete_url_to_strategy}oauth2callback'
	);
	
	/**
	 * Auth request
	 */
	public function request(){
		$url = 'https://login.live.com/oauth20_authorize.srf';
		
		$params = array(
			'client_id' => $this->strategy['client_id'],
			'redirect_uri' => $this->strategy['redirect_uri'],
			'response_type' => 'code'
		);

		foreach ($this->optionals as $key) {
			if (!empty($this->strategy[$key])) $params[$key] = $this->strategy[$key];
		}
		
		// redirect to generated url
		$this->clientGet($url, $params);
	}
	
	/**
	 * Internal callback, after Instagram's OAuth
	 */
	public function oauth2callback(){
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
			
			print_r($response);
			exit();
			
			$results = json_decode($response);
			
			if (!empty($results) && !empty($results['access_token'])) {
				$user = $this->user($results['access_token']);
				
				$this->auth = array(
					'uid' => $user['id'],
					'info' => array(),
					'credentials' => array(
						'token' => $results['access_token']
					),
					'raw' => $user
				);
				
				$this->mapProfile($user, 'name', 'info.name');
				$this->mapProfile($user, 'blog', 'info.urls.blog');
				$this->mapProfile($user, 'avatar_url', 'info.image');
				$this->mapProfile($user, 'bio', 'info.description');
				$this->mapProfile($user, 'login', 'info.nickname');
				$this->mapProfile($user, 'html_url', 'info.urls.github');
				$this->mapProfile($user, 'email', 'info.email');
				$this->mapProfile($user, 'location', 'info.location');
				$this->mapProfile($user, 'url', 'info.urls.github_api');
				
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
	 * Queries GitHub v3 API for user info
	 *
	 * @param string $access_token 
	 * @return array Parsed JSON results
	 */
	private function user($access_token) {
		$user = $this->serverGet('https://api.github.com/user', array('access_token' => $access_token), null, $headers);

		if (!empty($user)) {
			return $this->recursiveGetObjectVars(json_decode($user));
		}
		else {
			$error = array(
				'code' => 'userinfo_error',
				'message' => 'Failed when attempting to query GitHub v3 API for user information',
				'raw' => array(
					'response' => $user,
					'headers' => $headers
				)
			);

			$this->errorCallback($error);
		}
	}
}