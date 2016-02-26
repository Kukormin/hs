<?

namespace Local\Catalog;

use Local\Common\ExtCache;
use Local\Common\Utils;

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

			$iblockId = Utils::getIBlockIdByCode('user');

			$enum = new \CIBlockPropertyEnum();
			$rsItems = $enum->GetList(array(), array(
				'IBLOCK_ID' => $iblockId,
				'CODE' => 'GENDER',
			));
			while ($item = $rsItems->Fetch()) {
				$return['ITEMS'][$item['XML_ID']] = array(
					'ID' => $item['ID'],
					'XML_ID' => $item['XML_ID'],
					'VALUE' => $item['VALUE'],
				);
				$return['CODES'][$item['ID']] = $item['XML_ID'];
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
				'CODE' => $item['XML_ID'],
				'NAME' => $item['VALUE'],
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