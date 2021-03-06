<?

namespace Local\Catalog;

use Local\Common\ExtCache;
use Local\Common\Utils;

/**
 * Class Gender Пол
 * @package Local\Catalog
 */
class Gender
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Catalog/Gender/';

	/**
	 * Возвращает все
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

			$iblockId = Utils::getIBlockIdByCode('gender');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array(), array(
				'IBLOCK_ID' => $iblockId,
			), false, false, array(
				'ID', 'NAME', 'CODE',
			));
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$code = trim($item['CODE']);
				$return['ITEMS'][$code] = array(
					'ID' => $id,
					'NAME' => $item['NAME'],
					'CODE' => $code,
				);
				$return['CODES'][$id] = $code;
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает список полов
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
	 * Возвращает код пола по ID
	 * @param $id
	 * @return mixed
	 */
	public static function getCodeById($id) {
		$all = self::getAll();
		return $all['CODES'][$id];
	}

	/**
	 * Возвращает пол по коду
	 * @param $code
	 * @return mixed
	 */
	public static function getByCode($code) {
		$all = self::getAll();
		return $all['ITEMS'][$code];
	}

}