<?

namespace Local\Data;

use Local\Api\ApiException;
use Local\Common\ExtCache;
use Local\Common\Utils;

/**
 * Class Comments Сообщения в разных переписках
 * @package Local\Data
 */
class Messages
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/Messages/';

	/**
	 * Количество сообщений за раз
	 */
	const DEFAULT_COUNT = 10;

	/**
	 * Возвращает сообщения по ключу
	 * @param $key
	 * @param array $params
	 * @param bool $refreshCache $refreshCache для принудительного сброса кеша
	 * @return array|mixed
	 */
	public static function getByKey($key, $params = array(), $refreshCache = false)
	{
		$return = array();

		$filter = array(
			'=CODE' => $key,
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
			static::CACHE_PATH . __FUNCTION__ . '/' . $key . '/',
			86400 * 20,
			false
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('chat');
			$filter['IBLOCK_ID'] = $iblockId;

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(
				array('ID' => 'DESC'),
				$filter,
				false,
				array('nTopCount' => $count),
				array(
					'ID', 'CODE', 'XML_ID', 'PREVIEW_TEXT', 'DATE_CREATE',
				)
			);
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$return[] = array(
					'ID' => $id,
					'USER' => intval($item['XML_ID']),
					'MESSAGE' => $item['PREVIEW_TEXT'],
					'DATE' => $item['DATE_CREATE'],
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает все сообщения по ключу
	 * @param $key
	 * @return array
	 */
	public static function getAllByKey($key)
	{
		$return = array();

		$iblockId = Utils::getIBlockIdByCode('chat');
		$iblockElement = new \CIBlockElement();
		$rsItems = $iblockElement->GetList(
			array('ID' => 'DESC'),
			array(
				'=CODE' => $key,
				'IBLOCK_ID' => $iblockId,
			),
			false,
			false,
			array(
				'ID', 'CODE', 'XML_ID', 'PREVIEW_TEXT', 'DATE_CREATE',
			)
		);
		while ($item = $rsItems->Fetch())
		{
			$id = intval($item['ID']);
			$return[] = array(
				'ID' => $id,
				'USER' => intval($item['XML_ID']),
				'MESSAGE' => $item['PREVIEW_TEXT'],
				'DATE' => $item['DATE_CREATE'],
			);
		}

		return $return;
	}

	/**
	 * Добавляет сообщение
	 * @param $key
	 * @param $userId
	 * @param $message
	 * @return bool
	 * @throws ApiException
	 */
	public static function add($key, $userId, $message)
	{
		$name = htmlspecialchars($message);
		if (strlen($name) > 15)
			$name = substr($name, 0, 15) . '...';
		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('chat');
		$id = $iblockElement->Add(array(
			'IBLOCK_ID' => $iblockId,
			'NAME' => $name,
			'CODE' => $key,
			'XML_ID' => $userId,
			'PREVIEW_TEXT' => $message,
		));
		if (!$id)
			throw new ApiException(['message_add_error'], 500, $iblockElement->LAST_ERROR);

		// обновляем кеш
		self::clearCache($key);

		return $id;
	}

	/**
	 * Очищает кеш сообщений по ключу
	 * @param $key
	 */
	private static function clearCache($key)
	{
		$phpCache = new \CPHPCache();
		$phpCache->CleanDir(static::CACHE_PATH . 'getByKey/' . $key);
	}
}