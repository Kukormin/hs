<?
class iqsms_JsonGate
{
	
    const ERROR_EMPTY_RESPONSE = 'errorEmptyResponse';
    
	protected $_apiLogin = 'dupepav2402';
	
	protected $_apiPassword = '782850';
	
    protected $_host = 'json.gate.iqsms.ru';
    
    protected $_packetSize = 200;
    
    protected $_results = array();
	
    public function setHost($host)
    {
    	$this->_host = $host;
    }
    
    public function getHost()
    {
    	return $this->_host;
    }
	
    private function _sendRequest($uri, $params = null)
    {
    	$url = $this->_getUrl($uri);
    	$data = $this->_formPacket($params);

    	$client = curl_init($url);
        curl_setopt_array($client, array(
        	CURLOPT_RETURNTRANSFER => true,
        	CURLOPT_POST => true,
        	CURLOPT_HEADER => false,
        	CURLOPT_HTTPHEADER => array('Host: ' . $this->getHost()),
        	CURLOPT_POSTFIELDS => $data,
        ));
        
        $body = curl_exec($client);
		curl_close($client);
        if (empty($body)) {
        	throw new Exception(self::ERROR_EMPTY_RESPONSE);
        }
        $decodedBody = json_decode($body, true);
        if (is_null($decodedBody)) {
        	throw new Exception($body);
        }
        return $decodedBody;
    }
    
    private function _getUrl($uri)
    {
    	return 'http://' . $this->getHost() . '/' . $uri . '/';
    }
    
    private function _formPacket($params = null)
    {
    	$params['login'] = $this->_apiLogin;
        $params['password'] = $this->_apiPassword;
        foreach ($params as $key => $value) {
        	if (empty($value)) {
        		unset($params[$key]);
        	}
        }
        $packet = json_encode($params);
        return $packet;
    }
    
    public function getPacketSize()
    {
    	return $this->_packetSize;
    }
    
    public function send($messages, $statusQueueName = null, $scheduleTime = null)
    {
        return $this->_sendRequest('send', array(
           'messages' => $messages,
           'statusQueueName' => $statusQueueName,
           'scheduleTime' => $scheduleTime,
        ));
    }
    
    public function status($messages)
    {
    	return $this->_sendRequest('status', array(
			'messages' => $messages)
		);
    }
    
    public function statusQueue($name, $limit)
    {
    	return $this->_sendRequest('statusQueue', array(
    		'statusQueueName' => $name,
    		'statusQueueLimit' => $limit,
    	));
    }
    
    public function credits()
    {
    	return $this->_sendRequest('credits');
    }
    
    public function senders()
    {
    	return $this->_sendRequest('senders');
    }
    
}

$gate = new iqsms_JsonGate();

var_dump($gate->credits()); // узнаем текущий баланс
var_dump($gate->senders()); // получаем список доступных подписей

$messages = array(
	/*array(
	   "clientId" => "1",
	   "phone"=> "79176183464",
	   "text"=> "first message",
	   "sender"=> "MediaGramma",
   ),*/
   array(
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
var_dump($gate->send($messages, 'testQueue')); // отправляем пакет sms

$messages = array(
	array("clientId"=>"1","smscId"=>1868409800),
	//array("clientId"=>"2","smscId"=>11255143),
	//array("clientId"=>"3","smscId"=>11255144),
);
var_dump($gate->status($messages)); // получаем статусы для пакета sms
//var_dump($gate->statusQueue('testQueue', 10)); // получаем статусы из очереди 'testQueue'*/