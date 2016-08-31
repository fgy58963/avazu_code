<?php
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
 *
 * @package thrift.transport
 */

namespace Thrift\Transport;

use Thrift\Transport\TTransport;
use Thrift\Transport\TCrypter;
use Thrift\Exception\TException;
use Thrift\Factory\TStringFuncFactory;


class Aes128CbcPKCS5PaddingCrypter implements TCrypter {

  protected $cipher_ = "rijndael-128";

  protected $mode_ = "cbc";

  protected $password_;

  protected $iv_;

  public function __construct($password, $iv) {
    $this->password_ = $password;
    $this->iv_ = $iv;
  }

  public function decrypt($str) {
    $decrypted = mcrypt_decrypt($this->cipher_, $this->password_, $str, $this->mode_, $this->iv_);
    return $this->pkcs5_unpad($decrypted);
  }

  public function encrypt($str) {
    $block_size = mcrypt_get_block_size($this->cipher_, $this->mode_);
    return mcrypt_encrypt($this->cipher_, $this->password_, $this->pkcs5_pad($str, $block_size), $this->mode_, $this->iv_);
  }

  function pkcs5_pad($text, $blocksize) {
    $pad = $blocksize - (TStringFuncFactory::create()->strlen($text) % $blocksize);
    return $text . str_repeat(chr($pad), $pad);
  }

  function pkcs5_unpad($text) {
    $pad = ord($text{TStringFuncFactory::create()->strlen($text)-1});
    if ($pad > TStringFuncFactory::create()->strlen($text)) return false;
    if (strspn($text, chr($pad), TStringFuncFactory::create()->strlen($text) - $pad) != $pad) return false;
    return TStringFuncFactory::create()->substr($text, 0, -1 * $pad);
  }
}
