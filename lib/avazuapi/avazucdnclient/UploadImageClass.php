<?php 
	define('OBJECT_OPERATIONS_URL',		'http://api.cdn.avazu.net/object/objectoperations');
	define('REQUEST_METHOD_GET',		'get');
	define('REQUEST_METHOD_POST',		'post');
	define('REQUEST_METHOD_DELETE',		'delete');
	define('MAX_TIMEOUT',				1800);

	error_reporting(0);
	
	class UploadImageClass {
		private $token = NULL;
		private $isSuccess = TRUE;
		private $errorMsg = NULL;
		
		public function getFile($fileName) {
			$requestHeader = array();
			$requestHeader[] = 'token: ' . $this->token;
			$requestHeader[] = 'filename: ' . $fileName;

			$data = $this->callAPI(REQUEST_METHOD_GET, $requestHeader);
			
			if (!$this->isSuccess) {
				return NULL;
			}
			
			$result = array();
			
			$this->buildGetResult($data['header'], $result, 'checksum');
			$this->buildGetResult($data['header'], $result, 'fileurl');
			$this->buildGetResult($data['header'], $result, 'creationtime');
			$this->buildGetResult($data['header'], $result, 'updatetime');
			$this->buildGetResult($data['header'], $result, 'filesize');
			$this->buildGetResult($data['header'], $result, 'imagewidth');
			$this->buildGetResult($data['header'], $result, 'imageheight');
			
			return $result;
		}
		
		public function uploadFile($fileFullPath, $fileTypes) {
			$requestHeader = array();
			$requestHeader[] = 'token: ' . $this->token;
			$requestHeader[] = 'fileTypes: ' . $fileTypes;
			
			$postData = array('Filedata' => '@' . $fileFullPath);
			
			$data = $this->callAPI(REQUEST_METHOD_POST, $requestHeader, $postData);
			
			if (!$this->isSuccess) {
				return NULL;
			}

			return $data['body'];
		}
		
		public function deleteFile($fileName) {
			$requestHeader = array();
			$requestHeader[] = 'token: ' . $this->token;
			$requestHeader[] = 'filename: ' . $fileName;
			
			$data = $this->callAPI(REQUEST_METHOD_DELETE, $requestHeader);
			
			if (!$this->isSuccess) {
				return FALSE;
			}
			
			return $data['header']['deletestatus'] == 1;
		}
		
		public function operationSuccess() {
			return $this->isSuccess;
		}
		
		public function erreoMsg() {
			return $this->errorMsg;
		}
		
		public function __construct($token) {
			$this->token = $token;
			$this->isSuccess = TRUE;
			$this->errorMsg = NULL;
		}
		
		private function callAPI($requestMethod, $requestHeader, $postData = NULL) {
			try {
				$ch = $this->initCurl();
				
				if ($requestMethod == REQUEST_METHOD_POST) {
					curl_setopt($ch, CURLOPT_POST, FALSE);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
				} else if ($requestMethod == REQUEST_METHOD_DELETE) {
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				}
				
				curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeader);
				
				$response = curl_exec($ch);
				
				if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') {
					$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
					$header = $this->parseHeaderStr(substr($response, 0, $headerSize));
					
					if (isset($header['apiexception'])) {
						$this->isSuccess = FALSE;
						$this->errorMsg = $header['apiexception'];
					
						return NULL;
					}
					
					return array(
							'header'	=> $header,
							'body'		=> substr($response, $headerSize)
						);
				}
			} catch(Exception $e) {}
			
			$this->isSuccess = FALSE;
			$this->errorMsg = 'api return empty';
			
			return NULL;
		}
		
		private function initCurl() {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, OBJECT_OPERATIONS_URL);
			curl_setopt($ch, CURLOPT_HEADER, TRUE);
			curl_setopt($ch, CURLOPT_NOBODY, FALSE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
			curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
			curl_setopt($ch, CURLOPT_TIMEOUT, MAX_TIMEOUT);
			
			return $ch;
		}
		
		private function parseHeaderStr($str) {
			$result = array();
			$lines = explode("\r\n", $str);
			
			foreach ($lines as $line) {
				$tmp = explode(':', $line, 2);
				
				if (is_array($tmp) && count($tmp) == 2) {
					$result[$tmp[0]] = trim($tmp[1]);
				}
			}
			
			return $result;
		}
		
		private function buildGetResult($header, &$result, $key) {
			if (isset($header[$key])) {
				$result[$key] = $header[$key];
			}
		}
	}
?>