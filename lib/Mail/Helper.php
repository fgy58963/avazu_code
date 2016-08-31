<?php
class Mail_Helper
{
    public static function getMailLoginUrl($email)
    {
        //获取邮箱帐号的后缀
        $domain = @array_shift(explode('.', array_pop(explode('@', $email, 2))));
        
        switch ($domain)
        {
            case "163":   //网易163
                return "http://mail.163.com/";
            case "126":   //  网易126
                return "http://mail.126.com/";
            case "sina":  //  sina
                return "http://mail.sina.com.cn/";
            case "yahoo": //雅虎
                return "http://mail.cn.yahoo.com/";
            case "sohu":  //  搜狐
                return "http://mail.sohu.com/";
            case "yeah":  //网易yeah.net
                return "http://www.yeah.net/";
            case "gmail": //Gmail
                return "http://gmail.google.com/";
            case "hotmail":   //Hotmail
                return "http://www.hotmail.com/";
            case "live":      //Hotmail
                return "http://www.hotmail.com/";
            case "qq":        //QQ
                return "https://mail.qq.com/";
            case "139":       //139
                return "http://mail.10086.cn/";
            default:
                return "http://mail.".$domain.".com";
            
        }
        
        return '';
    }
}