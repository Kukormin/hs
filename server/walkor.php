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
	)
);

use Workerman\Worker;

// Create a Websocket server
$ws_worker = new Worker("websocket://0.0.0.0:2346");

// 4 processes
$ws_worker->count = 4;

// Emitted when new connection come
$ws_worker->onConnect = function($connection)
{
	echo "New connection\n";
};

// Emitted when data received
$ws_worker->onMessage = function($connection, $data)
{
	// Send hello $data
	$connection->send('hello ' . $data);
};

// Emitted when connection closed
$ws_worker->onClose = function($connection)
{
	echo "Connection closed\n";
};

// Run worker
Worker::runAll();