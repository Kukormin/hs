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
use Local\User\Follower;
use Local\User\User;

/**
 * Class Feed Лента "Всё и сразу"
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
	 * @return int
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
	 * @return int
	 * @throws ApiException
	 */
	public static function addAd($adId, $name) {
		$name = 'Добавлено объявление "' . $name . '"';
		return self::add($adId, 0, $name);
	}

	/**
	 * Добавляет пост в ленту о регистрации пользователя
	 * @param $userId
	 * @param $name
	 * @return int
	 * @throws ApiException
	 */
	public static function addUser($userId, $name) {
		$name = 'Зарегистрирован пользователь "' . $name . '"';
		return self::add(0, $userId, $name);
	}

	/**
	 * Добавляет пост в ленту о том, что пользователь поделился объявлением
	 * @param $adId
	 * @param $userId
	 * @param $name
	 * @return int
	 * @throws ApiException
	 */
	public static function addShare($adId, $userId, $name) {
		$name = 'Пользователь поделился объявлением "' . $name . '"';
		return self::add($adId, $userId, $name);
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

		$filter = array();
		$count = self::DEFAULT_COUNT;

		if (intval($params['max']) > 0)
			$filter['<ID'] = intval($params['max']);
		if (intval($params['count']) > 0)
			$count = intval($params['count']);
		if (isset($params['publishers']))
		{
			// добавляем 0, чтоб не отфильтровать посты с XML_ID = 0 (добавление объявления)
			$params['publishers'][] = 0;
			$filter[] = array(
				'LOGIC' => 'OR',
				'=CODE' => 0, // регистрация пользователя
				'=XML_ID' => $params['publishers'],
			);
		}

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$filter,
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
			$filter['IBLOCK_ID'] = $iblockId;

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(
				array('ID' => 'DESC'),
				$filter,
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
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];
		$follow = Follower::get($userId);
		// Поделившиеся объявления нужно получить только у подписанных пользователей
		$params['publishers'] = $follow['publishers'];

		$return = array();

		$items = self::getList($params);
		foreach ($items as $item)
		{
			$res = array(
				'id' => $item['id'],
			    'type' => '',
			);

			if ($item['ad'])
				$res['ad'] = Ad::shortById($item['ad']);
			if ($item['user'])
				$res['user'] = User::publicProfile($item['user']);

			if ($item['ad'] && $item['user'])
				$res['type'] = 'share';
			elseif ($item['ad'])
				$res['type'] = 'ad';
			elseif ($item['user'])
				$res['type'] = 'user';

			$return[] = $res;
		}

		return $return;
	}
}