<?

namespace Local\Data;

use Local\Common\ExtCache;
use Local\Common\Utils;

/**
 * Class Faq Частые вопросы и ответы
 * @package Local\Data
 */
class Faq
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/Faq/';

	/**
	 * Возвращает все разделы и элементы
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

			$iblockId = Utils::getIBlockIdByCode('faq');

			$iblockSection = new \CIBlockSection();
			$rsSections = $iblockSection->GetList(array('LEFT_MARGIN' => 'ASC', 'SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
			), false, false);
			while ($section = $rsSections->Fetch())
			{
				$id = intval($section['ID']);
				$parent = intval($section['IBLOCK_SECTION_ID']);
				$return['sections'][] = array(
					'id' => $id,
					'name' => $section['NAME'],
					'parent' => $parent,
				);
			}

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array('SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
				'ACTIVE' => 'Y',
			), false, false, array(
				'ID', 'NAME', 'PREVIEW_TEXT', 'IBLOCK_SECTION_ID',
			));
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$parent = intval($item['IBLOCK_SECTION_ID']);
				$return['items'][] = array(
					'id' => $id,
					'q' => $item['NAME'],
					'a' => $item['PREVIEW_TEXT'],
				    'parent' => $parent,
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}
}