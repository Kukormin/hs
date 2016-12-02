<?

namespace Local\Data;

use Local\Catalog\Delivery;
use Local\Catalog\Payment;
use Local\Catalog\Catalog;
use Local\Catalog\Brand;
use Local\Catalog\Condition;
use Local\Catalog\Color;
use Local\Catalog\Size;
use Local\Common\ExtCache;
use Local\Common\Utils;
use Local\Api\ApiException;
use Local\User\Auth;
use Local\User\Follower;
use Local\User\User;

/**
 * Class News Новости
 * @package Local\Data
 */
class News
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/News/';

	/**
	 * Количество новостей за раз
	 */
	const DEFAULT_COUNT = 10;

	/**
	 * Возвращает все типы новостей
	 * @param bool $refreshCache для принудительного сброса кеша
	 * @return array|mixed
	 */
	private static function getTypes($refreshCache = false) {
		$return = array();

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('news');

			$enum = new \CIBlockPropertyEnum();
			$rsItems = $enum->GetList(array('SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
				'CODE' => 'TYPE',
			));
			while ($item = $rsItems->Fetch()) {
				$return['ITEMS'][$item['XML_ID']] = array(
					'ID' => $item['ID'],
					'XML_ID' => $item['XML_ID'],
					'VALUE' => $item['VALUE'],
				);
				$return['CODES'][$item['ID']] = $item['XML_ID'];
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает тип новости по коду
	 * @param $code
	 * @return mixed
	 */
	private static function getTypeByCode($code) {
		$all = self::getTypes(true);
		return $all['ITEMS'][$code];
	}


	/**
	 * Возвращает код типа новости по ID
	 * @param $id
	 * @return mixed
	 */
	private static function getTypeCodeById($id) {
		$all = self::getTypes();
		return $all['CODES'][$id];
	}

	/**
	 * Добавляет новость
	 * @param $parentId
	 * @param $typeCode
	 * @param $name
	 * @param $userId
	 * @param $adId
	 * @return int
	 * @throws ApiException
	 */
	private static function add($parentId, $typeCode, $name, $userId, $adId) {
		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('news');
		$type = self::getTypeByCode($typeCode);
		$id = $iblockElement->Add(array(
			'IBLOCK_ID' => $iblockId,
			'NAME' => $name,
		    'CODE' => $parentId,
		    'PROPERTY_VALUES' => array(
			    'TYPE' => $type['ID'],
			    'USER' => $userId,
			    'AD' => $adId,
		    ),
		));
		if (!$id)
			throw new ApiException(['feed_add_error'], 500, $iblockElement->LAST_ERROR);

		// сбрасываем кеш
		self::clearUserCache($parentId);

		return $id;
	}

	/**
	 * Добавляет новость о том, что пользователь подписался на вас
	 * @param $parentId
	 * @param $userId
	 * @return int
	 * @throws ApiException
	 */
	public static function follow($parentId, $userId) {
		$name = 'Пользователь подписался на вас';
		return self::add($parentId, 'follow', $name, $userId, 0);
	}

	/**
	 * Добавляет новость о том, что пользователь отписался от вас
	 * @param $parentId
	 * @param $userId
	 * @return int
	 * @throws ApiException
	 */
	public static function unFollow($parentId, $userId) {
		$name = 'Пользователь отписался от вас';
		return self::add($parentId, 'unfollow', $name, $userId, 0);
	}

	/**
	 * Добавляет новость о добавлении нового комментария к вашему объявлению
	 * @param $name
	 * @param $userId
	 * @param $adId
	 * @return int
	 * @throws ApiException
	 */
	public static function comment($name, $userId, $adId) {
		$ad = Ad::getById($adId);
		$parentId = $ad['USER'];
		return self::add($parentId, 'comment', $name, $userId, $adId);
	}

	/**
	 * Добавляет новость о добавлении вашего объявления в избранное
	 * @param $userId
	 * @param $adId
	 * @return int
	 * @throws ApiException
	 */
	public static function addToFavorite($userId, $adId) {
		$name = 'Пользователь добавил ваше объявление в избранное';
		$ad = Ad::getById($adId);
		$parentId = $ad['USER'];
		return self::add($parentId, 'add_favorite', $name, $userId, $adId);
	}

	/**
	 * Добавляет новость об удалении вашего объявления из избранного
	 * @param $userId
	 * @param $adId
	 * @return int
	 * @throws ApiException
	 */
	public static function remoteFromFavorite($userId, $adId) {
		$name = 'Пользователь удалил ваше объявление из избранного';
		$ad = Ad::getById($adId);
		$parentId = $ad['USER'];
		return self::add($parentId, 'remove_favorite', $name, $userId, $adId);
	}

	/**
	 * Добавляет новость о том, что пользователь поделился вашим объявлением
	 * @param $name
	 * @param $userId
	 * @param $adId
	 * @return int
	 * @throws ApiException
	 */
	public static function share($name, $userId, $adId) {
		$ad = Ad::getById($adId);
		$parentId = $ad['USER'];

		$res = self::add($parentId, 'share', $name, $userId, $adId);
		self::getShareCount($parentId, true);

		return $res;
	}

	/**
	 * Возвращает новости пользователя
	 * @param $userId
	 * @param array $params параметры постраничной навигации
	 * @param bool $refreshCache
	 * @return array|mixed
	 */
	public static function getList($userId, $params = array(), $refreshCache = false)
	{
		$userId = intval($userId);
		$return = array();

		$filter = array(
			'=CODE' => $userId,
		);
		$count = self::DEFAULT_COUNT;

		if (intval($params['max']) > 0)
			$filter['<ID'] = intval($params['max']);
		if (intval($params['count']) > 0)
			$count = intval($params['count']);

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$filter,
			    $count,
			),
			static::CACHE_PATH . __FUNCTION__ . '/' . $userId . '/',
			7200,
			false
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('news');
			$filter['IBLOCK_ID'] = $iblockId;

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(
				array('ID' => 'DESC'),
				$filter,
				false,
				array('nTopCount' => $count),
				array(
					'ID', 'CODE', 'NAME',
				    'PROPERTY_TYPE',
				    'PROPERTY_USER',
				    'PROPERTY_AD',
				)
			);
			while ($item = $rsItems->Fetch())
			{
				$type = self::getTypeCodeById($item['PROPERTY_TYPE_ENUM_ID']);
				$name = $item['NAME'];
				if ($type == 'follow' || $type == 'unfollow' ||
					$type == 'add_favorite' || $type == 'remove_favorite')
					$name = '';
				$return[] = array(
					'id' => intval($item['ID']),
					'name' => $name,
					'type' => $type,
					'ad' => intval($item['PROPERTY_AD_VALUE']),
					'user' => intval($item['PROPERTY_USER_VALUE']),
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает новости с заполненными данными по объявлениям и пользователям
	 * @param $params
	 * @return array
	 */
	public static function getAppData($params)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		$return = array();

		$items = self::getList($userId, $params);
		foreach ($items as $item)
		{
			$res = $item;

			if ($item['ad'])
				$res['ad'] = Ad::shortById($item['ad']);
			if ($item['user'])
				$res['user'] = User::publicProfile($item['user']);

			$return[] = $res;
		}

		return $return;
	}

	/**
	 * Возвращает количество постов, о том, что объявлением пользователя поделились
	 * @param $userId
	 * @param bool $refreshCache
	 * @return int|mixed
	 */
	public static function getShareCount($userId, $refreshCache = false)
	{
		$userId = intval($userId);

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$userId,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			7200,
			false
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('news');
			$type = self::getTypeByCode('share');
			$iblockElement = new \CIBlockElement();
			$count = $iblockElement->GetList(
				array(),
				array(
					'IBLOCK_ID' => $iblockId,
				    '=CODE' => $userId,
				    '=PROPERTY_TYPE' => $type['ID'],
				),
				array(),
				false
			);
			$return = intval($count);

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Очищает кеш новостей пользователя
	 * @param $userId
	 */
	private static function clearUserCache($userId)
	{
		$phpCache = new \CPHPCache();
		$phpCache->CleanDir(static::CACHE_PATH . 'getList/' . $userId);
	}
}