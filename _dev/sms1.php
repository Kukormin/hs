<?
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

$gate = new \Local\Common\Sms();

debugmessage($gate->credits()); // узнаем текущий баланс
debugmessage($gate->senders()); // получаем список доступных подписей


$messages = array(
	array(
	   "clientId" => "1",
	   "phone"=> "79176183464",
	   "text"=> "1564",
	   "sender"=> "MediaGramma",
   ),
   /*array(
	   "clientId" => "2",
	   "phone"=> "79176183464",
	   "text"=> "Текст на русском 1564", 
	   "sender"=> "CMC DUCKOHT",
   ),
   /*array(
	   "clientId" => "3",
	   "phone"=> "71234567892",
	   "text"=> "third message",
	   "sender"=> "TEST",
   ),*/
);
$res = $gate->send($messages, 'codeQueue'); // отправляем пакет sms
debugmessage($res);
die();

$messages = array(
	array("clientId"=>"1","smscId"=>1868409800),
	//array("clientId"=>"2","smscId"=>11255143),
	//array("clientId"=>"3","smscId"=>11255144),
);
var_dump($gate->status($messages)); // получаем статусы для пакета sms
//var_dump($gate->statusQueue('testQueue', 10)); // получаем статусы из очереди 'testQueue'*/