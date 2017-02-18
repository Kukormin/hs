<?

namespace Local\Chat;

/**
 * Class SocketChat поддержка чата по сокетам
 * @package Local\Chat
 */
class SocketChat
{
	/**
	 * @var array Соединения для пользователей
	 */
	public static $CBU = array();

	/**
	 * @var array Пользователь соединения
	 */
	public static $UBC = array();

	public static function addConnection($userId, $connection) {
		self::$CBU[$userId][$connection->id] = $connection;
		self::$UBC[$connection->id] = $userId;
	}

	public static function closeConnection($connection) {
		$userId = 0;
		if (isset(self::$UBC[$connection->id]))
			$userId = self::$UBC[$connection->id];
		if (isset(self::$UBC[$connection->id]))
			unset(self::$UBC[$connection->id]);
		if (isset(self::$CBU[$userId][$connection->id]))
		{
			unset(self::$CBU[$userId][$connection->id]);
			if (!count(self::$CBU[$userId]))
				unset(self::$CBU[$userId]);
		}
	}

	public static function getUser($connection) {
		if (isset(self::$UBC[$connection->id]))
			return self::$UBC[$connection->id];
		else
			return false;
	}

	public static function getConnections($userId) {
		if (isset(self::$CBU[$userId]))
			return self::$CBU[$userId];
		else
			return array();
	}
}