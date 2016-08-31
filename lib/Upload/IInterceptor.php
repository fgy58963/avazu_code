<?php
/**
 * @Author: liuzhen02@snda.com
 * @Date: 12-12-21
 * @Version: 1.0
 *
 * 文件上传拦截器接口
 */
interface Upload_IInterceptor
{
    /**
     * @param Upload_Uploader $uploader
     * @param mixed $args
     * @param string $error
     * @return bool 返回false将不再执行上传操作, 返回true将继续上传
     * @throws InvalidArgumentException
     */
    public function run(Upload_Uploader $uploader, $args, &$error = '');
}