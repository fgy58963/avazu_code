<?php
//参考http://www.ruturaj.net/scribe-php-logging
$GLOBALS['THRIFT_ROOT'] = '.';

include_once $GLOBALS['THRIFT_ROOT'] . '/scribe.php';
include_once $GLOBALS['THRIFT_ROOT'] . '/transport/TSocket.php';
include_once $GLOBALS['THRIFT_ROOT'] . '/transport/TFramedTransport.php';
include_once $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php';

$msg1['category'] = 'test';
$msg1['message'] = "This is some message for the category\n";
$msg2['category'] = 'test';
$msg2['message'] = "Some other message for the category\n";
//$log_entry = new LogEntry( array('category'=>$category, 'category'=>$category) ) 
$entry1 = new LogEntry($msg1);
$entry2 = new LogEntry($msg2);
$messages = array($entry1, $entry2);

$socket = new TSocket('localhost', 1463, true);
$transport = new TFramedTransport($socket);
//$protocol = new TBinaryProtocol($trans, $strictRead=false, $strictWrite=true)
$protocol = new TBinaryProtocol($transport, false, false);
//$scribe_client = new scribeClient($iprot=$protocol, $oprot=$protocol)
$scribe_client = new scribeClient($protocol, $protocol);

$transport->open();
$scribe_client->Log($messages);
$transport->close();
?>
