<?php
/**
 * 发送邮件
 */
class Mail_Sendmail
{
    public function Mail_Sendmail() {
    }

	public static function sendmail($to, $from, $fromdesc, $subject, $plaintext, $content, $attachment = NULL) {
		static $phpmailer = NULL;

		if ( $phpmailer == NULL ) {
			$phpmailer = new Mail_PHPMailer();
		}
        
        $phpmailer->ClearAddresses();

		if ( !is_array($to) ) {
			$to = array($to);
		}
		try {
            $phpmailer->CharSet = "UTF-8";
            $smtpServer = Config::get('smtp_server');
            if ($smtpServer) {
                $phpmailer->IsSMTP();
                $phpmailer->SMTPAuth = false;
                $phpmailer->Host = $smtpServer;
            } else {
              $phpmailer->IsSendmail();
            }

            $phpmailer->SetFrom($from, "=?UTF-8?B?".base64_encode($fromdesc)."?=");
            foreach ( $to as $dest ) {
                $destname = @ explode('@', $dest);
                $destname = $destname[0];
                $phpmailer->AddAddress($dest, "=?UTF-8?B?".base64_encode($destname)."?=");
            }
            $phpmailer->Subject = "=?UTF-8?B?".base64_encode($subject)."?=";
            $phpmailer->AltBody = $plaintext;
            $phpmailer->MsgHTML($content);
            if($attachment) {
                $phpmailer->AddAttachment($attachment['dir'], $attachment['name']);
            }
            $phpmailer->Send();
            return TRUE;
		} catch (phpmailerException $e) {
		    return FALSE;
		} catch (Exception $e) {
		    return FALSE;
		}

		return TRUE;
	}

    /**
     * 发送邮件
     * @param  [type] $to         [description]
     * @param  [type] $subject    [description]
     * @param  [type] $plaintext  [description]
     * @param  [type] $content    [description]
     * @param  [type] $attachment [description]
     * @return [type]             [description]
     */
    public static function mail($to, $subject, $plaintext, $content, $attachment = NULL) {
        $mailList = array(
            'smtp' => 'IsSMTP',
            'mail' => 'IsMail',
            'sendmail' =>'IsSendmail' ,
            'qmail' =>'IsQmail',
        );
        static $phpmailer = NULL;
        if ( $phpmailer == NULL ) {
            $phpmailer = new Mail_PHPMailer();
        }
        $phpmailer->ClearAddresses();
        if ( !is_array($to) ) {
            $to = array($to);
        }
        $mailInfo = Config::get('mail');
        if (empty($mailInfo)) return false;
        $type = isset($mailInfo['type']) ? $mailInfo['type'] :'smtp';
        $server = isset($mailInfo['smtp']) ?$mailInfo['smtp'] :'smtp.teebik-inc.com';
        $user = isset($mailInfo['user']) ? $mailInfo['user'] :'tbgames@teebik-inc.com';
        $pwd = isset($mailInfo['pwd']) ? $mailInfo['pwd'] :'FSrW#$DSf';
        $fromDesc = isset($mailInfo['name']) ? $mailInfo['name'] :'Teebik Games';
        $port = isset($mailInfo['port']) ?  intval($mailInfo['port']) :25;
        try {
            $phpmailer->CharSet = "UTF-8";
            //选择类型
            if (isset($mailList[$type]))
            {
                $mailType = $mailList[$type];
                $phpmailer->$mailType();
            } else {
                $phpmailer->IsSMTP();
            }
            if($type == 'smtp')
            {
                $phpmailer->SMTPAuth = true;
                $phpmailer->Username = $user;
                $phpmailer->Password = $pwd;
            }
            $phpmailer->Port = $port;
            $phpmailer->Host = $server;
            $phpmailer->SetFrom($user, "=?UTF-8?B?".base64_encode($fromDesc)."?=");
            foreach ( $to as $dest ) {
                $destname = @ explode('@', $dest);
                $destname = $destname[0];
                $phpmailer->AddAddress($dest, "=?UTF-8?B?".base64_encode($destname)."?=");
            }
            $phpmailer->Subject = "=?UTF-8?B?".base64_encode($subject)."?=";
            $phpmailer->AltBody = $plaintext;
            $phpmailer->MsgHTML($content);
            if($attachment) {
                $phpmailer->AddAttachment($attachment['dir'], $attachment['name']);
            }
            $phpmailer->Send();
            return TRUE;
        } catch (phpmailerException $e) {
            return FALSE;
        } catch (Exception $e) {
            return FALSE;
        }

        return TRUE;
    }

      
}
