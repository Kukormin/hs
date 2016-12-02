<?

namespace Local\Data;

use Local\Common\ExtCache;
use Local\Common\Utils;

/**
 * Class Status Статусы сделок
 * @package Local\Data
 */
class Status
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/Status/';

	/**
	 * Возвращает все статусы сделки
	 * @param bool $refreshCache для принудительного сброса кеша
	 * @return array|mixed
	 */
	public static function getAll($refreshCache = false) {
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

			$iblockId = Utils::getIBlockIdByCode('statuses');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array('SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
			), false, false, array(
				'ID', 'NAME', 'CODE',
			    'PROPERTY_SELLER_PUSH',
			    'PROPERTY_BUYER_PUSH',
			));
			while ($item = $rsItems->Fetch())
			{
				$return['ITEMS'][$item['CODE']] = array(
					'ID' => intval($item['ID']),
					'CODE' => $item['CODE'],
					'NAME' => $item['NAME'],
				    'SELLER' => $item['PROPERTY_SELLER_PUSH_VALUE'],
				    'BUYER' => $item['PROPERTY_BUYER_PUSH_VALUE'],
				);
				$return['CODES'][$item['ID']] = $item['CODE'];
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает статусы
	 * @return array
	 */
	public static function getAppData() {
		$all = self::getAll();

		$return = array();
		foreach ($all['ITEMS'] as $item)
			$return[] = array(
				'code' => $item['CODE'],
				'name' => $item['NAME'],
			);

		return $return;
	}

	/**
	 * Возвращает статус по коду
	 * @param $code
	 * @return mixed
	 */
	public static function getByCode($code) {
		$all = self::getAll();
		return $all['ITEMS'][$code];
	}


	/**
	 * Возвращает код статуса по ID
	 * @param $id
	 * @return mixed
	 */
	public static function getCodeById($id) {
		$all = self::getAll();
		return $all['CODES'][$id];
	}
}