<?php 
	class Brand {
		private static $isInit = FALSE;
		private static $jsonStr = '';
		private static $brands = array();
		
		public static function getAllBrands() {
			self::init();
		
			return self::$brands;
		}
		
		public static function outputJS($jsVarName = '_brands') {
			self::init();
		
			header("Content-type:application/javascript; charset=utf-8");
			echo 'var ' . $jsVarName . ' = ' . self::$jsonStr . '';
		}
		
		private static function init() {
			if (self::$isInit) {
				return;
			}
			$defaultStr = '["2 Degrees","35Phone","AI","ALCATEL","AMOI","AOC","AVG","Access","Acer","AddThis","Advan","Ahong","Aiko","Ainol","Airis","Airness","AllWinner","AlphaCell Wireless","Amazon","Amlogic","Android","AnexTek","Anonymouse","Aole","Apanda","Apple","Arcelik","Archos","Arnova","Asmobile","Asus","Audiovox","Avvio","Azumi","B-Mobile","BASE","BBK","BEKO","BP","BQ","Barnes and Noble","BayMobile","Becker","Beetel","Bellwave","BenQ","BenQ-Siemens","Best Buy International","Bing","Bird","BlackBerry","Bleu","Blue Coat","Bluestar","Boxchip","Brilliantel Mobile Communications","CDM","CECT","CSL","Camangi","Capitel","Carrefour","Casio","Celkon","Cellon","CheckCom","Cheer","Chunghwa","Cingular","Ciphone","Cking","Claro","Coby","Commtiva","Compal","Coolpad","CoralWeb","Cowon","Craig","Creative","Cricket","Curtis","Cydle","DBTEL","DC","DIFRNCE","DaPeng","Dai Telecom","Dallab","Danger","Daxian","Dell","Desktop","DiGix","Dicam","Diyomate","Dmobo","DoCoMo","Dopod","Doris","Double Power","Dropad","EDL","ETEN","EZIO","Easypix","Eken","Elite","Elocity","Elson","Ematic","Emblaze","Emerson","Emgeton","Emobile","Enspert","Enteos","Entourage","Epad","Epiphany","Era","Ereneben","Ericsson","Ericy","Ezze","FCT","FLY","FaceBook","Faktor Zwei","Feiteng","Fitel","Flying","Flytouch","Freelander","Fuhu","Fujitsu","Fujitsu Toshiba","G-Fone","GHT","GM","GO Live","Garmin-Asus","GeeksPhone","General Mobile","Generic","Generic Windows","Genesis Tab","Gigabyte","Ginovo","Gionee","Google","Gradiente","Grameenphone","Grundig","HCL","HD","HDC","HEDY","HKC","HP","HTC","HTC Corporation","HTIL","HW","Haier","Haipad","Haipai","Hamlet","Hannspree","Hei","Helio","Hero","HiPhone","Hisense","Hitachi","Huawei","Hummer","Hunan Dacheng","Hyundai","IAC OKWAP","IM","INQ Mobile","IXI","Idea","Infineon","InfoSonics","Innostream","Intel","Intex","Itelco","Iview","Japan Radio Company","Java","Jexaa","K-Touch","KCM","KDDI","KGT","KN Mobile","KPT","KT Tech","Kalley","Karbon","Karbonn","Keen High","Kejian","Kendo","Kisen","Klondike","Kobo","Kogan","Konka","Kozi","Kuno","Kurio World","Kyocera","LCT","LG","LGUPlus","LT","LXE","Lanix","Lava","Le Pan","Leader","Lemon","Lenco","Lenovo","Lexibook","Lifetab","Limited Label","Lobster","LogicPD","Logitech","Longcos","Lynx","M3 Gate","M4TEL","MAUI-based Generic","MOBISTEL","MOMO Design","MTS","Maipad","Malata","Maxon","Maxx","Maylong Mobility","McAfee","McPad","Mediacom","Medion","Meizu","Micromax","Microsoft","Mio","Miracle","Mitac","Mitsubishi","Mobell","Mobile Wireless Group","Mobiltab","ModeLabs","Modottel","Modu","Morange","Motorola","Mozilla","Mpman","Muchtel","Multipad","MyPhone","NEC","NGM","NTT","NVSBL","Nem","Neonode","Netfront","Newgen","Newman","Nexian","Nextbook","Ninetology","Nintendo","Nokia","Norton","Novarra","O2","OPPO","Odys","Olive","Olivetti","Onda","Openwave","Opera","Opera Software","Optimay","Orange","Origin","Ouku","PCD","Palm","Panasonic","Panda","Pandigital","Pantech","Pearl","Pegatron","PendoPad","Phicomm","Philips","PhoneOne","PiPO","Pioneer","Pirelli-Arcor","PocketBook","Point of View","Polaris","Polaroid","Polytron","Poseidon","Postcom","Prestigio","Prixton","Proscan","Psion","QCI","QMobile","Qtek","Qualcomm","QuanZhi","RIM","RT","Raks","Ramos","Red Bull Mobile","Reporo","Rich &amp; HL","Ritmix","Robot","Rockchip","Roku","Ron","Rover","RoverPC","SAMART","SEOmoz","SFR","SK Telesys","SPC","SPRD","Sagem","Samsung","Sanyo","Sapo","Sendo","Sharp","Shenchuang","Siemens","SimplePie","Simvalley","Sky","Skybee","Skyfire","Skypad","Skyspring","Skytex","Skyworth","SlobTrot Software","Smart PAD","SmartQ","SmartTrust","Smartfren","Smile","Softbank","SoftwinerEvb","Sonim","Sony","Sony Ericsson","SonyEricsson","Spice","Sprint","Star","Storage Options","Sunon","Sunrise","Superpad","Supersonic","Sylvania","T-Mobile","TCL","THL","TIM","TMN","TTPCom","TWM","TX","Taipower","Taiwan Mobile","Techfaith","Teclast","Tecmobile","Tecno","Tel.Me.","Telecom Italia","Telenor","Telit","Telstra","TiPhone","Tianyu","Tinno","Titan","Tomtec","Toplux","Toshiba","Tranxcode","TrekStor","Trevi","Tsinghua Tongfang","Turkcell","UCWEB","UKING","UTStarcom","Ubiquam","Ultratab","Umeox","Unimax","Uniscope","Unistar","Urbetter","Uriver","Usha Lexus","Utec","VERZIOWORLD","VK Mobile","Vacom","Velocity Micro","Velocity Mobile","Venera","Verizon","Versus","Vertu","VeryKool","Vibo","Videocon","Viettel","ViewSonic","Virgin Mobile","Vitelcom","Vizio","Vodafone","Voxtel","W3C","WAPUniverse","WOM","Wapamp","Wapsilon","WayteQ","WellcoM","Weltbild","Willcom","WinWAP Technologies","Window","Windows Mobile","Wolfgang","Wonu","Woo Comet","Wynncom","X10","XDeviceEmulator","XJXN","XML-Sitemaps","Xiaomi","Xolo","Yarvik","Yas","Yitong","YooTab","Young Way","Youwave","Yushen","ZOPO","ZT","ZTE","ZXD","Zen","Zen Mobile","Zenithink","Zonda","eTouch","generic content fetcher","generic web browser","i-mate","i-mobile","iBall","iDeal","iKoMo","iMiTO","konqueror","myPad","tvCompass"]';
			$resultStr = DictionaryCurl::get(API_BRAND_URL);
		
			if (empty($resultStr)) {
				self::$jsonStr = $defaultStr;
			} else {
				try {
					$obj = json_decode($resultStr);
					
					if ($obj->code == 0) {
						$arr = array();
						
						foreach ($obj->data as $k => $item) {
							$arr[] = $item->name;
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
		
			self::$brands = json_decode(self::$jsonStr, TRUE);
		
			self::$isInit = TRUE;
		}		
	}
?>