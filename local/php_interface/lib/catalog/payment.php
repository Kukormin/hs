<?

namespace Local\Catalog;

use Local\Common\ExtCache;
use Local\Common\Utils;

class Payment
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Catalog/Payment/';

	/**
	 * Возвращает все способы оплаты
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

			$iblockId = Utils::getIBlockIdByCode('ad');

			$enum = new \CIBlockPropertyEnum();
			$rsItems = $enum->GetList(array(), array(
				'IBLOCK_ID' => $iblockId,
				'CODE' => 'PAYMENT',
			));
			while ($item = $rsItems->Fetch()) {
				$return[$item['XML_ID']] = array(
					'ID' => $item['ID'],
					'XML_ID' => $item['XML_ID'],
					'VALUE' => $item['VALUE'],
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
		$payments = self::getAll();

		$return = array();
		foreach ($payments as $item)
			$return[] = array(
				'CODE' => $item['XML_ID'],
				'NAME' => $item['VALUE'],
			);

		return $return;
	}
}