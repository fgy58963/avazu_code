<?php
/**
 * @Author: liuzhen02@snda.com
 * @Date: 12-12-21
 * @Version: 1.0
 *
 * 自定义通用拦截器: 真实文件名过滤&&生成
 */
class Upload_Interceptor_FileName implements Upload_IInterceptor
{
    protected static $bad = array(
        "<!--",
        "-->",
        "'",
        "<",
        ">",
        '"',
        '&',
        '$',
        '=',
        ';',
        '?',
        '/',
        "%20",
        "%22",
        "%3c",		// <
        "%253c",	// <
        "%3e",		// >
        "%0e",		// >
        "%28",		// (
        "%29",		// )
        "%2528",	// (
        "%26",		// &
        "%24",		// $
        "%3f",		// ?
        "%3b",		// ;
        "%3d"		// =
    );
    public static $maxTryFileNameNumber = 100;

    /**
     * @param Upload_Uploader $uploader
     * @param array $args [overwrite:bool, max_filename:int, remove_spaces:bool, clean:bool]
     * @param string $error
     * @return bool
     * @throws InvalidArgumentException
     */
    public function run(Upload_Uploader $uploader, $args, &$error = '')
    {
        $uploadFileBean = $uploader->getUploadFileBean();

        $basePath = $uploader->getBasePath();
        $realFileName = $uploader->getRealFilename();

        if (!empty($args['overwrite'])) {
            $realFileName = md5(uniqid(mt_rand())) . '.' . $uploadFileBean->getExt();
        } else {
            if (isset($args['max_filename']) && $args['max_filename'] > 0) {
                $length = (int)$args['max_filename'];
                if (strlen($realFileName) >= $length) {
                    $ext = '';
                    if (strpos($realFileName, '.') !== false) {
                        $parts		= explode('.', $realFileName);
                        $ext		= '.'.array_pop($parts);
                        $filename	= implode('.', $parts);
                    }
                    $realFileName = substr($realFileName, 0, ($length - strlen($ext))) . $ext;
                }
            }

            if (!empty($args['remove_spaces'])) {
                $realFileName = preg_replace('/\s+/', '_', $realFileName);
            }

            if (!empty($args['clean'])) {
                $realFileName = stripslashes(str_replace(self::$bad, '', $realFileName));
            }
        }

        $result = false;
        $fullPath = $basePath . $realFileName;
        for ($i=0; $i<self::$maxTryFileNameNumber; $i++) {
            if (!file_exists($basePath . $realFileName)) {
                $result = true;
                break;
            } else {
                $realFileName = $i==0 ?
                    $realFileName."_$i" :
                    substr($realFileName, 0, strrpos($realFileName, '_'));
            }
        }

        if ($result===false) {
            $error = 'aim file is exist';
        } else {
            $uploader->setRealFilename($realFileName);
        }
        return $result;
    }
}