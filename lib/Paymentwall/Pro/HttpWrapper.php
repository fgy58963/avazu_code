<?php

class Paymentwall_Pro_HttpWrapper extends Paymentwall_Base
{
	/**
	 * @var resource cURL handle
	 */
	protected $curl = null;

	/**
	 * @var array cURL options
	 */
	protected $options = array();

	/**
	 * @var array GET/POST parameters to send
	 */
	protected $requestParams = array();

	/**
	 * @var string cURL response data
	 */
	protected $response;

	/**
	 * @var string HTTP response status
	 */
	protected $status;

	/**
	 * Initialize cURL handler
	 *
	 * @param array $attributes - Charge details
	 */
	public function __construct($attributes) {

		$ipAddress = $_SERVER['REMOTE_ADDR'];

		if (!empty($attributes)) {
			$this->requestParams = $attributes;
			$this->requestParams['browser_ip'] = $ipAddress;
		}

		if (!extension_loaded('curl')) {
			$this->appendToErrors('curl extension is missing');
		}

		$this->curl = curl_init();
	}

	/**
	 * Close cURL handler
	 */
	public function __destruct() {
		if (is_resource($this->curl)) {
			curl_close($this->curl);
		}
		$this->curl = null;
	}

	/**
	 * Handle HTTP GET request
	 */
	public function get() {}

	/**
	 * Handle HTTP POST request
	 *
	 * @return array
	 */
	public function post() {
		$params = $this->requestParams;

		$url = self::CHARGE_URL;
		
		if (isset($params['period']) && isset($params['period_duration'])) {
			$url = self::SUBS_URL;
		}
		
		$response = $this->_handleRequest('POST', $url);
		return $this->_wrapResponseBody($response['body']);
	}

	/**
	 * Handle PUT HTTP request
	 */
	public function put() {}

	/**
	 * Handle DELETE HTTP request
	 */
	public function delete() {}

	/**
	 * Do HTTP request 
	 *
	 * @param $httpVerb string - HTTP request type
	 * @param $url string - API URL
	 * @return array
	 */
	private function _handleRequest($httpVerb, $url) {

		$this->options = array(
			CURLOPT_URL => $url,
			CURLOPT_CUSTOMREQUEST => $httpVerb,
			CURLOPT_TIMEOUT => 40,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => $this->requestParams,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HTTPHEADER => array(
				'X-Apikey: ' . $this->getProApiKey()
			)
		);

		curl_setopt_array($this->curl, $this->options);

		$this->response = curl_exec($this->curl);
		$this->status = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

		return array(
			'status' => $this->status,
			'body' => $this->response
		);
	}

	private function _wrapResponseBody($response) {
		return $this->_parseResponse($response);
	}

	private function _parseResponse($response) {
		$response = json_decode($response, true);

		if (isset($response)) {
			if (!Paymentwall_Pro_Error::isError($response)) {
				return $response;
			} else {
				return Paymentwall_Pro_Error::wrapError($response);
			}
		} else {
			return Paymentwall_Pro_Error::wrapInternalError($response);
		}
	}

}
