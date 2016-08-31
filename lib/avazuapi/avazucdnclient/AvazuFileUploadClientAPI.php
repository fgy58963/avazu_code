<?php
class AvazuFileUploadClientAPI {
    private static $config;
    public static function setConfig($token, $recvDataPath, $autoFrameHeightPath, $fileTypes, $uploadBtnMsg = '<strong> <-- </strong>click here to proceed.', $frameUrl = 'http://api.cdn.avazu.net/upload/main.php') {
        self::$config = array(
            'AVAZU_CDN_API_TOKEN' => $token,
            'AVAZU_CDN_API_FRAME_URL' => $frameUrl,
            'AVAZU_CDN_API_RECEIVE_DATA_URI' => $recvDataPath,
            'AVAZU_CDN_API_AUTO_FRAME_HEIGHT_URI' => $autoFrameHeightPath,
            'AVAZU_CDN_API_FILE_TYPES' => $fileTypes,
            'AVAZU_CDN_API_UPLOAD_BTN_MSG' => $uploadBtnMsg,
        );
    }

    public static function outputUploadFrame($text, $foreColor, $bgColor,$width='100%') {
        $html = '<iframe id="uploadfile" ';
        $html .= 'width="'.$width.'" ';
        $html .= 'height="50" ';
        $html .= 'frameborder="no" ';
        $html .= 'border="0" ';
        $html .= 'marginwidth="0" ';
        $html .= 'marginheight="0" ';
        $html .= 'scrolling="no" ';
        $html .= 'allowtransparency="yes" ';
        $html .= 'src="' . self::$config['AVAZU_CDN_API_FRAME_URL'];
        $html .= '?';
        $params = array(
            'token' =>self::$config['AVAZU_CDN_API_TOKEN'],
            'rdu' =>self::$config['AVAZU_CDN_API_RECEIVE_DATA_URI'],
            'afhu' =>self::getCurrentDomain() . self::$config['AVAZU_CDN_API_AUTO_FRAME_HEIGHT_URI'],
            'ft' =>self::$config['AVAZU_CDN_API_FILE_TYPES'],
            'uploadbtnmsg' =>self::$config['AVAZU_CDN_API_UPLOAD_BTN_MSG'],
            'text' => $text,
            'forecolor' => $foreColor,
            'bgcolor' => $bgColor);
        $html .= http_build_query($params) ;
        $html .= '">';
        $html .= '</iframe>';
        $html .= '';

        return $html;
    }

    /**
     * 把上传成功的信息写入当前页面的一个hidden input框
     */
    public static function uploadData() {
        $datas = $_POST['data'];

        if (empty($datas)) {
            return FALSE;
        }

        $jsonDatas = array();

        foreach ($datas as $data) {
            $tmp = json_decode(self::phpMagicQuotesGPC($data), TRUE);

            if (!empty($tmp)) {
                $jsonDatas[] = $tmp;
            }
        }

        if (empty($jsonDatas)) {
            return FALSE;
        }
        return $jsonDatas;
    }

    public static function echoAutoFrameHeightHtml() {

        echo <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title></title>
</head>
<body>
<script>
function getValue(name) {
	var url = window.location.href;
	var reg = new RegExp('(\\\\?|&)' + name + '=([^&?]*)', 'i');
	var arr = url.match(reg);
	if (arr) {
		return arr[2];
	}
	return null;
}

(function() {
	var frame = window.top.document.getElementById('uploadfile');

	if (frame) {
		var height = getValue('h') || 0;

		frame.style.height = height + 'px';
	}
})();
</script>
</body>
</html>
EOT;
    }


    private static function phpMagicQuotesGPC($value) {
        if (get_magic_quotes_gpc()) {
            return stripslashes($value);
        } else {
            return $value;
        }
    }

    private static function getCurrentDomain() {
        return 'http://' . $_SERVER['HTTP_HOST'];
    }
}