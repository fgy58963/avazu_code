<?php
/**
 * 对象工厂, 获取类的单例
 *
 * @example
 * 实例化的类无构造参数情况:
 * Factory::getInstance('ClassName');
 * Factory::$f->ClassName;
 * Factory::$f->ClassName();
 *
 * 实例化的类有构造参数情况:
 * Factory::getInstance('ClassName', 'Param1', 'Param2');
 * Factory::$f->ClassName('Param1', 'Param2')
 */
class Factory
{
    private static $instances = array();
    
    public static $f = NULL;
    
    private function __construct ()
    {
    }
    
    public static function _init ()
    {
        if (self::$f === NULL) {
            self::$f = new self();
        }
    }
    
    public static function getInstance ()
    {
        $args = func_get_args();
        $class = array_shift($args);
        $key = strtolower($class) . implode('|', $args);
        
        if (isset(Factory::$instances[$key])) {
            return Factory::$instances[$key];
        }
        
        $instance = NULL;
        switch (count($args)) {
            case 1:
                $instance = new $class($args[0]);
                break;
            case 2:
                $instance = new $class($args[0], $args[1]);
                break;
            case 3:
                $instance = new $class($args[0], $args[1], $args[2]);
                break;
            case 4:
                $instance = new $class($args[0], $args[1], $args[2], $args[3]);
                break;
            default:
                $instance = new $class();
                break;
        }
        
        Factory::$instances[$key] = $instance;
        return $instance;
    }
    
    public function __call ($name, $arguments)
    {
        if (empty($arguments)) {
            $arguments = array();
        }
        
        array_unshift($arguments, $name);
        return call_user_func_array(array(
            'Factory' , 
            'getInstance'
        ), $arguments);
    }
    
    public function __get ($name)
    {
        return self::__call($name, NULL);
    }
}

Factory::_init();