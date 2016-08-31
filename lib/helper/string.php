<?php
/**
 * Escapes text to make it safe to use with Javascript
 *
 * It is usable as, e.g.:
 *  echo '<script>aiert(\'begin'.escape_js_quotes($mid_part).'end\');</script>';
 * OR
 *  echo '<tag onclick="aiert(\'begin'.escape_js_quotes($mid_part).'end\');">';
 * Notice that this function happily works in both cases; i.e. you don't need:
 *  echo '<tag onclick="aiert(\'begin'.txt2html_old(escape_js_quotes($mid_part)).'end\');">';
 * That would also work but is not necessary.
 *
 * @param  string $str    The data to escape
 * @param  bool   $quotes should wrap in quotes (isn't this kind of silly?)
 * @return string         Escaped data
 */
function escape_js_quotes($str, $quotes = FALSE) {
    if ($str === null) {
        return;
    }
    $str = strtr($str, array('\\'=>'\\\\', "\n"=>'\\n', "\r"=>'\\r', '"'=>'\\x22', '\''=>'\\\'', '<'=>'\\x3c', '>'=>'\\x3e', '&'=>'\\x26'));
    return $quotes ? '"'. $str . '"' : $str;
}

if (! function_exists('str_startwith')) {
    function str_startwith($haystack, $needle)
    {
        return strpos($haystack, $needle) === 0;       
    }
}

function is_empty_string($str)
{
	return $str === NULL || $str === '';
}

#按字符宽度截取字符串,一个半角字符为一个宽度,全角字符为两个宽度
function str_truncate($str, $len)
{
     if (empty($str)) return $str;

    $gbkStr = @iconv('UTF-8', 'gbk', $str);

    if ($gbkStr == '') {
        //Convert encoding to gbk failed
        $i = 0;
        $wi = 0;
        $n = strlen($str);
        $newStr = '';
        while ($i < $n) {
            $ord = ord($str{$i});
            if ($ord > 224) {
                $newStr .= substr($str, $i, 3);
                $i += 3;
                $wi += 2;
            } else if ($ord > 192) {
                $newStr .= substr($str, $i, 2);
                $i += 3;
                $wi += 2;
            } else {
                $newStr .= substr($str, $i, 1);
                $i += 1;
                $wi += 1;
            }
            if ($wi >= $len) {
                break;
            }
        }
        if ($wi < $len || ($wi == $len && $i == $n)) {
            return $str;
        }
        return preg_replace('@([\x{00}-\x{ff}]{3}|.{2})$@u', '...', $newStr);
    }

    if ($len < 3 || strlen($gbkStr) <= $len) {
        return $str;
    }

    $cutStr = mb_strcut($gbkStr, 0, $len - 3, 'gbk');
    $cutStr = iconv('gbk', 'UTF-8', $cutStr);
    return $cutStr . '...';
}

/**
 * Format string camelize
 */
function camelize($str, $upperFirstChar = TRUE)
{
	$segments = explode('_', $str);
	$ret = '';
	for($i = 0, $n = count($segments); $i < $n; $i++) {
		$segment = $segments[$i];
		if (strlen($segment) == 0) {
			continue;
		}
		if ($i == 0 && !$upperFirstChar) {
			$ret .= $segment;
		} else {
			$ret .= strtoupper($segment{0});
			if (strlen($segment) > 1) {
				$ret .= substr($segment, 1);
			}
		}
	}
	return $ret;
}

// funnyThing => funny_thing
function underscore($str)
{
	return trim(preg_replace_callback(
		'@[A-Z]@',
		create_function('$m', 'return "_".strtolower($m[0]);'),
		$str
	), '_');
}

// Generate a random character string
function rand_str($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
{
    // Length of character list
    $chars_length = (strlen($chars) - 1);

    // Start our string
    $string = $chars{rand(0, $chars_length)};
    
    // Generate random string
    for ($i = 1; $i < $length; $i = strlen($string))
    {
        // Grab a random character from our list
        $r = $chars{rand(0, $chars_length)};
        
        // Make sure the same two characters don't appear next to each other
        if ($r != $string{$i - 1}) $string .=  $r;
    }
    
    // Return the string
    return $string;
}

//数值缩短
function dec2s4($dec)
{
    $base = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $result = '';
    do {
        $result = $base[$dec % 62] . $result;
        $dec = intval($dec / 62);
    } while ( $dec != 0 );
    return $result;
}

  
function s42dec($sixty_four)
{
    $base_map = array(
        '0' => 0,
        '1' => 1,
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5,
        '6' => 6,
        '7' => 7,
        '8' => 8,
        '9' => 9,
        'a' => 10,
        'b' => 11,
        'c' => 12,
        'd' => 13,
        'e' => 14,
        'f' => 15,
        'g' => 16,
        'h' => 17,
        'i' => 18,
        'j' => 19,
        'k' => 20,
        'l' => 21,
        'm' => 22,
        'n' => 23,
        'o' => 24,
        'p' => 25,
        'q' => 26,
        'r' => 27,
        's' => 28,
        't' => 29,
        'u' => 30,
        'v' => 31,
        'w' => 32,
        'x' => 33,
        'y' => 34,
        'z' => 35,
        'A' => 36,
        'B' => 37,
        'C' => 38,
        'D' => 39,
        'E' => 40,
        'F' => 41,
        'G' => 42,
        'H' => 43,
        'I' => 44,
        'J' => 45,
        'K' => 46,
        'L' => 47,
        'M' => 48,
        'N' => 49,
        'O' => 50,
        'P' => 51,
        'Q' => 52,
        'R' => 53,
        'S' => 54,
        'T' => 55,
        'U' => 56,
        'V' => 57,
        'W' => 58,
        'X' => 59,
        'Y' => 60,
        'Z' => 61 
    );
    $result = 0;
    $len = strlen($sixty_four);
    for($n = 0; $n < $len; $n ++) {
        $result *= 62;
        $result += $base_map[$sixty_four{$n}];
    }
    return $result;
}  
      
function is_utf8($word)
{
    if (preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$word) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$word) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$word) == true)
    {
        return true;
    }
    else
    {
        return false;
    }
}
function str_replace_char($string, $replaceChar, $start, $length = 0)
{
    $strLen = strlen($string);
    $startIndex = $start - 1;
    $endIndex = $strLen - 1;
    if ($strLen <= 0 || $startIndex > $endIndex) {
        return $string;
    }
    if ($length > 0) {
        $endIndex = $startIndex + $length;
        if ($endIndex > ($strLen - 1)) {
            return $string;
        }
    }
    if ($length < 0) {
        $endIndex = $strLen - 1 + $length;
        if ($endIndex < $startIndex) {
            return $string;
        }
    }
    for($i = $startIndex; $i <= $endIndex; $i ++) {
        $string{$i} = $replaceChar;
    }
    return $string;
}