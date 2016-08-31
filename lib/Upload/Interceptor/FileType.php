<?php
/**
 * @Author: liuzhen02@snda.com
 * @Date: 12-12-21
 * @Version: 1.0
 *
 * 自定义通用拦截器: 文件类型检测
 */
class Upload_Interceptor_FileType implements Upload_IInterceptor
{
    public static $SWFDUMP_CMD = '/usr/local/bin/swfdump';

    //private static $png_mimes  = array('image/x-png' => 1);
    //private static $jpeg_mimes = array('image/jpg' => 1, 'image/jpe' => 1, 'image/jpeg' => 1, 'image/pjpeg' => 1);
    private static $types = array('gif' => 1, 'jpeg' => 2, 'png' => 3);

    /**
     * @param Upload_Uploader $uploader
     * @param array $args [allowed_type:string, get_width_height:bool]
     * @param string $error
     * @return bool
     * @throws InvalidArgumentException
     */
    public function run(Upload_Uploader $uploader, $args, &$error = '')
    {
        $uploadFileBean = $uploader->getUploadFileBean();

        # 参数
        if (!isset($args['allowed_type'])) {
            throw new InvalidArgumentException;
        }

        # 检查扩展名
        if (isset($args['allowed_exts'])) {
            $mapAllowedExtToIndex = array_flip($args['allowed_exts']);
            $ext = $uploadFileBean->getExt();
            if (!isset($mapAllowedExtToIndex[$ext])) {
                $error = 'upload_invalid_filetype';
                return false;
            }
        }

        # 深入检查文件类型
        $tmpFile = $uploadFileBean->getTmpName();
        $result = false;
        switch ($args['allowed_type']) {
            case 'image':
                if (($size = self::getImageSize($tmpFile)) !== false) {
                    $result = true;
                    # 附加信息
                    if (!empty($args['get_width_height'])) {
                        $uploadFileBean
                            ->addResultData(array('width', 'image_width'), $size[0])
                            ->addResultData(array('height', 'image_height'), $size[1])
                            ->addResultData('image_type', isset(self::$types[$size[2]]) ? self::$types[$size[2]] : 'unknown')
                            ->addResultData('image_size_str', $size[3])
                            ->addResultData('is_image', true);
                    }
                }
                break;
            case 'flash';
                if (($size = self::getFlashSize($tmpFile)) !== false) {
                    $result = true;
                    # 附加信息
                    if (!empty($args['get_width_height'])) {
                        $uploadFileBean
                            ->addResultData('width', $size[0])
                            ->addResultData('height', $size[1]);
                    }
                }
                break;
            default:
                $result = true;
                break;
        }

        return $result;
    }

    /**
     * @param $file
     * @return array|bool
     */
    public static function getImageSize($file)
    {
        return getimagesize($file);
    }

    /**
     * @param $file
     * @return array|bool
     */
    public static function getFlashSize($file)
    {
        $cmd = self::$SWFDUMP_CMD . ' -XY' . " {$file}";
        $cmdResult = @exec($cmd);
        if (empty($cmdResult) || !preg_match('/^-X (\d+) -Y (\d+)$/' , trim($cmdResult), $match)) {
            return false;
        }
        return array($match[1], $match[2]);
    }
}