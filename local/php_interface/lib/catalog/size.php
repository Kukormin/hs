<?

namespace Local\Catalog;

use Local\Common\ExtCache;
use Local\Common\Utils;
use Local\Api\ApiException;

/**
 * Class Size Размеры
 * @package Local\Catalog
 */
class Size
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Catalog/Size/';

	/**
	 * Возвращает все размеры
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

			$iblockId = Utils::getIBlockIdByCode('size');

			$iblockSection = new \CIBlockSection();
			$rsSections = $iblockSection->GetList(array('LEFT_MARGIN' => 'ASC', 'SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
			), false, array(
				'UF_FOR_M',
				'UF_FOR_W',
			));
			while ($section = $rsSections->Fetch())
			{
				$id = intval($section['ID']);
				$return['SECTIONS'][$id] = array(
					'ID' => $id,
					'NAME' => $section['NAME'],
					'TEXT' => $section['DESCRIPTION'],
					'FOR_M' => intval($section['UF_FOR_M']),
					'FOR_W' => intval($section['UF_FOR_W']),
				    'ITEMS' => array(),
				);
			}

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array('SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
			), false, false, array(
				'ID', 'NAME', 'ACTIVE', 'IBLOCK_SECTION_ID',
			));
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$parent = intval($item['IBLOCK_SECTION_ID']);
				$return['SECTIONS'][$parent]['ITEMS'][$id] = array(
					'ID' => $id,
					'NAME' => $item['NAME'],
					'ACTIVE' => $item['ACTIVE'],
				);
				$return['ITEMS'][$id] = array(
					'ID' => $id,
					'NAME' => $item['NAME'],
					'ACTIVE' => $item['ACTIVE'],
				    'PARENT' => $parent,
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает размеры по ID папки с размерами
	 * @param $sizesId
	 * @return mixed
	 */
	public static function getBySizesId($sizesId)
	{
		$sizes = self::getAll();
		return $sizes['SECTIONS'][$sizesId];
	}

	/**
	 * Возвращает размер по ID
	 * @param $id
	 * @return mixed
	 */
	public static function getById($id)
	{
		$sizes = self::getAll();
		return $sizes['ITEMS'][$id];
	}

	/**
	 * Возвращает папку с размерами и активные размеры по ID раздела
	 * если $sectionId не задан, то все папки с размерами
	 * @param $sectionId
	 * @return array
	 * @throws ApiException
	 */
	public static function getAppData($sectionId)
	{
		$return = array();
		if ($sectionId)
		{
			$sizes = self::getBySectionId($sectionId);
			if ($sizes)
				$return = self::getSingleAppData($sizes);
		}
		else
		{
			$all = self::getAll();
			foreach ($all['SECTIONS'] as $sizes)
				$return[] = self::getSingleAppData($sizes);
		}

		return $return;
	}

	/**
	 * Возвращает размеры указанной размерной линейки
	 * @param $sizes
	 * @return array
	 */
	public static function getSingleAppData($sizes)
	{
		$return = array(
			'id' => $sizes['ID'],
			'name' => $sizes['NAME'],
			'text' => $sizes['TEXT'],
			'm' => $sizes['FOR_M'],
			'w' => $sizes['FOR_W'],
		);
		foreach ($sizes['ITEMS'] as $item)
			if ($item['ACTIVE'] == 'Y')
				$return['items'][] = array(
					'id' => $item['ID'],
					'name' => $item['NAME'],
				);

		return $return;
	}

	/**
	 * Возвращает размеры по ID раздела каталога
	 * @param $sectionId
	 * @return array|mixed
	 * @throws ApiException
	 */
	public static function getBySectionId($sectionId) {
		if (!$sectionId)
			throw new ApiException(['wrong_section'], 404);

		$sizesId = 0;
		while ($sectionId)
		{
			$section = Catalog::getSectionById($sectionId);
			if (!$section)
				throw new ApiException(['wrong_section'], 404);

			$sizesId = $section['SIZE'];
			if ($sizesId)
				break;

			$sectionId = $section['PARENT'];
		}

		$return = array();
		if ($sizesId)
			$return = self::getBySizesId($sizesId);

		return $return;
	}

	/**
	 * Возвращает размер по ID (для указанного раздела)
	 * @param $sectionId
	 * @param $id
	 * @return mixed
	 */
	public static function getBySectionAndId($sectionId, $id)
	{
		$sizes = self::getBySectionId($sectionId);
		return $sizes['ITEMS'][$id];
	}

	/**
	 * Возвращает
	 * @param $includedSizes
	 * @return array
	 */
	public static function getExcludedSizes($includedSizes)
	{
		$result = array();
		$all = self::getAll();
		foreach ($all['SECTIONS'] as $sectionId => $sizes)
		{
			$sectionRes = array();
			$sectionEx = false;
			foreach ($sizes['ITEMS'] as $item)
			{
				$id = $item['ID'];
				if (in_array($id, $includedSizes))
					$sectionEx = true;
				else
					$sectionRes[] = $id;
			}
			if ($sectionEx)
				$result = array_merge($result, $sectionRes);
		}

		return $result;
	}
}