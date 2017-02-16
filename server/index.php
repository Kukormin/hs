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
		'Local\\Chat\\ChatWebsocketDaemonHandler' => $lib . 'chat/ChatWebsocketDaemonHandler.php',
		'Local\\Chat\\Daemon' => $lib . 'chat/Daemon.php',
		'Local\\Chat\\Server' => $lib . 'chat/Server.php',
	)
);

if (empty($argv[1]) || !in_array($argv[1], array('start', 'stop', 'restart'))) {
	die("need parameter (start|stop|restart)\r\n");
}

$config = array(
	'pid' => '/tmp/websocket_chat.pid',
	'websocket' => 'tcp://0.0.0.0:2346',
);

$WebsocketServer = new \Local\Chat\Server($config);
call_user_func(array($WebsocketServer, $argv[1]));