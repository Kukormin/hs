<?

namespace Local\Data;

use Local\Common\ExtCache;
use Local\Common\Utils;

/**
 * Class Options Настройки
 * @package Local\Data
 */
class Options
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/Options/';

	/**
	 * Возвращает настройки сайта
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

			$iblockId = Utils::getIBlockIdByCode('options');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array(), array(
				'IBLOCK_ID' => $iblockId,
			), false, false, array(
				'ID', 'CODE', 'XML_ID', 'PREVIEW_TEXT',
			));
			while ($item = $rsItems->Fetch())
			{
				if ($item['PREVIEW_TEXT'])
					$value = $item['PREVIEW_TEXT'];
				else
					$value = intval($item['XML_ID']);
				$return[$item['CODE']] = $value;
			}


			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает настройки
	 * @return array
	 */
	public static function getAppData() {
		$all = self::getAll();

		$return = array();
		foreach ($all as $code => $value)
			$return[] = array(
				'code' => $code,
				'value' => $value,
			);

		return $return;
	}

	/**
	 * Возвращает настройку по коду
	 * @param $code
	 * @return mixed
	 */
	public static function get($code) {
		$all = self::getAll();
		return $all[$code];
	}
}