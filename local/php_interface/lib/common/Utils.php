<?
namespace Local\Common;

/**
 * Class Utils Различные утилиты проекта
 */
class Utils
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Common/Utils/';

	/**
	 * Склонение числительных
	 * @param $num
	 * @param string $s0 5 товаров
	 * @param string $s1 1 товар
	 * @param string $s2 2 товара
	 * @return string
	 */
	public static function getCardinalNumberRus($num, $s0 = '', $s1 = '', $s2 = '')
	{
		$length = strlen($num);
		$n = intval($num);
		$dec = 0;
		if ($length > 1)
		{
			$n = intval(substr($num, ($length - 1)));
			$dec = intval(substr($num, ($length - 2), 1));
		}
		if ($n > 4 || $n == 0 || $dec == 1)
			return $s0;
		elseif ($n == 1)
			return $s1;
		else
			return $s2;
	}

	/**
	 * возвращает время кэширования с учетом режима автокэширования
	 * @param int $cacheTime
	 * @param string $cacheType
	 * @param int $default
	 * @return int
	 */
	public static function getCacheTime($cacheTime = 0, $cacheType = 'A', $default = 0)
	{
		$cacheTime = intval($cacheTime);
		$cacheTime = $cacheTime > 0 ? $cacheTime : $default;
		$cacheType = $cacheType != 'Y' && $cacheType != 'N' ? 'A' : $cacheType;
		if ($cacheType == 'N' || ($cacheType == 'A' && \COption::GetOptionString('main', 'component_cache_on', 'Y') == 'N'))
			$cacheTime = 0;
		return $cacheTime;
	}

	/**
	 * Возвращает все инфоблоки
	 * @param bool|false $refreshCache сбросить кеш
	 * @return array
	 */
	public static function getAllIBlocks($refreshCache = false)
	{
		$return = array();

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20,
			false
		);
		if (!$refreshCache && $extCache->initCache())
		{
			$return = $extCache->getVars();
		}
		else
		{
			$extCache->startDataCache();

			$iblock = new \CIBlock();
			$rsItems = $iblock->GetList(array(), Array(), false);
			while ($item = $rsItems->Fetch())
			{

				$return['ITEMS'][$item['ID']] = array(
					'ID' => $item['ID'],
					'ACTIVE' => $item['ACTIVE'],
					'NAME' => $item['NAME'],
					'CODE' => $item['CODE'],
					'TYPE' => $item['IBLOCK_TYPE_ID'],
				);
				if ($item['CODE'])
				{
					$return['BY_CODE'][$item['CODE']] = $item['ID'];
				}
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает ID инфоблока по коду
	 * @param $code
	 * @return mixed
	 */
	public static function getIBlockIdByCode($code)
	{
		$iblocks = self::getAllIBlocks();
		return $iblocks['BY_CODE'][$code];
	}

	/**
	 * Возвращает инфоблок по коду
	 * @param $code
	 * @return mixed
	 */
	public static function getIBlockByCode($code)
	{
		$iblocks = self::getAllIBlocks();
		$id = $iblocks['BY_CODE'][$code];
		return $iblocks['ITEMS'][$id];
	}

	/**
	 * Возвращает инфоблок по ID
	 * @param $id
	 * @return mixed
	 */
	public static function getIBlockById($id)
	{
		$iblocks = self::getAllIBlocks();
		return $iblocks['ITEMS'][$id];
	}
}
