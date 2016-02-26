<?

namespace Local\Data;

use Local\Catalog\Delivery;
use Local\Catalog\Payment;
use Local\Catalog\Catalog;
use Local\Catalog\Brand;
use Local\Catalog\Condition;
use Local\Catalog\Color;
use Local\Catalog\Size;
use Local\Common\ExtCache;
use Local\Common\Utils;
use Local\Api\ApiException;
use Local\User\Auth;
use Local\User\User;

/**
 * Лента "Всё и сразу"
 * Class Feed
 * @package Local\Data
 */
class Feed
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/Feed/';

	/**
	 * Количество постов за раз
	 */
	const DEFAULT_COUNT = 10;

	/**
	 * Добавляет пост в ленту
	 * @param $adId
	 * @param $userId
	 * @param $name
	 * @return bool
	 * @throws ApiException
	 */
	private static function add($adId, $userId, $name) {
		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('feed');
		$id = $iblockElement->Add(array(
			'IBLOCK_ID' => $iblockId,
			'NAME' => $name,
		    'CODE' => $adId,
		    'XML_ID' => $userId,
		));
		if (!$id)
			throw new ApiException(['feed_add_error'], 500, $iblockElement->LAST_ERROR);

		return $id;
	}

	/**
	 * Добавляет пост в ленту о добавлении объявления
	 * @param $adId
	 * @param $name
	 * @throws ApiException
	 */
	public static function addAd($adId, $name) {
		$name = 'Добавлено объявление "' . $name . '"';
		self::add($adId, 0, $name);
	}

	/**
	 * Добавляет пост в ленту о регистрации пользователя
	 * @param $userId
	 * @param $name
	 * @throws ApiException
	 */
	public static function addUser($userId, $name) {
		$name = 'Зарегистрирован пользователь "' . $name . '"';
		self::add(0, $userId, $name);
	}

	/**
	 * Возвращает посты ленты
	 * @param array $params параметры постраничной навигации
	 * @param bool $refreshCache
	 * @return array|mixed
	 */
	public static function getList($params = array(), $refreshCache = false)
	{
		$return = array();

		$elementsFilter = array();
		$count = self::DEFAULT_COUNT;

		if (intval($params['max']) > 0)
			$elementsFilter['<ID'] = intval($params['max']);
		if (intval($params['count']) > 0)
			$count = intval($params['count']);

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$elementsFilter,
			    $count,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			7200
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('feed');
			$elementsFilter['IBLOCK_ID'] = $iblockId;

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(
				array('ID' => 'DESC'),
				$elementsFilter,
				false,
				array('nTopCount' => $count),
				array(
					'ID', 'CODE', 'XML_ID',
				)
			);
			while ($item = $rsItems->Fetch())
			{
				$return[] = array(
					'id' => intval($item['ID']),
					'ad' => intval($item['CODE']),
					'user' => intval($item['XML_ID']),
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает посты ленты с заполненными данными по объявлениям и пользователям
	 * @param $params
	 * @return array
	 */
	public static function getAppData($params)
	{
		$return = array();

		$items = self::getList($params, true);
		foreach ($items as $item)
		{
			$res = array(
				'id' => $item['id'],
			    'type' => '',
			);
			if ($item['ad'])
			{
				$res['type'] = 'ad';
				$res['ad'] = Ad::shortById($item['ad']);
			}
			elseif ($item['user'])
			{
				$res['type'] = 'user';
				$res['user'] = User::publicProfile($item['user']);
			}

			$return[] = $res;
		}

		return $return;
	}
}