<?

namespace Chat;

/**
 * Class SocketChat поддержка чата по сокетам
 * @package Local\Chat
 */
class SocketChat
{
	/**
	 * @var array Соединения для пользователей
	 */
	public static $CBU = [];

	/**
	 * @var array Пользователь соединения
	 */
	public static $UBC = [];

	public static function addConnection($user, $connection)
	{
		$userId = $user['id'];
		self::$CBU[$userId][$connection->id] = $connection;
		self::$UBC[$connection->id] = $user;
	}

	public static function getUser()
	{
		$return = [];

		$authToken = '';
		if (isset($_SERVER['HTTP_X_AUTH']))
			$authToken = $_SERVER['HTTP_X_AUTH'];

		if ($authToken)
		{
			$http = new \Http([
				'auth' => $authToken,
			]);
			$res = $http->get(API . '/user/profile');

			if ($res['result'])
				$return = [
					'id' => $res['result']['id'],
					'name' => $res['result']['name'],
					'nickname' => $res['result']['nickname'],
					'auth' => $authToken,
				];
		}

		return $return;
	}

	public static function closeConnection($connection)
	{
		$userId = 0;
		if (isset(self::$UBC[$connection->id]))
		{
			$user = self::$UBC[$connection->id];
			$userId = $user['id'];
		}
		if (isset(self::$UBC[$connection->id]))
			unset(self::$UBC[$connection->id]);
		if (isset(self::$CBU[$userId]) && isset(self::$CBU[$userId][$connection->id]))
			{
				unset(self::$CBU[$userId][$connection->id]);
				if (!count(self::$CBU[$userId]))
					unset(self::$CBU[$userId]);
			}
	}

	public static function getUserData($connection)
	{
		if (isset(self::$UBC[$connection->id]))
			return self::$UBC[$connection->id];
		else
			return false;
	}

	public static function getConnections($userId)
	{
		if (isset(self::$CBU[$userId]))
			return self::$CBU[$userId];
		else
			return [];
	}
}