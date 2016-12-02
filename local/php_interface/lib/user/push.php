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

		// TODO:
		return false;

		$cert = $_SERVER['DOCUMENT_ROOT'] . '/push/push.pem';
		$passphrase = 'hishopper';

		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', $cert);
		stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
		$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
		$body['aps'] = array('alert' => $message,'sound' => 'default');
		$payload = json_encode($body);
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
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

		return $result;
	}

}