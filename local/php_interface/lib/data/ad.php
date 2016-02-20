<?

namespace Local\Data;

use Local\Common\ExtCache;
use Local\Common\Utils;

class Ad
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/Ad/';

	public static function add() {
		$session = User::checkAuth();
		$userId = $session['USER_ID'];

		return $userId;
	}
}