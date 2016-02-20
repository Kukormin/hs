<?

namespace Local\Catalog;

use Local\Common\ExtCache;
use Local\Common\Utils;

class Brand
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Catalog/Brand/';

	/**
	 * Возвращает все бренды
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

			$iblockId = Utils::getIBlockIdByCode('brand');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array('NAME' => 'ASC'), array(
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
	 * Возвращает активные бренды
	 * @return array
	 */
	public static function getAppData() {
		$brands = self::getAll();

		$return = array();
		foreach ($brands as $item)
			if ($item['ACTIVE'] == 'Y')
				$return[] = array(
					'ID' => $item['ID'],
					'NAME' => $item['NAME'],
				);

		return $return;
	}
}