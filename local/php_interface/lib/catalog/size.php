<?

namespace Local\Catalog;

use Local\Common\ExtCache;
use Local\Common\Utils;
use Local\Api\ApiException;

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
			), false, false);
			while ($section = $rsSections->Fetch())
			{
				$id = intval($section['ID']);
				$return[$id] = array(
					'ID' => $id,
					'NAME' => $section['NAME'],
					'TEXT' => $section['DESCRIPTION'],
				    'ITEMS' => array(),
				);
			}

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array('SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
			), false, false, array(
				'ID', 'NAME', 'IBLOCK_SECTION_ID',
			));
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$parent = intval($item['IBLOCK_SECTION_ID']);
				$return[$parent]['ITEMS'][$id] = array(
					'ID' => $id,
					'NAME' => $item['NAME'],
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
		return $sizes[$sizesId];
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
}