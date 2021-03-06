<?

namespace Local\Catalog;

use Local\Common\ExtCache;
use Local\Common\Utils;

/**
 * Class Delivery Способы отправки
 * @package Local\Catalog
 */
class Delivery
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Catalog/Delivery/';

	/**
	 * Возвращает все способы отправки
	 * (учитывает теговый кеш)
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

			$iblockId = Utils::getIBlockIdByCode('delivery');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array('SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
			), false, false, array(
				'ID', 'NAME', 'ACTIVE', 'CODE', 'XML_ID',
			    'PROPERTY_SHOW_ADDRESS',
			));
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$return['ITEMS'][$item['CODE']] = array(
					'ID' => $id,
				    'NAME' => $item['NAME'],
				    'ACTIVE' => $item['ACTIVE'],
				    'CODE' => $item['CODE'],
				    'PRICE' => $item['XML_ID'],
				    'SHOW_ADDRESS' => $item['PROPERTY_SHOW_ADDRESS_VALUE'] == 'Y',
				);
				$return['CODES'][$id] = $item['CODE'];
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает активные способы отправки
	 * @return array
	 */
	public static function getAppData() {
		$all = self::getAll();

		$return = array();
		foreach ($all['ITEMS'] as $item)
			if ($item['ACTIVE'] == 'Y')
				$return[] = array(
					'code' => $item['CODE'],
					'name' => $item['NAME'],
					'price' => $item['PRICE'],
					'showAddress' => $item['SHOW_ADDRESS'],
				);

		return $return;
	}

	/**
	 * Возвращает способ отправки по коду
	 * @param $code
	 * @return mixed
	 */
	public static function getByCode($code) {
		$all = self::getAll();
		return $all['ITEMS'][$code];
	}

	/**
	 * Возвращает код способа отправки по ID
	 * @param $id
	 * @return mixed
	 */
	public static function getCodeById($id) {
		$all = self::getAll();
		return $all['CODES'][$id];
	}

	/**
	 * Форматирует массив доставок
	 * @param $ar
	 * @return array
	 */
	public static function format($ar) {
		$return = array();
		foreach ($ar as $id)
		{
			$code = self::getCodeById($id);
			$return[] = $code;
		}
		return $return;
	}
}