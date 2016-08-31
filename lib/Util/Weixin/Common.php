<?php
/*
 *	2016/6/29 
 * ���ڷ�΢����ҵ����Ϣ
 * 
 * qy.weixin.qq.com 
 * ����->������->Ȩ�޹���->Ӧ��Ȩ��
 * ע����Ҫ��Ӧ������ӿɼ���Χ�Ĳ��ţ����ӿ�ʱӦ�úͲ��ű����Ҫ��Ӧ

 * 
 */

class Util_Weixin_Common {
    private $_corpId = '';
    private $_corpSecret = '';
    private $_accessToken = '';

    public function __construct($corpId = 'wx9a8d0bcd3f45c850', $corpSecret = '_SIR5Nw_q3HQovY-TiIVdaDfq3FDl8-mpikWZ_wZ8qxszhGR77t_ucAD13zpN_rs'){
        $this->_corpId = $corpId;

        $this->_corpSecret = $corpSecret;

        $this->_accessToken = $this->__getAccessToken($corpId, $corpSecret);

        log_message('token:'.$this->_accessToken, LOG_DEBUG);
    }

    private function __getAccessToken($corpId, $corpSecret){
        $accessToken = Cache_Memcache::sGet($corpId.$corpSecret);

        if($accessToken){
            log_message('token from cache', LOG_DEBUG);
            return $accessToken;
        }

        $data = http_get('https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid='.$corpId.'&corpsecret='.$corpSecret);

        log_message('res:'.json_encode($data), LOG_DEBUG);

        Cache_Memcache::sSet($corpId.$corpSecret, $data['access_token'], 7000);

        return $data['access_token'];
    }

    /**
     * �ӿ��ĵ��� http://qydev.weixin.qq.com/wiki/index.php?title=%E6%B6%88%E6%81%AF%E7%B1%BB%E5%9E%8B%E5%8F%8A%E6%95%B0%E6%8D%AE%E6%A0%BC%E5%BC%8F
     * @param  [type] $str     [description]
     * @param  string $touser  [description]
     * @param  string $agentid [description]
     * @param  string $msgtype [description]
     * @return [type]          [description]
     */

    /**
     * [sendMsg description]
     * @param  [type] $str     [description]
     * @param  string $touser  [description]
     * @param  string $agentid agentid  ��   ��ҵӦ�õ�id�����͡�����Ӧ�õ�����ҳ��鿴
     * @param  string $msgtype ��Ϣ���ͣ���ʱ�̶�Ϊ��text ��֧����Ϣ��Ӧ�ø���ҳ��Ӧ�ã�
     * @param  string $toparty ����ID�б�����������á�|���ָ������֧��100������touserΪ@allʱ���Ա�����
     * @param  string $totag   ��ǩID�б�����������á�|���ָ�����touserΪ@allʱ���Ա�����
     * @return [type]          [description]
     */
    public function sendMsg($str,$touser='@all',$agentid=1,$msgtype='text',$toparty='',$totag=''){

        $body = array(

            'msgtype' =>$msgtype
            // agentid  ��   ��ҵӦ�õ�id�����͡�����Ӧ�õ�����ҳ��鿴
            ,'agentid' => $agentid
            ,'text' => array(
                'content' => $str
            )
            // ��ʾ�Ƿ��Ǳ�����Ϣ��0��ʾ��1��ʾ�ǣ�Ĭ��0
            ,'safe' => '0'
        );

        if(!empty($touser)){
            $body['touser'] = $touser;
        }   

        if(!empty($toparty)){

            if(is_array($toparty)){
                $toparty = implode('|',$toparty);
            }
            $body['toparty'] = $toparty;
        }   

        if(!empty($totag)){
            $body['totag'] = $totag;
            if(is_array($totag)){
                $totag = implode('|',$totag);
            }            
        }   

// print_r($body);
        $body = json_encode($body, JSON_UNESCAPED_UNICODE);

        $res = http_post('https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=' . $this->_accessToken, $body);

        return $res;
    }

    /**
     * ����Ϣ��ָ������
     * @param  [type] $str     [description]
     * @param  [type] $toparty [description]
     * @param  string $agentid [description]
     * @return [type]          [description]
     */
    public function sendToParty($str,$toparty,$agentid='1'){

        $touser='';
        $msgtype='text';
        $totag='';

        return $this->sendMsg($str,$touser,$agentid,$msgtype,$toparty,$totag);

    }

    /**
     * ����Ϣ��ָ������
     * @param  [type] $str     [description]
     * @param  [type] $toparty [description]
     * @param  string $agentid [description]
     * @return [type]          [description]
     */
    public function sendToTag($str,$totag,$agentid='1'){

        $touser='';
        $msgtype='text';
        $toparty='';

        return $this->sendMsg($str,$touser,$agentid,$msgtype,$toparty,$totag);

    }


}