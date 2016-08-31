<?php 
	class DictionaryCurl {
		
		public static function get($url) {
			return self::call($url, 'POST', array(), Config::get('avazuservice_dict_cachetime'));
		}
		
		private static function call($url, $method = 'GET', $postParams = array(), $cache = 0) {
			$result = '';
			
			if ($cache > 0) {
				$result = Cache_Memcached::sGet($url);
				if (!empty($result)) {
					return $result;
				}
			}
			try {
				$result = http_post($url, $postParams);
				if( $cache > 0) {
					Cache_Memcached::sSet($url, $result, 3600);
				}
			} catch (Exception $e) {
				$result = '';
			}
			
			return $result;			
		}
	}
?>