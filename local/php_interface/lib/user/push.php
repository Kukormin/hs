<?

namespace Local\User;

/**
 * Class Push Отправка сообщений в пуш сервер
 * @package Local\User
 */
class Push
{
	public static function message($deviceToken, $message)
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

		$tHost = 'gateway.sandbox.push.apple.com';
		$tPort = 2195;
		$tCert = $_SERVER['DOCUMENT_ROOT'] . '/push/hishopper.pem';
		$tToken = $deviceToken;
		$tAlert = $message;
		$tBadge = 1;
		$tSound = 'default';
		$tBody['aps'] = array(
			'alert' => $tAlert,
			'badge' => $tBadge,
			'sound' => $tSound,
		);
		$tBody = json_encode($tBody, JSON_UNESCAPED_UNICODE);
		$s = pack('n', iconv_strlen($tBody, 'ISO-8859-1'));
		debugmessage(iconv_strlen($tBody, 'ISO-8859-1'));
		debugmessage($s);
		$s = pack('n', strlen($tBody));
		debugmessage(strlen($tBody));
		debugmessage($s);
		/*
		$tContext = stream_context_create();
		stream_context_set_option($tContext, 'ssl', 'local_cert', $tCert);
		$tSocket = stream_socket_client('ssl://' . $tHost . ':' . $tPort, $error, $errstr, 30, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $tContext);
		if (!$tSocket)
			return false;

		$tMsg = chr(0) . chr(0) . chr(32) . pack('H*', $tToken) . pack('n', iconv_strlen($tBody)) . $tBody;
		$l = iconv_strlen($tMsg);
		debugmessage($l);
		$tResult = fwrite($tSocket, $tMsg, $l);
		debugmessage($tResult);

		fclose($tSocket);

		if ($tResult)
			return true;
		else
			return false;*/

	}

}