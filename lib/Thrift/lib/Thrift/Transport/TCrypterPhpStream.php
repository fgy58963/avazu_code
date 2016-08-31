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


class TCrypterPhpStream extends TTransport {

  const MODE_R = 1;
  const MODE_W = 2;

  private $inStream_ = null;

  private $outStream_ = null;

  private $read_ = false;

  private $write_ = false;

  private $read_buf_ = false;

  private $read_idx_ = 0;

  private $write_buf_ = "";

  private $crypter_ = null;

  public function __construct($mode, $crypter) {
    $this->read_ = $mode & self::MODE_R;
    $this->write_ = $mode & self::MODE_W;
    $this->crypter_ = $crypter;
  }

  public function open() {
    if ($this->read_) {
      $this->inStream_ = @fopen(self::inStreamName(), 'r');
      if (!is_resource($this->inStream_)) {
        throw new TException('TCrypterPhpStream: Could not open php://input');
      }
    }
    if ($this->write_) {
      $this->outStream_ = @fopen('php://output', 'w');
      if (!is_resource($this->outStream_)) {
        throw new TException('TCrypterPhpStream: Could not open php://output');
      }
    }
  }

  public function close() {
    if ($this->read_) {
      @fclose($this->inStream_);
      $this->inStream_ = null;
    }
    if ($this->write_) {
      $this->flush();
      @fclose($this->outStream_);
      $this->outStream_ = null;
    }
  }

  public function isOpen() {
    return
      (!$this->read_ || is_resource($this->inStream_)) &&
      (!$this->write_ || is_resource($this->outStream_));
  }

  function checkRead() {
    if ($this->read_buf_ === false) {
      $this->read_buf_ = @stream_get_contents($this->inStream_);
      if ($this->crypter_) {
        $this->read_buf_ = $this->crypter_->decrypt($this->read_buf_);
      }
    }
  }

  function getReadBuffer() {
    $this->checkRead();

    return $this->read_buf_;
  }

  public function read($len) {
    $this->checkRead();

    $total = TStringFuncFactory::create()->strlen($this->read_buf_);

    if ($len > ($total - $this->read_idx_))
      throw new TException('TCrypterPhpStream: Could not read '.$len.' bytes');

    $data = TStringFuncFactory::create()->substr($this->read_buf_, $this->read_idx_, $len);
    $this->read_idx_ += $len;
    return $data;
  }

  public function write($buf) {
    $this->write_buf_ .= $buf;
  }

  function commitWrite() {
    $write_buf_len = TStringFuncFactory::create()->strlen($this->write_buf_);
    if ($write_buf_len > 0) {
      $buf = $this->write_buf_;
      if ($this->crypter_) 
        $buf = $this->crypter_->encrypt($this->write_buf_);
      $buf_len = TStringFuncFactory::create()->strlen($buf);
      if (php_sapi_name() != 'cli') {
        header("Package-Length:".$buf_len, true);
      }
      while (TStringFuncFactory::create()->strlen($buf) > 0) {
        $got = @fwrite($this->outStream_, $buf);
        if ($got === 0 || $got === FALSE) {
          throw new TException('TCrypterPhpStream: Could not write '.TStringFuncFactory::create()->strlen($buf).' bytes');
        }
        $buf = TStringFuncFactory::create()->substr($buf, $got);
      }
      $this->write_buf_ = "";
    }
  }

  public function flush() {
    $this->commitWrite();
    @fflush($this->outStream_);
  }

  private static function inStreamName() {
    if (php_sapi_name() == 'cli') {
      return 'php://stdin';
    }
    return 'php://input';
  }

}

