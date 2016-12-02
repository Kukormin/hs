<?

namespace Local\Catalog;

use Local\Common\ExtCache;
use Local\Common\Utils;

/**
 * Class Catalog Дерево разделов каталога
 * @package Local\Catalog
 */
class Catalog
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Catalog/Catalog/';

	/**
	 * Возвращает все разделы каталога
	 * (учитывает теговый кеш)
	 * @param bool $refreshCache для принудительного сброса кеша
	 * @return array|mixed
	 */
	public static function getAllSections($refreshCache = false) {
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

			$iblockId = Utils::getIBlockIdByCode('catalog');

			$iblockSection = new \CIBlockSection();
			$rsSections = $iblockSection->GetList(array('LEFT_MARGIN' => 'ASC', 'SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
			), false, array(
				'UF_SIZE'
			));
			while ($section = $rsSections->Fetch())
			{
				$id = intval($section['ID']);
				$parent = intval($section['IBLOCK_SECTION_ID']);
				$return[$id] = array(
					'ID' => $id,
					'NAME' => $section['NAME'],
					'FULL' => $section['CODE'],
					'ACTIVE' => $section['ACTIVE'],
					'PARENT' => $parent,
				    'SIZE' => $section['UF_SIZE'],
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает активные разделы каталога
	 * @return array
	 */
	public static function getAppData() {
		$sections = self::getAllSections();

		$return = array();
		foreach ($sections as $item)
			if ($item['ACTIVE'] == 'Y')
				$return[] = array(
					'id' => $item['ID'],
					'name' => $item['NAME'],
					'full' => $item['FULL'],
					'parent' => $item['PARENT'],
				);

		return $return;
	}

	/**
	 * Возвращает раздел по ID
	 * @param $id
	 * @return mixed
	 */
	public static function getSectionById($id) {
		$sections = self::getAllSections();
		return $sections[$id];
	}

}