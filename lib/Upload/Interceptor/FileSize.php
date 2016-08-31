<?php
/**
 * @Author: liuzhen02@snda.com
 * @Date: 12-12-21
 * @Version: 1.0
 *
 * 自定义通用拦截器: 文件大小检测
 */
class Upload_Interceptor_FileSize implements Upload_IInterceptor
{
    /**
     * @param Upload_Uploader $uploader
     * @param int $args max_size
     * @param string $error
     * @return bool
     * @throws InvalidArgumentException
     */
    public function run(Upload_Uploader $uploader, $args, &$error = '')
    {
        $uploadFileBean = $uploader->getUploadFileBean();
        if (($result = round($uploadFileBean->getSize()/1024, 2) <= $args)===false) {
            $error = 'upload_invalid_filesize';
        }
        return $result;
    }
}