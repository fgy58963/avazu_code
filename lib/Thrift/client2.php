<?php

error_reporting(E_ALL);

require_once(__DIR__.'/lib/Thrift/ClassLoader/ThriftClassLoader.php');

use Thrift\ClassLoader\ThriftClassLoader;

$GEN_DIR = realpath(dirname(__FILE__)).'/../../channels/api/application/third_party/thrift/proto/client';

$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', __DIR__ . '/lib');

$loader->registerDefinition('com\dotc\ime\protocol', $GEN_DIR);
$loader->register();

/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

/*
 * This is not a stand-alone server.  It should be run as a normal
 * php web script (like through Apache's mod_php) or as a cgi script
 * (like with the included runserver.py).  You can connect to it with
 * THttpClient in any language that supports it.  The PHP tutorial client
 * will work if you pass it the argument "--http".
 */

use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TSocket;
use Thrift\Transport\THttpClient;
use Thrift\Transport\Aes128CbcPKCS5PaddingCrypter;
use Thrift\Transport\TCrypterHttpClient;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Transport\TPhpStream;
use Thrift\Transport\TBufferedTransport;
use Thrift\Exception\TException;

$crypter = new Aes128CbcPKCS5PaddingCrypter("76D56BCC3A5985E6", "E202332F1660EB54");
//$crypter = new Aes128CbcPKCS5PaddingCrypter("95db364cd0bf2d49", "e4dc771ebc9c8d38");

$domain = Config::get('api_domain');

//$domain = '192.168.7.246';
var_dump($domain);
$socket = new TCrypterHttpClient($domain, 80, "/index.php", "http", $crypter);
$transport = new TBufferedTransport($socket, 1024, 1024);
$protocol = new TBinaryProtocol($transport);
$client = new \com\dotc\ime\protocol\ImeServiceClient($protocol);

//$socket->addHeaders(array('Cookie' => 'locksessid=7b4e2760907d0bc67aefb87d00f204ab'));
$transport->open();
//======获取配置开始
//$request = new \com\dotc\ime\protocol\ConfigRequest();
//
//$res = $client->loadConfig($request);
//======获取配置结束

//======获取语言包开始
$langRequset = new \com\dotc\ime\protocol\LanguagePackageInfoRequest();

$userClient = new \com\dotc\ime\protocol\ClientInfo();

$userClient->appInfo = null;

$userClient->deviceInfo = null;

$userClient->systemInfo = null;

//$langRequset->lang = 'cn';

$res = $client->loadLanguagePackageInfos($langRequset);
//======获取语言包结束

//======获取词库开始
//$dictionaryRequset = new \com\dotc\ime\protocol\DictionaryPackageInfoRequest();
//
//$res = $client->loadDictionaryPackageInfos($dictionaryRequset);
//======获取词库结束

//======获取表情包开始
//$emotionRequest = new \com\dotc\ime\protocol\EmotionPackageInfoRequest();
//
//$emotionRequest->lang = '';
//
//$res = $client->loadEmotionPackageInfos($emotionRequest);
//======获取表情包结束

//======获取皮肤包开始
//$skinRequest = new \com\dotc\ime\protocol\SkinPackageInfoRequest();
//
//$skinRequest->lang = '';
//
//$res = $client->loadSkinPackageInfos($skinRequest);
//======获取皮肤包结束

//======增加问题反馈开始
//$feedbackRequest = new \com\dotc\ime\protocol\FeedbackRequest();
//
//$feedbackRequest->email = 'jie.pan@avazu.net';
//
//$feedbackRequest->title = 'test';
//
//$feedbackRequest->content = 'test';
//
//$attachment = new \com\dotc\ime\protocol\Attachment();
//
//$attachment->data = file_get_contents("http://static.oschina.net/uploads/ad/question_banner_one_250_250(07_02_17_23_02)_QveGU.jpg");
//
//$attachments[] = $attachment;
//
//$attachment->data = file_get_contents("http://static.oschina.net/uploads/space/2015/0624/114103_4YFL_12.png");
//
//$attachments[] = $attachment;
//
//$feedbackRequest->attachments = $attachments;
//
//$res = $client->feedback($feedbackRequest);
//======增加问题反馈结束

//======增加package downloads(有返回值)开始
//$statRequest = new \com\dotc\ime\protocol\StatRequest();
//
//$statRequest->id = 1;
//
//$res = $client->commitStat($statRequest);
//======增加package downloads(有返回值)结束

//======增加package downloads(无返回值)开始
//$statRequest = new \com\dotc\ime\protocol\StatRequest();
//
//$statRequest->id = 1;
//
//$client->notifyStat($statRequest);
//======增加package downloads(无返回值)开始

echo json_encode($res)."\n";

$transport->close();
