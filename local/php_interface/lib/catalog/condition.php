<?

namespace Local\Catalog;

use Local\Common\ExtCache;
use Local\Common\Utils;

/**
 * Class Condition Состояния товаров в объявлениях
 * @package Local\Catalog
 */
class Condition
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Catalog/Condition/';

	/**
	 * Возвращает все состояния товара
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

			$iblockId = Utils::getIBlockIdByCode('condition');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array('SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
			), false, false, array(
				'ID', 'NAME', 'ACTIVE',
			));
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$return[$id] = array(
					'ID' => $id,
				    'NAME' => $item['NAME'],
				    'ACTIVE' => $item['ACTIVE'],
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает активные состояния товара
	 * @return array
	 */
	public static function getAppData() {
		$conditions = self::getAll();

		$return = array();
		foreach ($conditions as $item)
			if ($item['ACTIVE'] == 'Y')
				$return[] = array(
					'id' => $item['ID'],
					'name' => $item['NAME'],
				);

		return $return;
	}

	/**
	 * Возвращает состояние по ID
	 * @param $id
	 * @return mixed
	 */
	public static function getById($id) {
		$conditions = self::getAll();
		return $conditions[$id];
	}
}