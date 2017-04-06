<?

namespace Local\User;

/**
 * Class Push Отправка сообщений в пуш сервер
 * @package Local\User
 */
class Push
{
	public static function message($deviceToken, $message, $add = array())
	{
		if (!$deviceToken || !$message)
			return false;

		$result = '';
		$log = array();

		//$host = 'gateway.sandbox.push.apple.com';
		$host = 'gateway.push.apple.com';
		$port = 2195;
		//$сert = $_SERVER['DOCUMENT_ROOT'] . '/push/hishopper.pem';
		$сert = $_SERVER['DOCUMENT_ROOT'] . '/push/prod.pem';
		$pass = 'y6T%r4E#';
		$badge = 1;
		$sound = 'default';

		$context = stream_context_create();
		stream_context_set_option($context, 'ssl', 'local_cert', $сert);
		stream_context_set_option($context, 'ssl', 'passphrase', $pass);
		$socket = stream_socket_client('ssl://' . $host . ':' . $port, $error, $errstr, 30,
			STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $context);
		$log['socket'] = $socket ? 1 : 0;
		$log['error'] = $error;
		if ($socket)
		{

			$body = $add;
			$body['aps'] = array(
				'alert' => $message,
				'badge' => $badge,
				'sound' => $sound,
			);

			$body = json_encode($body, JSON_UNESCAPED_UNICODE);
			$bodyLen = iconv_strlen($body, 'ISO-8859-1');
			$msg = chr(0) . chr(0) . chr(32) . pack('H*', $deviceToken) . pack('n', $bodyLen) . $body;
			$msgLen = iconv_strlen($msg, 'ISO-8859-1');
			$result = fwrite($socket, $msg, $msgLen);
			$log['result'] = $msgLen . ' - ' . $result;

			fclose($socket);
		}

		_log_array($log);

		if ($result)
			return true;
		else
			return false;
	}

	public static function testMessage($deviceToken, $message, $add = array())
	{
		if (!$deviceToken || !$message)
			return false;

		//$host = 'gateway.sandbox.push.apple.com';
		$host = 'gateway.push.apple.com';
		$port = 2195;
		//$сert = $_SERVER['DOCUMENT_ROOT'] . '/push/hishopper.pem';
		$сert = $_SERVER['DOCUMENT_ROOT'] . '/push/prod.pem';
		$pass = 'y6T%r4E#';
		$badge = 1;
		$sound = 'default';

		$context = stream_context_create();
		stream_context_set_option($context, 'ssl', 'local_cert', $сert);
		stream_context_set_option($context, 'ssl', 'passphrase', $pass);
		stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
		$socket = stream_socket_client('ssl://' . $host . ':' . $port, $error, $errstr, 30,
			STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $context);
		debugmessage($socket);
		debugmessage($error);
		debugmessage($errstr);
		if (!$socket)
			return false;

		$body = $add;
		$body['aps'] = array(
			'alert' => $message,
			'badge' => $badge,
			'sound' => $sound,
		);

		$body = json_encode($body, JSON_UNESCAPED_UNICODE);
		$bodyLen = iconv_strlen($body, 'ISO-8859-1');
		$msg = chr(0) . chr(0) . chr(32) . pack('H*', $deviceToken) . pack('n', $bodyLen) . $body;
		$msgLen = iconv_strlen($msg, 'ISO-8859-1');
		$result = fwrite($socket, $msg, $msgLen);

		debugmessage($body);
		debugmessage($bodyLen);
		debugmessage($msg);
		debugmessage($msgLen);
		debugmessage($result);

		fclose($socket);

		if ($result)
			return true;
		else
			return false;
	}

}