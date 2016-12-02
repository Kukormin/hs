<?

namespace Local\Catalog;

use Local\Common\ExtCache;
use Local\Common\Utils;

/**
 * Class Payment Способы оплаты
 * @package Local\Catalog
 */
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

			$iblockId = Utils::getIBlockIdByCode('payment');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array('SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
			), false, false, array(
				'ID', 'NAME', 'CODE',
			));
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$return['ITEMS'][$item['CODE']] = array(
					'ID' => $id,
					'NAME' => $item['NAME'],
					'CODE' => $item['CODE'],
				);
				$return['CODES'][$id] = $item['CODE'];
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает активные способы оплаты
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
	 * Возвращает способ оплаты по коду
	 * @param $code
	 * @return mixed
	 */
	public static function getByCode($code) {
		$all = self::getAll();
		return $all['ITEMS'][$code];
	}


	/**
	 * Возвращает код способа оплаты по ID
	 * @param $id
	 * @return mixed
	 */
	public static function getCodeById($id) {
		$all = self::getAll();
		return $all['CODES'][$id];
	}

	/**
	 * Возвращает массив кодов по массиву ID
	 * @param $ar
	 * @return array
	 */
	public static function format($ar) {
		$return = array();
		foreach ($ar as $id)
		{
			$code = self::getCodeById($id);
			if ($code)
				$return[] = $code;
		}
		return $return;
	}
}