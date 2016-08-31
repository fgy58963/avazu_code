<?php 
	class OS {
		private static $isAliasInit = FALSE;
		private static $isInit = FALSE;
		private static $jsonStr = '';
		private static $oss = array();
		private static $aliasOss = array();
		
		public static function getAllOss() {
			self::init();
		
			return self::$oss;
		}
		
		public static function getAllAliasOss() {
			self::_aliasInit();
		
			return self::$aliasOss;
		}
		
		public static function outputJS($jsVarName = '_oss') {
			self::init();
		
			header("Content-type:application/javascript; charset=utf-8");
			echo 'var ' . $jsVarName . ' = ' . self::$jsonStr . '';
		}
		
		private static function init() {
			if (self::$isInit) {
				return;
			}
			$defaultStr = '{"100":"Android","101":"Bada OS","102":"Desktop","103":"FireFox OS","104":"Hiptop OS","105":"Linux Smartphone OS","106":"MTK\/Nucleus OS","107":"MeeGo","108":"Palm OS","109":"RIM OS","110":"RIM Tablet OS","111":"Rex Qualcomm OS","112":"Symbian OS","113":"Windows CE","114":"Windows Mobile OS","115":"Windows Phone OS","116":"Windows RT","117":"iOS","118":"webOS"}';
			$resultStr = DictionaryCurl::get(API_OS_URL);
		
			if (empty($resultStr)) {
				self::$jsonStr = $defaultStr;
			} else {
				try {
					$obj = json_decode($resultStr);
					
					if ($obj->code == 0) {
						$arr = array();
						
						foreach ($obj->data as $k => $item) {
							$arr[$item->id] = $item->name;
						}
						
						asort($arr);
						
						self::$jsonStr = json_encode($arr);
					} else {
						self::$jsonStr = $defaultStr;
					}
				} catch(Exception $e) {
					self::$jsonStr = $defaultStr;
				}
			}
		
			self::$oss = json_decode(self::$jsonStr, TRUE);
		
			self::$isInit = TRUE;
		}

		private static function _aliasInit() {
			if (self::$isAliasInit) {
				return;
			}
			$defaultStr = '[{"id":100,"name":"Android","aliasname":["Android"]},{"id":101,"name":"Bada OS","aliasname":["Bada OS","Bada_OS"]},{"id":102,"name":"Desktop","aliasname":["Desktop"]},{"id":103,"name":"FireFox OS","aliasname":["FireFox OS"]},{"id":104,"name":"Hiptop OS","aliasname":["Hiptop OS","Hiptop_OS"]},{"id":105,"name":"Linux Smartphone OS","aliasname":["Linux Smartphone OS","Linux_Smartphone_OS"]},{"id":106,"name":"MTK\/Nucleus OS","aliasname":["MTK\/Nucleus OS","MTK_Nucleus_OS"]},{"id":107,"name":"MeeGo","aliasname":["MeeGo"]},{"id":108,"name":"Palm OS","aliasname":["Palm OS","Palm_OS"]},{"id":109,"name":"RIM OS","aliasname":["RIM OS","RIM_OS"]},{"id":110,"name":"RIM Tablet OS","aliasname":["RIM Tablet OS","RIM_Tablet_OS"]},{"id":111,"name":"Rex Qualcomm OS","aliasname":["Rex Qualcomm OS","Rex_Qualcomm_OS"]},{"id":112,"name":"Symbian OS","aliasname":{"1":"Nokia_OS","0":"Symbian OS","2":"Symbian_OS"}},{"id":113,"name":"Windows CE","aliasname":["Windows CE","Windows_CE"]},{"id":114,"name":"Windows Mobile OS","aliasname":["Windows Mobile OS","Windows_Mobile_OS"]},{"id":115,"name":"Windows Phone OS","aliasname":["Windows Phone OS","Windows_Phone_OS"]},{"id":116,"name":"Windows RT","aliasname":["Windows RT","Windows_RT"]},{"id":117,"name":"iOS","aliasname":{"0":"iOS","2":"iPhone OS","1":"iPhone_OS"}},{"id":118,"name":"webOS","aliasname":["webOS"]}]';
			$resultStr = DictionaryCurl::get(API_OS_URL);

			// echo $resultStr;exit('sss');
		
			if (empty($resultStr)) {
				self::$jsonStr = $defaultStr;
			} else {
				try {
					$obj = json_decode($resultStr);
					
					if ($obj->code == 0) {
						$arr = array();
						
						foreach ($obj->data as $k => $item) {
							foreach($item->aliasname as $osname) {
								$arr[strtolower($osname)] = $item->id;
							}
						}
						// asort($arr);
						self::$jsonStr = json_encode($arr);
					} else {
						self::$jsonStr = $defaultStr;
					}
				} catch(Exception $e) {
					self::$jsonStr = $defaultStr;
				}
			}
		
			self::$aliasOss = json_decode(self::$jsonStr, TRUE);
		
			self::$isAliasInit = TRUE;
		}
	}
?>