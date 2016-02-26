<?

namespace Local\User;

use Local\Api\ApiException;
use Local\Common\ExtCache;
use Local\Common\Utils;

class Session
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/User/Session/';

	/**
	 * Соль для генерации токенов
	 */
	const SALT = 's0fgs6d0f6h';

	/**
	 * Создает сессию для указанного пользователя
	 * @param $userId
	 * @param $device
	 * @return string
	 * @throws ApiException
	 */
	public static function create($userId, $device) {
		$deviceToken = trim($device['uuid']);
		if (strlen($deviceToken) == 0)
			throw new ApiException(['empty_device_uuid'], 400);

		$authToken = md5($userId . '|' . $deviceToken . '|' . static::SALT);
		$session = self::getByToken($authToken);
		// Если не найден, то пробуем создать
		if (!$session)
			if (self::add($authToken, $userId, $device))
				// Если пользователь создан, получаем все его поля, заодно обновляя кеш
				$session = self::getByToken($authToken, true);

		if (!$session)
			throw new ApiException(['session_add_error'], 500);

		return $authToken;
	}

	/**
	 * Получает сессию по токену авторизации
	 * @param $authToken
	 * @param bool $refreshCache
	 * @return array|mixed
	 */
	public static function getByToken($authToken, $refreshCache = false) {
		$return = array();
		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$authToken,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20,
			false // не используем теговый кеш, чтоб не удалять кеш при измененях
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('session');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array(), array(
				'IBLOCK_ID' => $iblockId,
				'NAME' => $authToken,
			), false, false, array(
				'ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'XML_ID',
			));
			if ($item = $rsItems->Fetch())
			{
				$return = array(
					'ID' => intval($item['ID']),
					'NAME' => $item['NAME'],
					'CODE' => $item['CODE'],
					'SIZE' => $item['PREVIEW_TEXT'],
					'USER_ID' => intval($item['XML_ID']),
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Добавляет элемент сессии
	 * @param $authToken
	 * @param $userId
	 * @param $device
	 * @return bool
	 * @throws ApiException
	 */
	private static function add($authToken, $userId, $device) {
		$iblockElement = new \CIBlockElement();

		$iblockId = Utils::getIBlockIdByCode('session');
		$id = $iblockElement->Add(array(
			'IBLOCK_ID' => $iblockId,
			'NAME' => $authToken,
			'CODE' => $device['uuid'],
		    'PREVIEW_TEXT' => $device['x'] . 'x' . $device['y'],
		    'XML_ID' => $userId,
		));
		if (!$id)
			throw new ApiException(['session_add_error'], 500, $iblockElement->LAST_ERROR);

		return $id;
	}

	/**
	 * Удаляет все сессии пользователя
	 * @param $userId
	 */
	public static function deleteByUserId($userId)
	{
		$iblockId = Utils::getIBlockIdByCode('session');

		$iblockElement = new \CIBlockElement();
		$rsItems = $iblockElement->GetList(array(), array(
			'IBLOCK_ID' => $iblockId,
			'XML_ID' => $userId,
		), false, false, array(
			'ID',
		));
		while ($item = $rsItems->Fetch())
			$iblockElement->Delete($item['ID']);
	}

}