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

		$host = 'gateway.sandbox.push.apple.com';
		$port = 2195;
		$сert = $_SERVER['DOCUMENT_ROOT'] . '/push/hishopper.pem';
		$badge = 1;
		$sound = 'default';

		$context = stream_context_create();
		stream_context_set_option($context, 'ssl', 'local_cert', $сert);
		$socket = stream_socket_client('ssl://' . $host . ':' . $port, $error, $errstr, 30,
			STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $context);
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

		fclose($socket);

		if ($result)
			return true;
		else
			return false;
	}

	public static function testMessage($deviceToken, $message)
	{
		if (!$deviceToken || !$message)
			return false;

		$host = 'gateway.sandbox.push.apple.com';
		$port = 2195;
		$сert = $_SERVER['DOCUMENT_ROOT'] . '/push/hishopper.pem';
		$badge = 1;
		$sound = 'default';

		$context = stream_context_create();
		stream_context_set_option($context, 'ssl', 'local_cert', $сert);
		$socket = stream_socket_client('ssl://' . $host . ':' . $port, $error, $errstr, 30,
			STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $context);
		if (!$socket)
			return false;

		$body['aps'] = array(
			'alert' => $message,
			'badge' => $badge,
			'sound' => $sound,
		);
		debugmessage($body);
		$body = json_encode($body, JSON_UNESCAPED_UNICODE);
		debugmessage($body);
		$bodyLen = iconv_strlen($body, 'ISO-8859-1');
		debugmessage($bodyLen);
		$msg = chr(0) . chr(0) . chr(32) . pack('H*', $deviceToken) . pack('n', $bodyLen) . $body;
		debugmessage($msg);
		$msgLen = iconv_strlen($msg, 'ISO-8859-1');
		debugmessage($msgLen);
		$result = fwrite($socket, $msg, $msgLen);
		debugmessage($result);

		fclose($socket);

		if ($result)
			return true;
		else
			return false;

	}

}