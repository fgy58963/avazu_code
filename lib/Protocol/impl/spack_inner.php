<?php

namespace talus\internal;
// protocol info
const MAGIC_0 = 0xD2;
const MAGIC_1 = 0x8D;
const VERSION_MAJOR = 0x01;
const VERSION_MINOR = 0x01;

// types
const TYPE_ID_UNDEFINED = 0xFF;
// int types
// const TYPE_ID_NULL = 0x00;
const TYPE_ID_BOOL = 0x01;
const TYPE_ID_INT8 = 0x10;
const TYPE_ID_UINT8 = 0x11;
const TYPE_ID_INT16 = 0x12;
const TYPE_ID_UINT16 = 0x13;
const TYPE_ID_INT32 = 0x14;
const TYPE_ID_UINT32 = 0x15;
const TYPE_ID_INT64 = 0x16;
const TYPE_ID_UINT64 = 0x17;
// float types
const TYPE_ID_FLOAT = 0x20;
const TYPE_ID_DOUBLE = 0x21;
// string types
const TYPE_ID_STR_8 = 0x30; // ONLY used by framework in package
const TYPE_ID_STR_16 = 0x31; // ONLY used by framework in package
const TYPE_ID_STR = 0x32; // TYPE_ID_STR_32
                          // array types
const TYPE_ID_ARRAY_8 = 0x40; // ONLY used by framework in package
const TYPE_ID_ARRAY_16 = 0x41; // ONLY used by framework in package
const TYPE_ID_ARRAY = 0x42; // TYPE_ID_ARR_32
                            // map types
const TYPE_ID_MAP_8 = 0x50; // ONLY used by framework in package
const TYPE_ID_MAP_16 = 0x51; // ONLY used by framework in package
const TYPE_ID_MAP = 0x52; // TYPE_ID_MAP_32

define ( 'BIG_ENDIAN', pack ( 'L', 0xfffe ) === pack ( 'N', 0xfffe ) );

/**
 * host to net (BIG_ENDIAN) conversion
 *
 * @param
 *        	bin_str binary string <p>
 *        	must not be normal string
 *        	</p>
 * @return BIG_ENDIAN string
 */
function hton_str($bin_str) {
	if (BIG_ENDIAN) {
		return $bin_str;
	} else {
		return strrev ( $bin_str );
	}
}
// pack/unpack functions
function pack_uint8($s) {
	return pack ( 'C', $s );
}

function unpack_uint8($s) {
	return unpack ( 'Ci', $s )['i'];
}

function pack_int8($i8) {
	return pack ( 'c', $i8 );
}

function unpack_int8($s) {
	return unpack ( 'ci', $s )['i'];
}

function pack_uint16($i16) {
	return pack ( 'n', $i16 );
}

function unpack_uint16($s) {
	return unpack ( 'ni', $s )['i'];
}

function pack_int16($i16) {
	return hton_str ( pack ( 's', $i16 ) );
}

function unpack_int16($s) {
	return unpack ( 'si', hton_str ( $s ) )['i'];
}

function pack_uint32($i32) {
	return pack ( 'N', $i32 );
}

function unpack_uint32($s) {
	return unpack ( 'Ni', $s )['i'];
}

function pack_int32($i32) {
	return hton_str ( pack ( 'l', $i32 ) );
}

function unpack_int32($s) {
	return unpack ( 'li', hton_str ( $s ) )['i'];
}

function pack_uint64($i64) {
	$low = $i64 & 0xffffffff;
	$hi = $i64 >> 32;
	return pack ( 'NN', $hi, $low );
}

function unpack_uint64($s) {
	$arr = unpack ( 'Nh/Nl', $s );
	return ($arr ['h'] << 32) + $arr ['l'];
}

function pack_int64($i64) {
	// treat as uint64; correct when PHP_INT_MAX == 0x7FFFFFFFFFFFFFFF
	return pack_uint64 ( $i64 );
}

function unpack_int64($s) {
	return unpack_uint64 ( $s );
	// return pack('q', hton_str($s));
}

function pack_float($f) {
	return hton_str ( pack ( 'f', $f ) );
}

function unpack_float($s) {
	return unpack ( 'ff', hton_str ( $s ) )['f'];
}

function pack_double($d) {
	return hton_str ( pack ( 'd', $d ) );
}

function unpack_double($s) {
	return unpack ( 'df', hton_str ( $s ) )['f'];
}

/**
 * put bool value into buffer
 *
 * @param
 *        	buf [in/out] buffer <p>
 *        	buffer should be append content
 *        	</p>
 * @param
 *        	b bool <p>
 *        	bool value will be stored
 *        	</p>
 * @return none
 */
function put_bool(&$buf, $b) {
	$buf [] = pack_uint8 ( TYPE_ID_BOOL );
	$buf [] = pack_uint8 ( $b );
}

function put_int(&$buf, $i) {
	if ($i >= 0) {
		if ($i <= 0xff) {
			$buf [] = pack_uint8 ( TYPE_ID_UINT8 );
			$buf [] = pack_uint8 ( $i );
		} elseif ($i <= 0xffff) {
			$buf [] = pack_uint8 ( TYPE_ID_UINT16 );
			$buf [] = pack_uint16 ( $i );
		} elseif ($i <= 0xffffffff) {
			$buf [] = pack_uint8 ( TYPE_ID_UINT32 );
			$buf [] = pack_uint32 ( $i );
		} else {
			$buf [] = pack_uint8 ( TYPE_ID_UINT64 );
			$buf [] = pack_uint64 ( $i );
		}
	} else {
		if ($i >= - 0x80) {
			$buf [] = pack_uint8 ( TYPE_ID_INT8 );
			$buf [] = pack_int8 ( $i );
		} elseif ($i >= - 0x8000) {
			$buf [] = pack_uint8 ( TYPE_ID_INT16 );
			$buf [] = pack_int16 ( $i );
		} elseif ($i >= - 0x80000000) {
			$buf [] = pack_uint8 ( TYPE_ID_INT32 );
			$buf [] = pack_int32 ( $i );
		} else {
			$buf [] = pack_uint8 ( TYPE_ID_INT64 );
			$buf [] = pack_int64 ( $i );
		}
	}
}

function put_float(&$buf, $f) {
	$buf [] = pack_uint8 ( TYPE_ID_DOUBLE );
	$buf [] = pack_double ( $f );
}

function put_string(&$buf, $s) {
	$len = strlen ( $s );
	if ($len <= 0xff) {
		$buf [] = pack_uint8 ( TYPE_ID_STR_8 );
		$buf [] = pack_uint8 ( $len );
	} elseif ($len <= 0xffff) {
		$buf [] = pack_uint8 ( TYPE_ID_STR_16 );
		$buf [] = pack_uint16 ( $len );
	} else {
		$buf [] = pack_uint8 ( TYPE_ID_STR );
		$buf [] = pack_uint32 ( $len );
	}
	$buf [] = $s;
}

/**
 * put key into buffer
 *
 * @param
 *        	buf [in/out] buffer <p>
 *        	buffer should be append content
 *        	</p>
 * @param
 *        	b bool <p>
 *        	bool value will be stored
 *        	</p>
 * @return none
 */
function put_key(&$buf, $key) {
	if (! is_string ( $key )) { // key is always string
		$key = ( string ) $key;
	}
	$buf [] = pack_uint16 ( strlen ( $key ) );
	$buf [] = $key;
}

/**
 * put array into buffer
 *
 * @param
 *        	buf [in/out] buffer <p>
 *        	buffer should be append content
 *        	</p>
 * @param
 *        	arr array <p>
 *        	will be stored into buf
 *        	</p>
 * @return none
 */
function put_array(&$buf, $arr) {
	if (array_keys ( $arr ) === range ( 0, count ( $arr ) - 1 )) { // sequence array
		$arrbuf = [ ];
		foreach ( $arr as $v ) {
			put_any ( $arrbuf, $v );
		}
		$arrbin = implode ( $arrbuf );
		$cnt = count ( $arr );
		if ($cnt <= 0xff) {
			$arrtype = TYPE_ID_ARRAY_8;
			$arrsize = pack_uint8 ( $cnt );
		} else if ($cnt <= 0xffff) {
			$arrtype = TYPE_ID_ARRAY_16;
			$arrsize = pack_uint16 ( $cnt );
		} else {
			$arrtype = TYPE_ID_ARRAY;
			$arrsize = pack_uint32 ( $cnt );
		}
	} else { // associative array
		$arrbuf = [ ];
		foreach ( $arr as $k => $v ) {
			put_key ( $arrbuf, $k );
			put_any ( $arrbuf, $v );
		}
		$arrbin = implode ( $arrbuf );
		$cnt = count ( $arr );
		if ($cnt <= 0xff) {
			$arrtype = TYPE_ID_MAP_8;
			$arrsize = pack_uint8 ( $cnt );
		} else if ($cnt <= 0xffff) {
			$arrtype = TYPE_ID_MAP_16;
			$arrsize = pack_uint16 ( $cnt );
		} else {
			$arrtype = TYPE_ID_MAP;
			$arrsize = pack_uint32 ( $cnt );
		}
	}
	$buf [] = pack_uint8 ( $arrtype ); // type id
	$buf [] = pack_uint32 ( strlen ( $arrsize ) + strlen ( $arrbin ) ); // bytes
	$buf [] = $arrsize; // item count
	$buf [] = $arrbin; // binary
}

/**
 * put any object into buffer
 *
 * @param
 *        	buf [in/out] buffer <p>
 *        	buffer should be append content
 *        	</p>
 * @param
 *        	v any type element <p>
 *        	will be stored into buf
 *        	</p>
 * @return none
 */
function put_any(&$buf, $v) {
	if (is_array ( $v )) {
		put_array ( $buf, $v );
	} elseif (is_string ( $v )) {
		put_string ( $buf, $v );
	} elseif (is_int ( $v )) {
		put_int ( $buf, $v );
	} elseif (is_float ( $v )) {
		put_float ( $buf, $v );
	} elseif (is_bool ( $v )) {
		put_bool ( $buf, $v );
	} else { // object
		put_array ( $buf, ( array ) $v );
	}
}

/**
 * pop the head part of binary string
 *
 * @param
 *        	bin binary string <p>
 *        	The whole binary string object
 *        	</p>
 * @param
 *        	offset [in/out] int <p>
 *        	It is bin's start position
 *        	</p>
 * @param
 *        	bytes int <p>
 *        	It is bin's substr len
 * @param
 *        	unpack function pointer <p>
 *        	Specific value type has specific unpack function
 *        	</p>
 * @return bin[offset..(bytes)] binary => string
 */
function get_next($bin, &$offset, $bytes, $unpack = NULL) {
	if ($offset + $bytes <= strlen ( $bin )) {
		if ($bytes > 0) {
			$v = substr ( $bin, $offset, $bytes );
			$offset += $bytes;
		} else {
			$v = '';
		}
		
		if (is_string ( $unpack )) {
			return call_user_func ( '\\talus\\internal\\' . $unpack, $v );
		} else {
			return $v;
		}
	} else {
		return NULL;
	}
}

/**
 * get next object's size
 *
 * @param
 *        	bin binary string <p>
 *        	Buffer is binary string
 *        	</p>
 * @param
 *        	offset int <p>
 *        	Offset in binary string
 *        	</p>
 * @param
 *        	bytes int <p>
 *        	It is bin's substr len
 *        	</p>
 * @return [int] next element's size
 */
function get_next_size($bin, &$offset, $bytes) {
	if ($bytes == 1) {
		$unpack = 'unpack_uint8';
	} elseif ($bytes == 2) {
		$unpack = 'unpack_uint16';
	} elseif ($bytes == 4) {
		$unpack = 'unpack_uint32';
	} elseif ($bytes == 8) {
		$unpack = 'unpack_uint64';
	} else {
		return NULL;
	}
	return get_next ( $bin, $offset, $bytes, $unpack );
}

/**
 * get object from binary string
 *
 * @param
 *        	bin binary string <p>
 *        	Buffer is binary string
 *        	</p>
 * @param
 *        	offset int <p>
 *        	Offset in binary string
 * @return [int] wrapper of get function
 */
function get_any($bin, &$offset) {
	$type = get_next ( $bin, $offset, 1, 'unpack_uint8' );
	if (is_null ( $type ))
		return NULL;
	switch ($type) {
		case TYPE_ID_BOOL :
			return get_next ( $bin, $offset, 1, 'unpack_uint8' ) != 0;
		case TYPE_ID_INT8 :
			return get_next ( $bin, $offset, 1, 'unpack_int8' );
		case TYPE_ID_UINT8 :
			return get_next ( $bin, $offset, 1, 'unpack_uint8' );
		case TYPE_ID_INT16 :
			return get_next ( $bin, $offset, 2, 'unpack_int16' );
		case TYPE_ID_UINT16 :
			return get_next ( $bin, $offset, 2, 'unpack_uint16' );
		case TYPE_ID_INT32 :
			return get_next ( $bin, $offset, 4, 'unpack_int32' );
		case TYPE_ID_UINT32 :
			return get_next ( $bin, $offset, 4, 'unpack_uint32' );
		case TYPE_ID_INT64 :
			return get_next ( $bin, $offset, 8, 'unpack_int64' );
		case TYPE_ID_UINT64 :
			return get_next ( $bin, $offset, 8, 'unpack_uint64' );
		// float types
		case TYPE_ID_FLOAT :
			return get_next ( $bin, $offset, 4, 'unpack_float' );
		case TYPE_ID_DOUBLE :
			return get_next ( $bin, $offset, 8, 'unpack_double' );
		case TYPE_ID_STR_8 :
		case TYPE_ID_STR_16 :
		case TYPE_ID_STR :
			if ($type == TYPE_ID_STR_8) {
				$len = get_next_size ( $bin, $offset, 1 );
			} else if ($type == TYPE_ID_STR_16) {
				$len = get_next_size ( $bin, $offset, 2 );
			} else {
				$len = get_next_size ( $bin, $offset, 4 );
			}
			if (is_null ( $len ))
				return NULL;
			return get_next ( $bin, $offset, $len );
		case TYPE_ID_ARRAY_8 :
		case TYPE_ID_ARRAY_16 :
		case TYPE_ID_ARRAY :
			$len = get_next_size ( $bin, $offset, 4 );
			if (is_null ( $len ))
				return NULL;
			if ($type == TYPE_ID_ARRAY_8) {
				$cnt = get_next_size ( $bin, $offset, 1 );
			} else if ($type == TYPE_ID_ARRAY_16) {
				$cnt = get_next_size ( $bin, $offset, 2 );
			} else {
				$cnt = get_next_size ( $bin, $offset, 4 );
			}
			if (is_null ( $cnt ))
				return NULL;
			$arr = array ();
			for($i = 0; $i < $cnt; $i ++) {
				$o = get_any ( $bin, $offset );
				if (is_null ( $o ))
					return NULL;
				$arr [] = $o;
			}
			return $arr;
		case TYPE_ID_MAP_8 :
		case TYPE_ID_MAP_16 :
		case TYPE_ID_MAP :
			$len = get_next_size ( $bin, $offset, 4 );
			if (is_null ( $len ))
				return NULL;
			if ($type == TYPE_ID_MAP_8) {
				$cnt = get_next_size ( $bin, $offset, 1 );
			} else if ($type == TYPE_ID_MAP_16) {
				$cnt = get_next_size ( $bin, $offset, 2 );
			} else {
				$cnt = get_next_size ( $bin, $offset, 4 );
			}
			if (is_null ( $cnt ))
				return NULL;
			$map = array ();
			for($i = 0; $i < $cnt; $i ++) {
				$key_len = get_next_size ( $bin, $offset, 2 );
				if (is_null ( $key_len )) {
					return NULL;
				}
				$k = get_next ( $bin, $offset, $key_len );
				if (is_null ( $k ))
					return NULL;
				$o = get_any ( $bin, $offset );
				if (is_null ( $o ))
					return NULL;
				$map [$k] = $o;
			}
			return $map;
	}
	return NULL;
}

/**
 * serialize object to buffer
 *
 * @param
 *        	obj object <p>
 *        	Object to be serialized
 *        	</p>
 * @return [binary string] the spack object in our format
 */
function build($obj) {
	$buf = [ ];
	put_any ( $buf, $obj );
	return implode ( $buf );
}

/**
 * extract object from buffer
 *
 * @param
 *        	buf binary string <p>
 *        	Buffer is binary string
 *        	</p>
 * @return <p> success: mixed
 *         </p>
 */
function parse($buf) {
	$offset = 0;
	$obj = get_any ( $buf, $offset );
	if (! is_null ( $obj ) && strlen ( $buf ) == $offset) {
		return $obj;
	} else {
		return NULL;
	}
}

function print_packet_organization() {
	echo '
 0                   1                   2                   3
  0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 |          Magic Number         | Major Version | Minor Version |
 +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 |                                                               |
 +                      64-bits Sequence Id                      +
 |                                                               |
 +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 |                                                               |
 +                      64-bits Reservation                      +
 |                                                               |
 +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 |                      32-bits Content Length                   |
 +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 |                  content-length bytes of data                 |
 +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
';
}
const HEAD_BYTES = 24;
const E_OK = 0;
const E_MORE = 1;
const E_BAD_MAGIC = - 1;
const E_BAD_VERSION = - 2;
const E_BAD_BODY = - 3;

/**
 * errcode for parse packet
 *
 * @param
 *        	code int <p>
 *        	The error code int
 *        	</p>
 * @return the description of error code
 */
function error_message($code) {
	switch ($code) {
		case E_OK :
			return 'OK';
		case E_MORE :
			return 'Need more data';
		case E_BAD_MAGIC :
			return 'Bad magic number';
		case E_BAD_VERSION :
			return 'Bad version number';
		case E_BAD_BODY :
			return 'Bad body data';
		default :
			return 'Unexpected code(' . ( string ) ($code) . ')';
	}
}

/**
 * build packet from php object
 * return the moved bytes
 *
 * @param
 *        	obj object <p>
 *        	Object to be serialized
 *        	</p>
 * @param
 *        	seqid int <p>
 *        	The sequence id
 *        	</p>
 * @return mixed
 */
function build_packet($obj, $seqid) {
	$bin = build ( $obj );
	$buf = [ ];
	$buf [] = pack_uint8 ( MAGIC_0 );
	$buf [] = pack_uint8 ( MAGIC_1 );
	$buf [] = pack_uint8 ( VERSION_MAJOR );
	$buf [] = pack_uint8 ( VERSION_MINOR );
	$buf [] = pack_uint64 ( $seqid ); // sequence id
	$buf [] = pack_uint64 ( 0x00 ); // reservation
	$buf [] = pack_uint32 ( strlen ( $bin ) );
	$buf [] = $bin;
	return implode ( $buf );
}

/**
 * like the strncat in C language, but the source string will truncated
 *
 * @param
 *        	dst string [in/out] <p>
 *        	Destination of string, will append moved string
 *        	</p>
 * @param
 *        	src string [in/out] <p>
 *        	Source of string, will truncate from head, truncate length determine by <b>len</b> 
 *        	</p>
 * @param
 *        	len int <p>
 *        	Move len characters from src to dst, if src has no enough characters, 
 *          move entire src.
 *        	</p>
 * @return the length of moved
 */
function str_move(&$dst, &$src, $len) {
	$srclen = strlen ( $src );
	if ($srclen <= $len) {
		$dst .= $src;
		$src = '';
		return $srclen;
	} else {
		$dst .= substr ( $src, 0, $len );
		$src = substr ( $src, $len );
		return $len;
	}
}

/**
 * extract packet from binary stream
 */
class PacketParser {

	private $head = '';

	private $seqid = NULL;

	private $bodylen = NULL;

	private $bodybuf = '';

	private $bodyobj = NULL;

	/**
	 * reset the parser state
	 */
	function clear() {
		$this->head = '';
		$this->seqid = NULL;
		$this->bodylen = NULL;
		$this->bodybuf = '';
		$this->bodyobj = NULL;
	}

	/**
	 * sequence id of the packet
	 *
	 * @return sequence id
	 */
	function sequence_id() {
		return $this->seqid;
	}

	/**
	 * body array object
	 *
	 * @return body array object
	 */
	function bodyobj() {
		return $this->bodyobj;
	}

	/**
	 * head buffer
	 *
	 * @return body array object
	 */
	function headbuf() {
		return $this->head;
	}
	
	/**
	 * body buffer
	 *
	 * @return body array object
	 */
	function bodybuf() {
		return $this->bodybuf;
	}
	
	/**
	 * feed some data to the parser, put the extra data in $extra
	 *
	 * @param
	 *        	data [in] <p>
	 *        	The content that will be append to this object
	 *        	</p>
	 * @param
	 *        	extra [out] <p>
	 *        	Extra data
	 *        	</p>
	 * @return <p> on parse finished : E_OK </p>
	 *         <p> parse incomplete : E_MORE </p>
	 *         <p> parse error : other error code </p>
	 */
	function feed($data, &$extra) {
		if (is_null ( $this->seqid )) {
			// receive head
			$head_expected = HEAD_BYTES - strlen ( $this->head );
			if (str_move ( $this->head, $data, $head_expected ) == $head_expected) {
				// parse head
				$offset = 0;
				$magic0 = get_next_size ( $this->head, $offset, 1 );
				$magic1 = get_next_size ( $this->head, $offset, 1 );
				if ($magic0 != MAGIC_0 || $magic1 != MAGIC_1) {
					return E_BAD_MAGIC;
				}
				
				$version_major = get_next_size ( $this->head, $offset, 1 );
				$version_minor = get_next_size ( $this->head, $offset, 1 );
				if ($version_major != VERSION_MAJOR || $version_minor != VERSION_MINOR) {
					return E_BAD_VERSION;
				}
				
				$this->seqid = get_next_size ( $this->head, $offset, 8 );
				$this->reserved = get_next_size ( $this->head, $offset, 8 );
				$this->bodylen = get_next_size ( $this->head, $offset, 4 );
			} else {
				$extra = '';
				return E_MORE;
			}
		}
		$body_expected = $this->bodylen - strlen ( $this->bodybuf ); // $this->bodylen never changed
		if (str_move ( $this->bodybuf, $data, $body_expected ) == $body_expected) {
			if ($this->bodylen > 0) {
				$this->bodyobj = parse ( $this->bodybuf );
				if (is_null ( $this->bodyobj ))
					return E_BAD_BODY;
			} // else: null body
			$extra = $data;
			return E_OK;
		} else {
			$extra = '';
			return E_MORE;
		}
	}
}
