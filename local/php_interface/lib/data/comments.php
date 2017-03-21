<?

namespace Local\Data;

use Local\Api\ApiException;
use Local\Common\ExtCache;
use Local\Common\Utils;

/**
 * Class Comments Комментарии в объявлениях
 * @package Local\Data
 */
class Comments
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/Comments/';

	/**
	 * Количество комментариев за раз
	 */
	const DEFAULT_COUNT = 10;

	/**
	 * @var string буквы, для разбиения на слова
	 */
	private static $chars = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя';

	/**
	 * Возвращает комментарии заданного объявления
	 * @param $adId
	 * @param array $params
	 * @param bool $refreshCache $refreshCache для принудительного сброса кеша
	 * @return array|mixed
	 */
	public static function getByAd($adId, $params = array(), $refreshCache = false)
	{
		$return = array();

		$adId = intval($adId);
		$filter = array(
			'=CODE' => $adId,
		);
		$count = self::DEFAULT_COUNT;

		if (intval($params['max']) > 0)
			$filter['<ID'] = intval($params['max']);
		if (intval($params['count']) > 0)
			$count = intval($params['count']);

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$filter,
				$count,
			),
			static::CACHE_PATH . __FUNCTION__ . '/' . $adId . '/',
			86400 * 20,
			false
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('comment');
			$filter['IBLOCK_ID'] = $iblockId;
			$filter['ACTIVE'] = 'Y';

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(
				array('ID' => 'DESC'),
				$filter,
				false,
				array('nTopCount' => $count),
				array(
					'ID', 'CODE', 'XML_ID', 'PREVIEW_TEXT',
				)
			);
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$return[] = array(
					'ID' => $id,
					'USER' => intval($item['XML_ID']),
					'MESSAGE' => $item['PREVIEW_TEXT'],
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает количество комментариев к объявлению
	 * @param $adId
	 * @param bool $refreshCache
	 * @return int|mixed
	 */
	public static function getCountByAd($adId, $refreshCache = false)
	{
		$adId = intval($adId);

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$adId,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20,
			false
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('comment');
			$filter['IBLOCK_ID'] = $iblockId;
			$filter['ACTIVE'] = 'Y';

			$iblockElement = new \CIBlockElement();
			$count = $iblockElement->GetList(
				array('ID' => 'DESC'),
				array(
					'IBLOCK_ID' => $iblockId,
					'ACTIVE' => 'Y',
					'=CODE' => $adId,
				),
				array(),
				false
			);
			$return = intval($count);

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Добавляет комментарий
	 * @param $adId
	 * @param $userId
	 * @param $message
	 * @return bool
	 * @throws ApiException
	 */
	public static function add($adId, $userId, $message)
	{
		if (!self::checkProfanity($message))
			throw new ApiException(['profanity'], 400);

		$name = htmlspecialchars($message);
		if (strlen($name) > 15)
			$name = substr($name, 0, 15) . '...';
		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('comment');
		$id = $iblockElement->Add(array(
			'IBLOCK_ID' => $iblockId,
			'NAME' => $name,
			'CODE' => $adId,
			'XML_ID' => $userId,
			'PREVIEW_TEXT' => $message,
		));
		if (!$id)
			throw new ApiException(['comment_add_error'], 500, $iblockElement->LAST_ERROR);

		// обновляем кеш
		self::clearCache($adId);
		self::getCountByAd($adId, true);

		News::comment($name, $userId, $adId);

		return $id;
	}

	/**
	 * Проверяет текст на наличие ненормативной лексики
	 * @param $message
	 * @return bool
	 */
	private static function checkProfanity($message)
	{
		$stop = Options::get('stop_words');
		$stopWords = explode(' ', $stop);
		$words = str_word_count(mb_strtolower($message), 1, self::$chars);
		foreach ($words as $word)
		{
			if (in_array($word, $stopWords))
				return false;
		}
		return true;
	}

	/**
	 * Очищает кеш комментов для объявления
	 * @param $adId
	 */
	private static function clearCache($adId)
	{
		$phpCache = new \CPHPCache();
		$phpCache->CleanDir(static::CACHE_PATH . 'getByAd/' . $adId);
	}

}