<?php
/**
 * @Author: liuzhen02@snda.com
 * @Date: 12-12-21
 * @Version: 1.0
 *
 * 文件临时上传对象
 */
class Upload_UploadFileBean
{
    /**
     * @var string 临时上传文件错误
     */
    private $error;

    /**
     * @var string 临时上传文件名称
     */
    private $name;

    /**
     * @var string 临时上传文件对象大小
     */
    private $size;

    /**
     * @var string 临时上传文件完整路径
     */
    private $tmp_name;

    /**
     * @var string 临时上传文件 mime 类型
     */
    private $type;

    /**
     * @var string 上传过程中的附加信息
     */
    private $resultData = array();

    /**
     * @param $FILE 格式与数组 $_FILES[your_upload_filed] 相同
     * @throws RuntimeException
     */
    public function __construct($FILE)
    {
        foreach (get_class_vars('Upload_UploadFileBean') as $k=>$v) {
            if (!isset($FILE[$k])) {
                continue;
            }
            $v = $FILE[$k];
            switch ($k) {
                case 'type':
                    $this->type = strtolower(trim(stripslashes(preg_replace("/^(.+?);.*$/", "\\1", $v)), '"'));
                    break;
                case 'tmp_name':
                    if (!is_file($v)) {
                        throw new RuntimeException("File[$v] is not validate");
                    }
                    $this->$k = $v;
                    break;
                default:
                    $this->$k = $v;
                    break;
            }
        }
    }

    /**
     * 获取扩展名
     * @return string
     */
    public function getExt()
    {
        $pos = strrpos($this->getName(), '.');
        return $pos===false ? '' : substr($this->getName(), $pos+1);
    }

    /**
     * 上传过程中, 可通过此方法附加信息
     * @param mixed $k
     * @param mixed $v
     * @return Upload_UploadFileBean
     */
    public function addResultData($k, $v)
    {
        if (!is_array($k)) {
            $k = array($k);
        }
        foreach ($k as $kk) {
            $this->resultData[$kk] = $v;
        }
        return $this;
    }

    # getter and setter
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getResultData()
    {
        return $this->resultData;
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getTmpName()
    {
        return $this->tmp_name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}