<?

namespace Local\Data;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Local\Api\ApiException;
use Local\Common\ExtCache;
use Local\Data\News;

/**
 * Class History История сделок
 * @package Local\Data
 */
class History
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/History/';

	/**
	 * ID сущности
	 */
	const ENTITY_ID = 3;

	/**
	 * Получение истории сделки
	 * @param $dealId
	 * @param bool $refreshCache
	 * @return array|mixed
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function get($dealId, $refreshCache = false) {
		$dealId = intval($dealId);

		$return = array();
		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$dealId,
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
			$query->setFilter(array('UF_DEAL' => $dealId));
			$query->setSelect(array('UF_TO', 'UF_DATE', 'UF_USER'));
			$rsItems = $query->exec();
			while ($item = $rsItems->Fetch())
				$return[] = array(
					'status' => intval($item['UF_TO']),
					'date' => date('c', $item['UF_DATE']),
					'user' => intval($item['UF_USER']),
				);

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Добавление записи в историю
	 * @param $dealId
	 * @param $to
	 * @param $userId
	 * @return int
	 * @throws ApiException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	public static function add($dealId, $to, $userId) {
		$dealId = intval($dealId);
		$to = intval($to);
		$userId = intval($userId);

		Loader::includeModule('highloadblock');

		$entityInfo = HighloadBlockTable::getById(static::ENTITY_ID)->Fetch();
		$entity = HighloadBlockTable::compileEntity($entityInfo);
		$dataClass = $entity->getDataClass();
		$result = $dataClass::add(array(
			'UF_DEAL' => $dealId,
			'UF_TO' => $to,
			'UF_USER' => $userId,
			'UF_DATE' => time(),
		));
		if (!$result->isSuccess())
			throw new ApiException(['add_history_error'], 500, $result->getErrorMessages());

		self::get($dealId, true);

		return $result->getId();
	}

}