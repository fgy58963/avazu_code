<?php
/**
 * google gsm 等消息推送
 */
class Util_MsgPush {


    private $_gsmKey = 'AIzaSyDQXYlHEuFlh0DJpw6ygT4XEGSIjfFyw_A';

	/**
	 * [gmsPush description]
	 * https://avazu1.atlassian.net/wiki/pages/viewpage.action?pageId=42434673
	 * google开发文档：https://developers.google.com/cloud-messaging/http?hl=zh-cn
	 * @param  array  $dataArray [description]
	 * @return [type]            [description]
	 */
	public function gmsPush($dataArray, $key = '') {

		$push_url  = 'https://gcm-http.googleapis.com/gcm/send';
		// $push_url = 'http://localhost/test.php';
		if(empty($key)) $key = $this->_gsmKey;
		$post_data = json_encode($dataArray);

		$header_param = array(
			'Authorization' => 'key='.$key
			, 'Content-Type' => 'application/json',
		);

/*
成功后返回：
{"multicast_id":5325827957553175196,"success":1,"failure":0,"canonical_ids":0,"results":[{"message_id":"0:1464058105969495%8838e283f9fd7ecd"}]}
 */
		$result = F::$f->Util_HttpCurl()->httpPost($push_url, $post_data, $header_param);
        return $result;
	}

}