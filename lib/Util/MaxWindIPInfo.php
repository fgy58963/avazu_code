<?php 
define('MAXMIND_DIR',		dirname(__FILE__) . '/');
define('MAXMIND_DATA_DIR',	MAXMIND_DIR . 'datas/');

class Util_MaxWindIPInfo {
	private static $includeGeoIp = FALSE;
	private static $geoip = NULL;
	private static $geoiporg = NULL;
	private static $geoipisp = NULL;

	public static function emptyString($str) {
		return !self::notEmptyString($str);
	}

	public static function notEmptyString($str) {
		return isset($str) && trim($str) != '';
	}

	public static function getCountryCode($ip) {
		if (!self::$includeGeoIp) {
			include MAXMIND_DIR . 'geoip.inc';
			self::$includeGeoIp = TRUE;
		}

		if (!self::$geoip) {
			self::$geoip = geoip_open(MAXMIND_DATA_DIR . '/GeoIP-106_20150428.dat', GEOIP_STANDARD);
		}

		return geoip_country_code_by_addr(self::$geoip, $ip);
	}

	public static function getOrg($ip) {
		if (!self::$includeGeoIp) {
			include MAXMIND_DIR . 'geoip.inc';
			self::$includeGeoIp = TRUE;
		}

		if (!self::$geoiporg) {
			self::$geoiporg = geoip_open(MAXMIND_DATA_DIR . '/GeoIPOrg.dat', GEOIP_STANDARD);
		}

		return geoip_org_by_addr(self::$geoiporg, $ip);
	}

	public static function getIsp($ip) {
		if (!self::$includeGeoIp) {
			include MAXMIND_DIR . 'geoip.inc';
			self::$includeGeoIp = TRUE;
		}

		if (!self::$geoipisp) {
			self::$geoipisp = geoip_open(MAXMIND_DATA_DIR . '/GeoIPISP.dat', GEOIP_STANDARD);
		}

		return geoip_org_by_addr(self::$geoipisp, $ip);
	}
}

?>