<?

namespace Local\Catalog;

use Local\Common\ExtCache;
use Local\Common\Utils;

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

		$iblockId = Utils::getIBlockIdByCode('delivery');

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

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array('SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
			), false, false, array(
				'ID', 'NAME', 'ACTIVE', 'CODE', 'XML_ID',
			));
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$return[$item['CODE']] = array(
					'ID' => $id,
				    'NAME' => $item['NAME'],
				    'ACTIVE' => $item['ACTIVE'],
				    'CODE' => $item['CODE'],
				    'PRICE' => $item['XML_ID'],
				);
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
		$items = self::getAll();

		$return = array();
		foreach ($items as $item)
			if ($item['ACTIVE'] == 'Y')
				$return[] = array(
					'CODE' => $item['CODE'],
					'NAME' => $item['NAME'],
					'PRICE' => $item['PRICE'],
				);

		return $return;
	}

	/**
	 * Возвращает способ отправки по коду
	 * @param $code
	 * @return mixed
	 */
	public static function getByCode($code) {
		$items = self::getAll();
		return $items[$code];
	}
}