<?

namespace Local\User;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Local\Api\ApiException;
use Local\Common\ExtCache;
use Local\Data\News;

/**
 * Class Favorite Избранное пользователя
 * @package Local\User
 */
class Favorite
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/User/Favorite/';

	/**
	 * ID сущности подписчиков
	 */
	const ENTITY_ID = 2;

	/**
	 * Количество постов за раз
	 */
	const DEFAULT_COUNT = 10;

	/**
	 * Возвращает количество избранных объявлений у пользователя
	 * @param $userId
	 * @param bool $refreshCache
	 * @return array|mixed
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getCountByUser($userId, $refreshCache = false) {
		$userId = intval($userId);

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$userId,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			Loader::includeModule('highloadblock');

			$entityInfo = HighloadBlockTable::getById(static::ENTITY_ID)->Fetch();
			$entity = HighloadBlockTable::compileEntity($entityInfo);

			$query = new Query($entity);
			$query->setFilter(array('UF_USER' => $userId));
			$query->countTotal(true);
			$rsItems = $query->exec();
			$return = $rsItems->getCount();

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает количество пользователей, которые добавили объявление в избранное
	 * @param $adId
	 * @param bool $refreshCache
	 * @return int|mixed
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getCountByAd($adId, $refreshCache = false) {
		$adId = intval($adId);

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$adId,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			Loader::includeModule('highloadblock');

			$entityInfo = HighloadBlockTable::getById(static::ENTITY_ID)->Fetch();
			$entity = HighloadBlockTable::compileEntity($entityInfo);

			$query = new Query($entity);
			$query->setFilter(array('UF_AD' => $adId));
			$query->countTotal(true);
			$rsItems = $query->exec();
			$return = $rsItems->getCount();

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Проверяет, находится ли данное объявление в избранном у пользователя
	 * @param $user
	 * @param $ad
	 * @param bool $refreshCache
	 * @return int|mixed
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function check($user, $ad, $refreshCache = false) {
		$user = intval($user);
		$ad = intval($ad);

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$user,
				$ad,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			Loader::includeModule('highloadblock');

			$entityInfo = HighloadBlockTable::getById(static::ENTITY_ID)->Fetch();
			$entity = HighloadBlockTable::compileEntity($entityInfo);

			$query = new Query($entity);
			$query->setFilter(array(
				'UF_USER' => $user,
				'UF_AD' => $ad,
			));
			$query->countTotal(true);
			$rsItems = $query->exec();
			$return = $rsItems->getCount() > 0;

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Добавление объявления в избранное
	 * @param $user
	 * @param $ad
	 * @return int
	 * @throws ApiException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	public static function add($user, $ad) {
		$user = intval($user);
		$ad = intval($ad);

		Loader::includeModule('highloadblock');

		$entityInfo = HighloadBlockTable::getById(static::ENTITY_ID)->Fetch();
		$entity = HighloadBlockTable::compileEntity($entityInfo);

		$query = new Query($entity);
		$query->setFilter(array(
			'UF_USER' => $user,
			'UF_AD' => $ad,
		));
		$query->setSelect(array('ID'));
		$rsItems = $query->exec();
		if ($item = $rsItems->Fetch())
			throw new ApiException(['already_favorite'], 400);

		$dataClass = $entity->getDataClass();
		$result = $dataClass::add(array(
			'UF_USER' => $user,
			'UF_AD' => $ad,
		));
		if (!$result->isSuccess())
			throw new ApiException(['add_favorite_error'], 500, $result->getErrorMessages());

		// обновляем кеш
		self::getCountByUser($user, true);
		self::getCountByAd($ad, true);
		self::check($user, $ad, true);
		self::clearUserCache($user);

		News::addToFavorite($user, $ad);

		return $result->getId();
	}

	/**
	 * Удаление объявления
	 * @param $user
	 * @param $ad
	 * @return bool
	 * @throws ApiException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	public static function remove($user, $ad) {
		$user = intval($user);
		$ad = intval($ad);

		Loader::includeModule('highloadblock');

		$entityInfo = HighloadBlockTable::getById(static::ENTITY_ID)->Fetch();
		$entity = HighloadBlockTable::compileEntity($entityInfo);

		$query = new Query($entity);
		$query->setFilter(array(
			'UF_USER' => $user,
			'UF_AD' => $ad,
		));
		$query->setSelect(array('ID'));
		$rsItems = $query->exec();
		if ($item = $rsItems->Fetch())
		{
			$dataClass = $entity->getDataClass();
			$result = $dataClass::delete($item['ID']);
			if (!$result->isSuccess())
				throw new ApiException(['delete_favorite_error'], 500, $result->getErrorMessages());

			self::getCountByUser($user, true);
		}
		else
			throw new ApiException(['not_in_favorites'], 400);

		// обновляем кеш
		self::getCountByUser($user, true);
		self::getCountByAd($ad, true);
		self::check($user, $ad, true);
		self::clearUserCache($user);

		News::remoteFromFavorite($user, $ad);

		return true;
	}

	/**
	 * Возвращает избранное пользователя
	 * @param $userId
	 * @param array $params параметры постраничной навигации
	 * @param bool $refreshCache
	 * @return array|mixed
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getList($userId, $params = array(), $refreshCache = false)
	{
		$return = array();

		$filter = array(
			'UF_USER' => $userId,
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
			7200
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			Loader::includeModule('highloadblock');

			$entityInfo = HighloadBlockTable::getById(static::ENTITY_ID)->Fetch();
			$entity = HighloadBlockTable::compileEntity($entityInfo);

			$query = new Query($entity);
			$query->setFilter($filter);
			$query->setSelect(array('ID', 'UF_AD'));
			$query->setLimit($count);
			$rsItems = $query->exec();
			while ($item = $rsItems->Fetch())
				$return[] = array(
					'ID' => intval($item['ID']),
					'AD' => intval($item['UF_AD']),
				);

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Очищает кеш избранного для пользователя
	 * @param $userId
	 */
	private static function clearUserCache($userId)
	{
		$phpCache = new \CPHPCache();
		$phpCache->CleanDir(static::CACHE_PATH . 'getList/' . $userId);
	}

}