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

		$tHost = 'gateway.sandbox.push.apple.com';
		$tPort = 2195;
		$tCert = $_SERVER['DOCUMENT_ROOT'] . '/push/hishopper.pem';
		$tPassphrase = 'test123';
		$tToken = $deviceToken;
		$tBadge = 1;
		$tSound = 'default';
		$tPayload = 'APNS Message';
		$tBody['aps'] = array(
			'alert' => $message,
			'badge' => $tBadge,
			'sound' => $tSound,
		);
		//$tBody['payload'] = $tPayload;
		$tBody = json_encode($tBody);
		$tContext = stream_context_create();
		stream_context_set_option($tContext, 'ssl', 'local_cert', $tCert);
		stream_context_set_option($tContext, 'ssl', 'passphrase', $tPassphrase);
		$tSocket = stream_socket_client('ssl://' . $tHost . ':' . $tPort, $error, $errstr, 30, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $tContext);
		if (!$tSocket)
			return false;

		$tMsg = chr(0) . chr(0) . chr(32) . pack('H*', $tToken) . pack('n', strlen($tBody)) . $tBody;
		$tResult = fwrite($tSocket, $tMsg, strlen($tMsg));

		fclose($tSocket);

		if ($tResult)
			return true;
		else
			return false;

	}

	public static function testMessage($deviceToken, $message)
	{
		if (!$deviceToken || !$message)
			return false;

		// TODO:
		//return false;

		/*$cert = $_SERVER['DOCUMENT_ROOT'] . '/push/hishopper.pem';
		$passphrase = 'test123';

		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', $cert);
		//stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
		$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
		$body['aps'] = array('alert' => $message,'sound' => 'default');
		$payload = json_encode($body);
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
		debugmessage($msg);
		$result = fwrite($fp, $msg, strlen($msg));
		debugmessage($result);
		$i = 0;
		while (!feof($fp)) {
			$s = fgets($fp, 1024);
			debugmessage($s);
			$i++;
			if ($i > 5)
				break;
		}
		fclose($fp);

		debugmessage($result);
		return $result;*/


		$tHost = 'gateway.sandbox.push.apple.com';
		$tPort = 2195;
		$tCert = $_SERVER['DOCUMENT_ROOT'] . '/push/hishopper.pem';
		$tPassphrase = 'test123';
		$tToken = $deviceToken;
		$tAlert = 'wopassword';
		$tBadge = 1;
		$tSound = 'default';
		$tPayload = 'APNS Message';
		$tBody['aps'] = array(
			'alert' => $tAlert,
			'badge' => $tBadge,
			'sound' => $tSound,
		);
		//$tBody['payload'] = $tPayload;
		debugmessage($tBody);
		$tBody = json_encode($tBody);
		$tContext = stream_context_create();
		stream_context_set_option($tContext, 'ssl', 'local_cert', $tCert);
		//stream_context_set_option($tContext, 'ssl', 'passphrase', $tPassphrase);
		$tSocket = stream_socket_client('ssl://' . $tHost . ':' . $tPort, $error, $errstr, 30, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $tContext);
		if (!$tSocket)
			return false;

		$tMsg = chr(0) . chr(0) . chr(32) . pack('H*', $tToken) . pack('n', strlen($tBody)) . $tBody;
		debugmessage($tMsg);
		$tResult = fwrite($tSocket, $tMsg, strlen($tMsg));
		debugmessage($tResult);

		fclose($tSocket);

		if ($tResult)
			return true;
		else
			return false;

	}

}