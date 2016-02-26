<?

namespace Local\User;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Local\Api\ApiException;
use Local\Common\ExtCache;

class Follower
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/User/Follower/';

	/**
	 * ID сущности подписчиков
	 */
	const ENTITTY_ID = 1;

	/**
	 * Получение подписок и подписчиков для пользователя
	 * @param $userId
	 * @param bool $refreshCache
	 * @return array|mixed
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function get($userId, $refreshCache = false) {
		$userId = intval($userId);

		$return = array(
			'followers' => array(),
			'publishers' => array(),
		);
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

			$entityInfo = HighloadBlockTable::getById(static::ENTITTY_ID)->Fetch();
			$entity = HighloadBlockTable::compileEntity($entityInfo);

			$query = new Query($entity);
			$query->setFilter(array('UF_PUBLISHER' => $userId));
			$query->setSelect(array('UF_FOLLOWER'));
			$rsItems = $query->exec();
			while ($item = $rsItems->Fetch())
				$return['followers'][] = intval($item['UF_FOLLOWER']);

			$query = new Query($entity);
			$query->setFilter(array('UF_FOLLOWER' => $userId));
			$query->setSelect(array('UF_PUBLISHER'));
			$rsItems = $query->exec();
			while ($item = $rsItems->Fetch())
				$return['publishers'][] = intval($item['UF_PUBLISHER']);

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Добавление подписчика
	 * @param $follower
	 * @param $publisher
	 * @return int
	 * @throws ApiException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	public static function add($follower, $publisher) {
		$follower = intval($follower);
		$publisher = intval($publisher);

		Loader::includeModule('highloadblock');

		$entityInfo = HighloadBlockTable::getById(static::ENTITTY_ID)->Fetch();
		$entity = HighloadBlockTable::compileEntity($entityInfo);

		$query = new Query($entity);
		$query->setFilter(array(
			'UF_FOLLOWER' => $follower,
			'UF_PUBLISHER' => $publisher,
		));
		$query->setSelect(array('ID'));
		$rsItems = $query->exec();
		if ($item = $rsItems->Fetch())
			throw new ApiException(['already_followed'], 400);

		$dataClass = $entity->getDataClass();
		$result = $dataClass::add(array(
			'UF_FOLLOWER' => $follower,
			'UF_PUBLISHER' => $publisher,
		));
		if (!$result->isSuccess())
			throw new ApiException(['add_follower_error'], 500, $result->getErrorMessages());

		self::get($follower, true);
		self::get($publisher, true);

		return $result->getId();
	}

	/**
	 * Удаление подписчика
	 * @param $follower
	 * @param $publisher
	 * @return bool
	 * @throws ApiException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	public static function delete($follower, $publisher) {
		$follower = intval($follower);
		$publisher = intval($publisher);

		Loader::includeModule('highloadblock');

		$entityInfo = HighloadBlockTable::getById(static::ENTITTY_ID)->Fetch();
		$entity = HighloadBlockTable::compileEntity($entityInfo);

		$query = new Query($entity);
		$query->setFilter(array(
			'UF_FOLLOWER' => $follower,
			'UF_PUBLISHER' => $publisher,
		));
		$query->setSelect(array('ID'));
		$rsItems = $query->exec();
		if ($item = $rsItems->Fetch())
		{
			$dataClass = $entity->getDataClass();
			$result = $dataClass::delete($item['ID']);
			if (!$result->isSuccess())
				throw new ApiException(['delete_follower_error'], 500, $result->getErrorMessages());

			self::get($follower, true);
			self::get($publisher, true);
		}
		else
			throw new ApiException(['not_exists'], 400);

		return true;
	}

}