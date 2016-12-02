<?

namespace Local\Catalog;

use Local\Common\ExtCache;
use Local\Common\Utils;

/**
 * Class Color Цвета
 * @package Local\Catalog
 */
class Color
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Catalog/Color/';

	/**
	 * Возвращает все цвета
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

			$iblockId = Utils::getIBlockIdByCode('color');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array('SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
			), false, false, array(
				'ID', 'NAME', 'ACTIVE', 'XML_ID', 'CODE',
			));
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$return[$id] = array(
					'ID' => $id,
				    'NAME' => $item['NAME'],
				    'ACTIVE' => $item['ACTIVE'],
				    'XML_ID' => $item['XML_ID'],
				    'CODE' => $item['CODE'],
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает активные цвета
	 * @return array
	 */
	public static function getAppData() {
		$colors = self::getAll();

		$return = array();
		foreach ($colors as $item)
			if ($item['ACTIVE'] == 'Y')
				$return[] = array(
					'id' => $item['ID'],
					'ru' => $item['NAME'],
					'en' => $item['CODE'],
					'hex' => $item['XML_ID'],
				);

		return $return;
	}

	/**
	 * Возвращает цвет по ID
	 * @param $id
	 * @return mixed
	 */
	public static function getById($id) {
		$colors = self::getAll();
		return $colors[$id];
	}
}