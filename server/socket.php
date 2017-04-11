<?
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/..');

define('API' , 'https://hi-shopper-app.ru/api/v1');

require_once('Workerman/Autoloader.php');
require_once('Chat.php');
require_once('Http.php');

iconv_set_encoding("internal_encoding", "ISO-8859-1");
iconv_set_encoding("output_encoding", "ISO-8859-1");
iconv_set_encoding("input_encoding", "ISO-8859-1");

use Workerman\Worker;

$ws_worker = new Worker("websocket://0.0.0.0:2346");
$ws_worker->count = 1;

// Emitted when new connection come
$ws_worker->onConnect = function($connection)
{
	$connection->onWebSocketConnect = function($connection, $buffer)
	{
		$user = array(
			'id' => 0,
			'name' => '',
			'nickname' => '',
			'auth' => '',
		);
		if ($_SERVER['REQUEST_URI'] != '/admin/')
		{
			$user = \Chat\SocketChat::getUser();

			if (!$user)
			{
				$connection->send("401 Not Authorized");
				$connection->close();
				return false;
			}
		}

		\Chat\SocketChat::addConnection($user, $connection);

		return true;
	};
};

$ws_worker->onMessage = function($connection, $data)
{
	if (!strlen($data)) {
		return false;
	}

	$user = \Chat\SocketChat::getUserData($connection);

	try
	{
		$params = json_decode($data, true);
	}
	catch (\Exception $e)
	{
		$message = json_encode(Array(
			'errors' => array('json_decode_error'),
			'code' => $e->getCode(),
			'message' => $e->getMessage(),
		), JSON_UNESCAPED_UNICODE);
		$connection->send($message);
		return false;
	}

	if (!$params['message'])
		return false;

	$return = array();
	$message = '';
	$now = time();

	try
	{

		//
		// Оператор СП
		//
		if (!$user['id'])
		{
			// {"key":"u|1231","message":"тест"}
			// {"key":"d|2|2435","message":"тест"}

			$http = new \Http();
			$return = $http->post(API . '/support/message', $data);
			$chat = '';
		}
		//
		// Пользователи
		//
		else
		{
			// {"chat":"deal","deal":1290,"message":"тест"}

			$http = new \Http(array(
				'auth' => $user['auth'],
			));
			if ($params['chat'] == 'deal')
				$return = $http->post(API . '/deal/message', $data);
			elseif ($params['chat'] == 'dealsupport')
				$return = $http->post(API . '/deal/support', $data);
			elseif ($params['chat'] == 'usersupport')
				$return = $http->post(API . '/user/support', $data);
			$chat = $params['chat'];
		}

		foreach ($return['result']['users'] as $uid)
		{
			$cons = \Chat\SocketChat::getConnections($uid);

			foreach ($cons as $con)
			{
				$con->send(json_encode(array(
					'type' => 'new',
					'id' => $return['result']['id'],
					'message' => $params['message'],
					'datef' => date('d.m.Y H:i:s', $now),
					'date' => date('c', $now),
					'chat' => $chat,
					'role' => $return['result']['role'],
					'suffix' => $return['result']['suffix'],
					'user' => $user['id'],
					'nickname' => $user['nickname'],
					'name' => $user['name'],
				), JSON_UNESCAPED_UNICODE));
			}
		}
	}
	catch (\Local\Api\ApiException $e)
	{
		$return = array(
			'result' => null,
			'errors' => $e->getErrors(),
		);
		if ($e->getMessage())
			$return['message'] = $e->getMessage();
		$message = json_encode($return, JSON_UNESCAPED_UNICODE);
	}
	catch (\Exception $e)
	{
		$message = json_encode(Array(
			'errors' => array('unknown_error'),
			'code' => $e->getCode(),
			'message' => $e->getMessage(),
		), JSON_UNESCAPED_UNICODE);
	}

	if ($message)
		$connection->send($message);

	return true;
};

// Emitted when connection closed
$ws_worker->onClose = function($connection)
{
	\Chat\SocketChat::closeConnection($connection);
};

// Run worker
Worker::runAll();