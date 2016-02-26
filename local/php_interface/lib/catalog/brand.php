<?

namespace Local\Catalog;

use Local\Common\ExtCache;
use Local\Common\Utils;
use Local\Api\ApiException;
use Local\User\Auth;

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
			    'PROPERTY_USER',
			));
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$user = intval($item['PROPERTY_USER_VALUE']);
				$return['ITEMS'][$id] = array(
					'ID' => $id,
				    'NAME' => $item['NAME'],
				    'ACTIVE' => $item['ACTIVE'],
				    'USER' => $user,
				);
				$return['BY_NAME'][$item['NAME']][] = $id;
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
		$brands = self::getAll(true);

		$userId = Auth::getCurrentUserId();

		$return = array();
		foreach ($brands['ITEMS'] as $item)
			if ($item['ACTIVE'] == 'Y')
				if (!$item['USER'] || $item['USER'] == $userId)
					$return[] = array(
						'ID' => $item['ID'],
						'NAME' => $item['NAME'],
					);

		return $return;
	}

	/**
	 * Возвращает бренд по ID
	 * @param $id
	 * @return mixed
	 */
	public static function getById($id) {
		$brands = self::getAll();
		return $brands['ITEMS'][$id];
	}

	/**
	 * Возвращает ID брендов по названию
	 * @param $name
	 * @return mixed
	 */
	public static function getIdsByName($name) {
		$brands = self::getAll();
		return $brands['BY_NAME'][$name];
	}

	public static function add($name) {
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		$name = htmlspecialchars(trim($name));
		if (!$name)
			throw new ApiException(['wrong_name'], 400);

		$brandIds = self::getIdsByName($name);
		$exists = false;
		foreach ($brandIds as $id)
		{
			$brand = self::getById($id);
			if (!$brand['USER'] || $brand['USER'] == $userId)
			{
				$exists = true;
				break;
			}
		}
		if ($exists)
			throw new ApiException(['already_exists'], 400);

		// DEBUG: на время тестов
		if ($name == 'Новый бренд')
			return array(
				'ID' => 0,
				'DEBUG' => "на самом деле не создаем элемент, чтоб в следующий раз проходил тест",
			);

		//
		// Добавление бренда
		//
		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('brand');
		$id = $iblockElement->Add(array(
			'IBLOCK_ID' => $iblockId,
			'NAME' => $name,
			'PROPERTY_VALUES' => array(
				'USER' => $userId,
			),
		));
		if (!$id)
			throw new ApiException(['brand_add_error'], 500, $iblockElement->LAST_ERROR);

		return array(
			'ID' => $id,
		);
	}
}