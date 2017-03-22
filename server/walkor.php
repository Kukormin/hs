<?
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/..');
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];

define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

$lib = '/local/php_interface/lib/';
\CModule::AddAutoloadClasses(
	'',
	array(
		'Workerman\\Worker' => $lib . 'Workerman/Worker.php',
		'Workerman\\Autoloader' => $lib . 'Workerman/Autoloader.php',
		'Workerman\\Lib\\Timer' => $lib . 'Workerman/Lib/Timer.php',
		'Local\\Chat\\SocketChat' => $lib . 'chat/Chat.php',
	)
);

iconv_set_encoding("internal_encoding", "ISO-8859-1");
iconv_set_encoding("output_encoding", "ISO-8859-1");
iconv_set_encoding("input_encoding", "ISO-8859-1");

use Workerman\Worker;

$ws_worker = new Worker("websocket://0.0.0.0:2346");

$ws_worker->count = 1;
//$ws_worker->transport = 'ssl';
/*$ws_worker->onWorkerStart = function($ws_worker)
{
	$timer_id = Timer::add(3, function() {
		global $ws_worker;
		echo count($ws_worker->connections) . " ping\n";
		foreach ($ws_worker->connections as $connection)
		{
			$connection->send(pack('H*', '8900'), true);
		}
	});
};*/

// Emitted when new connection come
$ws_worker->onConnect = function($connection)
{
	$connection->onWebSocketConnect = function($connection, $buffer)
	{
		$userId = 0;
		if ($_SERVER['REQUEST_URI'] != '/admin/')
		{

			$authToken = '';
			if (isset($_SERVER['HTTP_X_AUTH']))
				$authToken = $_SERVER['HTTP_X_AUTH'];

			if ($authToken)
			{
				$session = \Local\User\Session::getByToken($authToken);
				if ($session)
					$userId = $session['USER_ID'];
			}

			/*if (strpos($_SERVER['HTTP_USER_AGENT'], 'AppleWebKit') !== false)
				$userId = 54;
			else
				$userId = 1193;
			$connection->send($userId);*/

			if (!$userId)
			{
				$connection->send("401 Not Authorized");
				$connection->close();
				return false;
			}
		}

		\Local\Chat\SocketChat::addConnection($userId, $connection);

		return true;
	};
};

// Emitted when data received
$ws_worker->onMessage = function($connection, $data)
{
	if (!strlen($data)) {
		return false;
	}

	$userId = \Local\Chat\SocketChat::getUser($connection);

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
	$userNickname = '';
	$userName = '';
	$now = time();

	try
	{
		// {"chat":"deal","deal":1290,"message":"тест"}

		//
		// Оператор СП
		//
		if (!$userId)
		{
			$ar = explode('|', $params['key']);
			$type = $ar[0];
			$oid = $ar[1];
			$return['id'] = \Local\Data\Messages::add($params['key'], 0, $params['message']);
			$return['role'] = 0;
			if ($type == 'u')
			{
				\Local\User\User::updateChatInfo($oid, true);
				$params['chat'] = 'usersupport';
				$return['users'] = array(0, $oid);
				$return['suffix'] = 0;
				\Local\User\User::push($oid, 'Новое сообщение от службы поддержки');
			}
			elseif ($type == 'd')
			{
				\Local\Data\Deal::updateChatInfo($oid, true);
				$params['chat'] = $ar[2] ? 'dealsupport' : 'deal';
				$deal = \Local\Data\Deal::getById($oid);
				$return['users'] = array(0);
				if ($ar[2] != 1)
				{
					$return['users'][] = $deal['BUYER'];
					\Local\User\User::push($oid, 'Новое сообщение от службы поддержки');
				}
				if ($ar[2] < 2)
				{
					$return['users'][] = $deal['SELLER'];
					\Local\User\User::push($oid, 'Новое сообщение от службы поддержки');
				}
				$return['suffix'] = $ar[2];
			}
			/*if ($updatestatus && $item['IBLOCK_ID'] == $dealsIblockId)
			{
				$status = \Local\Data\Status::getByCode($updatestatus);
				$deal = \Local\Data\Deal::update($item['ID'], array('STATUS' => $status['ID']));
				\Local\Data\History::add($item['ID'], $status['ID'], 0);
				$activeTab = 'deal';
			}*/
		}
		//
		// Пользователи
		//
		else
		{
			$user = \Local\User\User::getById($userId);
			$userNickname = trim($user['nickname']);
			$userName = trim($user['name']);
			if ($params['chat'] == 'deal')
				$return = \Local\Data\Deal::message($userId, $params['deal'], $params['message'], false);
			elseif ($params['chat'] == 'dealsupport')
				$return = \Local\Data\Deal::message($userId, $params['deal'], $params['message'], true);
			elseif ($params['chat'] == 'usersupport')
				$return = \Local\User\User::message($userId, $params['message']);
		}

		/*$x = array(
			'type' => 'cons',
		    'conId' => $connection->id,
		);
		foreach (\Local\Chat\SocketChat::$CBU as $uid => $cons)
			foreach ($cons as $conId => $con)
				$x[$uid . "|" . $conId] = 1;*/

		foreach ($return['users'] as $uid)
		{
			$cons = \Local\Chat\SocketChat::getConnections($uid);

			foreach ($cons as $con)
			{
				$con->send(json_encode(array(
					'type' => 'new',
					'id' => $return['id'],
					'message' => $params['message'],
					'datef' => ConvertTimeStamp($now, 'FULL'),
					'date' => date('c', $now),
					'chat' => $params['chat'],
					'role' => $return['role'],
					'suffix' => $return['suffix'],
					'user' => $userId,
					'nickname' => $userNickname,
					'name' => $userName,
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
};

// Emitted when connection closed
$ws_worker->onClose = function($connection)
{
	\Local\Chat\SocketChat::closeConnection($connection);
};

// Run worker
Worker::runAll();