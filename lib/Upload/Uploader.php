<?php
/**
 * @Author: liuzhen02@snda.com
 * @Date: 12-12-21
 * @Version: 1.0
 *
 * 文件上传组件
 */
class Upload_Uploader
{
    private static $mapErrcodeErrstring = array(
        1 => 'upload_file_exceeds_limit',
        2 => 'upload_file_exceeds_form_limit',
        3 => 'upload_file_partial',
        4 => 'upload_no_file_selected',
        6 => 'upload_no_temp_directory',
        7 => 'upload_unable_to_write_file',
        8 => 'upload_stopped_by_extension',
    );

    /**
     * @var Upload_UploadFileBean 临时上传文件对象
     */
    private $uploadFileBean;

    /**
     * @var string 错误信息
     */
    private $error;

    /**
     * @var array 拦截器数组 [拦截器类名:[拦截器对象, 参数]]
     */
    private $interceptors = array();

    /**
     * @var string 根文件夹
     */
    private $basePath;

    /**
     * @var string 子文件夹
     */
    private $subPath;

    /**
     * @var string 上传文件所在完整目录
     */
    private $filePath;

    /**
     * @var string 处理过后将要上传的文件名
     */
    private $realFileName;

    /**
     * @param Upload_UploadFileBean $uploadFileBean 临时上传文件对象
     * @param string $basePath 跟目录
     * @param string $subPath 子目录
     * @param array $interceptors [[0=>Upload_IInterceptor(Interceptor), 1=>mixed(args)], etc...] 拦截器
     * @throws RuntimeException
     */
    public function __construct(Upload_UploadFileBean $uploadFileBean, $basePath, $subPath = '', array $interceptors = array())
    {
        $this->uploadFileBean = $uploadFileBean;
        $this->basePath = $basePath[strlen($basePath)-1]=='/' ? $basePath : ($basePath.'/');
        $this->subPath = ($subPath && $subPath[strlen($subPath)-1]=='/') ? $subPath : ($subPath.'/');
        $this->filePath = $this->basePath . $this->subPath;

        if (!file_exists($this->filePath) && !mkdir($this->filePath, 0777, true)) {
            throw new RuntimeException("Can not create dir[{$this->filePath}]");
        }

        $this->realFileName = $uploadFileBean->getName();
        if (!empty($interceptors)) {
            foreach ($interceptors as $v) {
                if (isset($v[0]) && $v[0] instanceof Upload_IInterceptor && isset($v[1])) {
                    $this->addInterceptor($v[0], $v[1]);
                }
            }
        }
    }

    /**
     * 添加一个上传拦截器
     * @param Upload_IInterceptor $interceptor 拦截器对象
     * @param mixed $args 执行时参数
     * @return Upload_Uploader
     */
    public function addInterceptor(Upload_IInterceptor $interceptor, $args)
    {
        $className = get_class($interceptor);
        if (!isset($this->interceptors[$className])) {
            $this->interceptors[$className] = array($interceptor, $args);
        }
        return $this;
    }

    /**
     * 执行上传
     * @return bool
     */
    public function upload()
    {
        if (!is_uploaded_file($this->uploadFileBean->getTmpName())) {
            $errorNumber = $this->uploadFileBean->getError();
            $this->error = isset(self::$mapErrcodeErrstring[$errorNumber]) ?
                self::$mapErrcodeErrstring[$errorNumber] :
                self::$mapErrcodeErrstring[4];
        }

        foreach ($this->interceptors as $v) {
            /** @var $interceptor Upload_IInterceptor */
            list($interceptor, $args) = $v;

            if (!$interceptor->run($this, $args, $this->error)) {
                return false;
            }
        }

        $tmpFile = $this->uploadFileBean->getTmpName();
        $finalFile = $this->getFilePath() . $this->getRealFileName();
        if (!@move_uploaded_file($tmpFile, $finalFile)) {
            $this->error = 'upload_destination_error';
            $result = false;
        } else {
            $result = true;
        }

        return $result;
    }

    /**
     * @return Upload_UploadFileBean
     */
    public function getUploadFileBean()
    {
        return $this->uploadFileBean;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @return string
     */
    public function getRealFileName()
    {
        return $this->realFileName;
    }

    /**
     * @param string $realFileName
     */
    public function setRealFileName($realFileName)
    {
        $this->realFileName = $realFileName;
    }

    /**
     * @return string
     */
    public function getSubPath()
    {
        return $this->subPath;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }
}