<?

namespace Local\User;

use Local\Catalog\Brand;
use Local\Catalog\Catalog;
use Local\Catalog\Delivery;
use Local\Catalog\Gender;
use Local\Catalog\Payment;
use Local\Catalog\Size;
use Local\Common\ExtCache;
use Local\Common\Utils;
use Local\Api\ApiException;
use Local\Data\Ad;
use Local\Data\Deal;
use Local\Data\Feed;
use Local\Data\History;
use Local\Data\Messages;
use Local\Data\News;
use Local\Data\Status;

/**
 * Class User Пользователи мобильного приложения
 * @package Local\User
 */
class User
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/User/User/';

	/**
	 * Количество пользователей за раз
	 */
	const DEFAULT_COUNT = 10;

	/**
	 * Возвращает пользователя по номеру телефона
	 * @param $phone
	 * @param bool $refreshCache
	 * @return mixed
	 */
	public static function getByPhone($phone, $refreshCache = false) {
		$phone = strval($phone);

		$return = [];
		$extCache = new ExtCache(
			[
				__FUNCTION__,
			    $phone,
			],
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20,
			false // не используем теговый кеш, чтоб не удалять кеш при измененях в пользователях
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('user');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList([], [
				'IBLOCK_ID' => $iblockId,
				'NAME' => $phone,
			], false, false, [
				'ID', 'NAME', 'ACTIVE', 'TMP_ID',
			]);
			if ($item = $rsItems->Fetch())
			{
				$return = [
					'ID' => intval($item['ID']),
					'NAME' => $item['NAME'],
				    'ACTIVE' => $item['ACTIVE'],
				    'SMS' => $item['TMP_ID'],
				];
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает профиль текущего пользователя
	 * @return mixed
	 * @throws ApiException
	 */
	public static function profile()
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		$profile = self::getById($userId);
		$profile['follow'] = Follower::get($userId);
		return $profile;
	}

	/**
	 * Возвращает пользователя вместе со сделками и объявлениями
	 * @param $publicUserId
	 * @param $sub
	 * @param $params
	 * @return array
	 * @throws ApiException
	 */
	public static function publicProfileFull($publicUserId, $sub, $params)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if ($sub == 'deals')
		{
			$params['history'] = 'Y';
			$deals = Deal::getByUserFormatted($publicUserId, 1, $params);
			return $deals;
		}
		elseif ($sub == 'ads')
		{
			$ads = Ad::getListByUser($publicUserId, $params);
			return $ads;
		}
		else
		{
			$profile = self::publicProfile($publicUserId);
			$follow = Follower::get($userId);
			$profile['followed'] = in_array($publicUserId, $follow['publishers']);
			$profile['deals'] = Deal::getByUserFormatted($publicUserId, 1, ['history' => 'Y']);
			$profile['ads'] = Ad::getListByUser($publicUserId);
			return $profile;
		}
	}

	/**
	 * Возвращает публичный профиль пользователя
	 * @param $userId
	 * @return array
	 */
	public static function publicProfile($userId)
	{
		$profile = self::getById($userId);
		$follow = Follower::get($userId);
		return [
			'id' => $profile['id'],
			'city' => $profile['city'],
			'name' => $profile['name'],
			'nickname' => $profile['nickname'],
			'photo' => $profile['photo'],
		    'followers' => count($follow['followers'])
		];
	}

	/**
	 * Возвращает пользователя по ID
	 * @param $id
	 * @param bool $refreshCache
	 * @return mixed
	 */
	public static function getById($id, $refreshCache = false) {
		$id = intval($id);

		$return = [];
		$extCache = new ExtCache(
			[
				__FUNCTION__,
				$id,
			],
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20,
			false // не используем теговый кеш, чтоб не удалять кеш при измененях в пользователях
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('user');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList([], [
			    'IBLOCK_ID' => $iblockId,
			    'ID' => $id,
			], false, false, [
				'ID', 'IBLOCK_ID', 'NAME', 'CODE',
			    'PROPERTY_NAME',
			    'PROPERTY_CITY',
			    'PROPERTY_EMAIL',
			    'PROPERTY_PHOTO',
			    'PROPERTY_STREET',
			    'PROPERTY_FLAT',
			    'PROPERTY_INDEX',
			    'PROPERTY_FIO',
			    'PROPERTY_GENDER',
			    'PROPERTY_SIZE',
			    'PROPERTY_BRAND',
			    'PROPERTY_SECTION',
			]);
			if ($item = $rsItems->Fetch())
			{
				$photo = [];
				if ($item['PROPERTY_PHOTO_VALUE'])
					$photo = [Utils::getFileArray($item['PROPERTY_PHOTO_VALUE'])];
				$gender = '';
				if ($item['PROPERTY_GENDER_VALUE'])
					$gender = Gender::getCodeById($item['PROPERTY_GENDER_VALUE']);
				$size = [];
				foreach ($item['PROPERTY_SIZE_VALUE'] as $tmp)
					$size[] = intval($tmp);
				$brand = [];
				foreach ($item['PROPERTY_BRAND_VALUE'] as $tmp)
					$brand[] = intval($tmp);
				$section = [];
				foreach ($item['PROPERTY_SECTION_VALUE'] as $tmp)
					$section[] = intval($tmp);
				$return = [
					'id' => intval($item['ID']),
					'phone' => $item['NAME'],
					'name' => $item['PROPERTY_NAME_VALUE'],
					'city' => $item['PROPERTY_CITY_VALUE'],
					'nickname' => $item['CODE'],
					'email' => $item['PROPERTY_EMAIL_VALUE'],
					'photo' => $photo,
					'gender' => $gender,
					'address' => [
						'street' => $item['PROPERTY_STREET_VALUE'],
						'flat' => $item['PROPERTY_FLAT_VALUE'],
						'index' => $item['PROPERTY_INDEX_VALUE'],
						'fio' => $item['PROPERTY_FIO_VALUE'],
					],
					'sizes' => $size,
					'brands' => $brand,
					'sections' => $section,
				];
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Добавляет пользователя с указанным номером телефона
	 * @param $phone
	 * @return bool
	 * @throws ApiException
	 */
	public static function addByPhone($phone) {
		$iblockElement = new \CIBlockElement();

		$iblockId = Utils::getIBlockIdByCode('user');
		$id = $iblockElement->Add([
			'IBLOCK_ID' => $iblockId,
			'NAME' => $phone,
			'ACTIVE' => 'Y',
		]);
		if (!$id)
			throw new ApiException(['user_add_error'], 500, $iblockElement->LAST_ERROR);

		return $id;
	}

	/**
	 * Проверка занят ли Никнейм
	 * @param $nickname
	 * @return array
	 * @throws ApiException
	 */
	public static function nickname($nickname)
	{
		$nickname = htmlspecialchars(trim($nickname));
		if (!$nickname)
			throw new ApiException(['wrong_nickname'], 400);

		if (strpos($nickname, ' ') !== false)
			throw new ApiException(['space'], 400);

		$id = self::getIdByNickName($nickname);
		return [
			'used' => $id ? 1 : 0,
		];
	}

	/**
	 * Обновляет данные пользователя. Возвращает профиль
	 * @param $data
	 * @return mixed
	 * @throws ApiException
	 */
	public static function update($data)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		$profile = self::getById($userId);

		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('user');

		$fields = [];
		if ($data['nickname'] && $profile['nickname'] != $data['nickname'])
		{
			$nickname = htmlspecialchars(trim($data['nickname']));
			if (strpos($nickname, ' ') !== false)
				throw new ApiException(['space'], 400);

			$id = self::getIdByNickName($nickname);
			if ($id == 0)
				$fields['CODE'] = $nickname;
			elseif ($id != $userId)
				throw new ApiException(['nickname_already_exists'], 400);

			// Когда у пользователя не было никнейма и теперь он появился,
			// считается, что пользователь до конца прошел процедуру регистрации
			if ($nickname && !$profile['nickname'])
				Feed::addUser($userId, $nickname);
		}
		if ($fields)
			$iblockElement->Update($userId, $fields);

		$properties = [];
		if ($data['name'] && $profile['name'] != $data['name'])
			$properties['NAME'] = htmlspecialchars(trim($data['name']));
		if ($data['city'] && $profile['city'] != $data['city'])
			$properties['CITY'] = htmlspecialchars(trim($data['city']));
		if ($data['email'] && $profile['email'] != $data['email'])
			$properties['EMAIL'] = htmlspecialchars(trim($data['email']));
		if ($data['gender'] && $profile['gender'] != $data['gender'])
		{
			$gender = Gender::getByCode($data['gender']);
			$properties['GENDER'] = $gender['ID'];
		}
		if (isset($data['address']))
		{
			if ($profile['address']['street'] != $data['address']['street'])
				$properties['STREET'] = htmlspecialchars(trim($data['address']['street']));
			if ($profile['address']['flat'] != $data['address']['flat'])
				$properties['FLAT'] = htmlspecialchars(trim($data['address']['flat']));
			if ($profile['address']['index'] != $data['address']['index'])
				$properties['INDEX'] = htmlspecialchars(trim($data['address']['index']));
			if ($profile['address']['fio'] != $data['address']['fio'])
				$properties['FIO'] = htmlspecialchars(trim($data['address']['fio']));
		}
		if (isset($data['sizes']) && $profile['sizes'] != $data['sizes'])
		{
			$sizeIds = [];
			foreach($data['sizes'] as $id)
			{
				$size = Size::getById($id);
				if ($size['ACTIVE'] == 'Y')
					$sizeIds[] = $size['ID'];
			}
			if ($sizeIds)
				$properties['SIZE'] = $sizeIds;
		}
		if (isset($data['brands']) && $profile['brands'] != $data['brands'])
		{
			$brandIds = [];
			foreach($data['brands'] as $id)
			{
				$brand = Brand::getById($id);
				if ($brand['ACTIVE'] == 'Y')
					if (!$brand['USER'] || $brand['USER'] == $userId)
						$brandIds[] = $brand['ID'];
			}
			if ($brandIds)
				$properties['BRAND'] = $brandIds;
		}
		if (isset($data['sections']) && $profile['sections'] != $data['sections'])
		{
			$sectionIds = [];
			foreach($data['sections'] as $id)
			{
				$section = Catalog::getSectionById($id);
				if ($section['ACTIVE'] == 'Y')
					$sectionIds[] = $section['ID'];
			}
			if ($sectionIds)
				$properties['SECTION'] = $sectionIds;
		}
		foreach ($_FILES as $file)
		{
			$properties['PHOTO'] = $file;
			break;
		}
		if (!$properties['PHOTO'] && $data['photo'] == 'delete' && $profile['photo'])
			$properties['PHOTO'] = [
				'del' => 'Y'
			];

		if ($properties)
			$iblockElement->SetPropertyValuesEx($userId, $iblockId, $properties);

		$refreshCache = ($properties || $fields) ? true : false;

		return self::getById($userId, $refreshCache);
	}

	/**
	 * Находит ID пользователя по никнейму
	 * @param $code
	 * @return int
	 */
	private static function getIdByNickName($code)
	{
		$id = 0;
		$iblockId = Utils::getIBlockIdByCode('user');
		$iblockElement = new \CIBlockElement();
		$rsItems = $iblockElement->GetList([], [
			'CODE' => $code,
			'IBLOCK_ID' => $iblockId,
		], false, false, [
			'ID',
		]);
		if ($item = $rsItems->Fetch())
			$id = $item['ID'];

		return $id;
	}

	/**
	 * Поиск. Возвращает пользавателей по запросу
	 * @param $params
	 * @return array
	 * @throws ApiException
	 */
	public static function search($params)
	{
		$return = [];

		$q = htmlspecialchars($params['q']);
		if (strlen($q) < 3)
			throw new ApiException(['short_query'], 400);

		$filter = [
			'CODE' => '%' . $q . '%',
		];

		if ($params['type'] == 'count')
		{
			$return = [
				'count' => self::getCountByFilter($filter),
			];
		}
		else
		{
			$count = self::DEFAULT_COUNT;
			if (intval($params['max']) > 0)
				$filter['<ID'] = intval($params['max']);
			if (intval($params['count']) > 0)
				$count = intval($params['count']);

			$ids = self::getByFilter($filter, $count, true);
			foreach ($ids as $id)
				$return[] = self::publicProfile($id);
		}

		return $return;
	}

	/**
	 * Возвращает количество пользователей по фильтру
	 * @param $filter
	 * @param bool $refreshCache
	 * @return \CIBlockResult|int|mixed
	 */
	private static function getCountByFilter($filter, $refreshCache = false)
	{
		$extCache = new ExtCache(
			[
				__FUNCTION__,
				$filter,
			],
			static::CACHE_PATH . __FUNCTION__ . '/',
			7200
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('user');
			$filter['IBLOCK_ID'] = $iblockId;

			$iblockElement = new \CIBlockElement();
			$return = $iblockElement->GetList(
				['ID' => 'DESC'],
				$filter,
				[],
				false
			);

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает ID пользователей по фильтру
	 * @param $filter
	 * @param int $count максимальное количество
	 * @param bool $refreshCache
	 * @return array|mixed
	 */
	private static function getByFilter($filter, $count, $refreshCache = false)
	{
		$return = [];

		$extCache = new ExtCache(
			[
				__FUNCTION__,
				$filter,
				$count,
			],
			static::CACHE_PATH . __FUNCTION__ . '/',
			7200
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('user');
			$filter['IBLOCK_ID'] = $iblockId;

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(
				['ID' => 'DESC'],
				$filter,
				false,
				['nTopCount' => $count],
				[
					'ID',
				]
			);
			while ($item = $rsItems->Fetch())
				$return[] = intval($item['ID']);

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Подписывает текущего пользователя на пользователя $publisherId
	 * @param $publisherId
	 * @return array|mixed
	 * @throws ApiException
	 */
	public static function follow($publisherId)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$publisherId || $publisherId == $userId)
			throw new ApiException(['wrong_publisher'], 400);

		$publisher = self::getById($publisherId);
		if (!$publisher)
			throw new ApiException(['publisher_not_found'], 400);

		Follower::add($userId, $publisherId);

		$user = User::getById($userId);
		self::push(
			$publisherId,
			'На Вас подписался "' . $user['nickname'] . '"',
			['type' => 'follow', 'userId' => intval($userId)]
		);

		return Follower::get($userId);
	}

	/**
	 * Возвращает подписчиков пользователя
	 * @return array
	 * @throws ApiException
	 */
	public static function followers()
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		$return = [];

		$follow = Follower::get($userId);
		foreach ($follow['followers'] as $id)
			$return[] = self::publicProfile($id);

		return $return;
	}

	/**
	 * Возвращает пользователей, на кого подписан текущий
	 * @return array
	 * @throws ApiException
	 */
	public static function publishers()
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		$return = [];

		$follow = Follower::get($userId);
		foreach ($follow['publishers'] as $id)
			$return[] = self::publicProfile($id);

		return $return;
	}

	/**
	 * Отписывает текущего пользователя от пользователя $publisherId
	 * @param $publisherId
	 * @return array|mixed
	 * @throws ApiException
	 */
	public static function unfollow($publisherId)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$publisherId || $publisherId == $userId)
			throw new ApiException(['wrong_publisher'], 400);

		$publisher = self::getById($publisherId);
		if (!$publisher)
			throw new ApiException(['publisher_not_found'], 400);

		Follower::delete($userId, $publisherId);

		$user = User::getById($userId);
		self::push(
			$publisherId,
			'От Вас отписался "' . $user['nickname'] . '"',
			['type' => 'unfollow', 'userId' => intval($userId)]
		);

		return Follower::get($userId);
	}

	/**
	 * Добавляет объявление в избранное
	 * @param $adId
	 * @return array
	 * @throws ApiException
	 */
	public static function addToFavorite($adId)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$adId)
			throw new ApiException(['wrong_ad'], 400);

		$ad = Ad::getById($adId);
		if (!$ad)
			throw new ApiException(['ad_not_found'], 400);

		Favorite::add($userId, $adId);

		return [
			'count' => Favorite::getCountByUser($userId),
		];
	}

	/**
	 * Удаляет объявление из избранного
	 * @param $adId
	 * @return array
	 * @throws ApiException
	 */
	public static function removeFromFavorite($adId)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$adId)
			throw new ApiException(['wrong_ad'], 400);

		$ad = Ad::getById($adId);
		if (!$ad)
			throw new ApiException(['ad_not_found'], 400);

		Favorite::remove($userId, $adId);

		return [
			'count' => Favorite::getCountByUser($userId),
		];
	}

	/**
	 * Возвращает избранное пользователя
	 * @param $params
	 * @return array
	 * @throws ApiException
	 */
	public static function favorites($params)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		$return = [];

		$fav = Favorite::getList($userId, $params);
		foreach ($fav as $item)
			$return[] = [
				'id' => $item['ID'],
				'ad' => Ad::shortById($item['AD']),
			];

		return $return;
	}

	/**
	 * Возвращает количество избранных объявлений у пользователя
	 * @return array
	 * @throws ApiException
	 */
	public static function favoritesCount()
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		return [
			'count' => Favorite::getCountByUser($userId),
		];
	}

	/**
	 * Добавление сделки
	 * @param $adIds
	 * @param $payment
	 * @param $delivery
	 * @param $check
	 * @param $address
	 * @param bool $debug
	 * @return array
	 * @throws ApiException
	 */
	public static function addDeal($adIds, $payment, $delivery, $check, $address, $debug = false)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$payment)
			throw new ApiException(['wrong_payment'], 400);

		if (!$delivery)
			throw new ApiException(['wrong_delivery'], 400);

		if ($payment == 'agreement' && $delivery != 'personal')
			throw new ApiException(['wrong_payment_delivery'], 400);

		if (!$adIds)
			throw new ApiException(['wrong_ad'], 400);

		$deliveryPrice = 0;
		$name = '';
		$sellerId = 0;
		foreach ($adIds as $adId)
		{
			if (!$adId)
				throw new ApiException(['wrong_ad'], 400);

			$ad = Ad::getById($adId);
			if (!$ad)
				throw new ApiException(['ad_not_found'], 400);

			if ($ad['USER'] == $userId)
				throw new ApiException(['self_ad'], 400);

			if (!$ad['CAN_BUY'])
				throw new ApiException(['ad_with_deal'], 400);

			if (!in_array($payment, Payment::format($ad['PAYMENT'])))
				throw new ApiException(['wrong_payment'], 400);

			$deliveries = Delivery::format($ad['DELIVERY']);
			if (!in_array($delivery, $deliveries))
				throw new ApiException(['wrong_delivery'], 400);

			if ($deliveryPrice < $deliveries[$delivery])
				$deliveryPrice = $deliveries[$delivery];

			if (!$name)
				$name = $ad['NAME'];

			if (!$sellerId)
				$sellerId = $ad['USER'];
			else
				if ($sellerId != $ad['USER'])
					throw new ApiException(['only_one_seller'], 400);
		}

		if ($delivery != 'personal')
			if (!$address)
				throw new ApiException(['wrong_address'], 400);

		$check = $check ? true : false;

		if ($debug)
			return [
				'id' => 1,
			];

		$id = Deal::add($adIds, $name, $userId, $sellerId, $payment, $delivery, $deliveryPrice, $check, $address);

		return [
			'id' => $id,
		];
	}

	/**
	 * Добавление объявления к сделке
	 * @param $adId
	 * @param $dealId
	 * @return array
	 * @throws ApiException
	 */
	public static function appendAdToDeal($adId, $dealId)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$adId)
			throw new ApiException(['wrong_ad'], 400);

		$ad = Ad::getById($adId);
		if (!$ad)
			throw new ApiException(['ad_not_found'], 400);

		if ($ad['USER'] == $userId)
			throw new ApiException(['self_ad'], 400);

		if (!$ad['CAN_BUY'])
			throw new ApiException(['ad_with_deal'], 400);

		if (!$dealId)
			throw new ApiException(['wrong_deal'], 400);

		$deal = Deal::getById($dealId);
		if (!$deal)
			throw new ApiException(['deal_not_found'], 400);

		if ($userId != $deal['BUYER'])
			throw new ApiException(['not_your_deal'], 400);

		if (in_array($adId, $deal['AD']))
			throw new ApiException(['already_in_deal'], 400);

		if ($ad['USER'] != $deal['SELLER'])
			throw new ApiException(['only_one_seller'], 400);

		if (!in_array($deal['PAYMENT'], $ad['PAYMENT']))
			throw new ApiException(['wrong_payment'], 400);

		if (!in_array($deal['DELIVERY'], $ad['DELIVERY']))
			throw new ApiException(['wrong_delivery'], 400);

		$deal['AD'][] = $adId;
		Deal::update($dealId, ['AD' => $deal['AD']]);

		return [
			'id' => $dealId,
		];
	}

	public static function removeAdFromDeal($adId, $dealId)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$adId)
			throw new ApiException(['wrong_ad'], 400);

		if (!$dealId)
			throw new ApiException(['wrong_deal'], 400);

		$deal = Deal::getById($dealId);
		if (!$deal)
			throw new ApiException(['deal_not_found'], 400);

		if ($userId != $deal['BUYER'])
			throw new ApiException(['not_your_deal'], 400);

		if (!in_array($adId, $deal['AD']))
			throw new ApiException(['not_in_deal'], 400);

		if (count($deal['AD']) < 2)
			throw new ApiException(['last_ad'], 400);

		$ads = [];
		foreach ($deal['AD'] as $id)
		{
			if ($id != $adId)
				$ads[] = $id;
		}
		Deal::update($dealId, ['AD' => $ads]);
		Ad::updateCanBuy($adId, true);

		return [
			'id' => $dealId,
		];
	}

	/**
	 * Изменение статуса сделки
	 * @param $dealId
	 * @param $statusCode
	 * @param bool $price цена доставки
	 * @param string $track код посылки
	 * @return array|mixed
	 * @throws ApiException
	 */
	public static function updateDealStatus($dealId, $statusCode, $price = false, $track = '')
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$dealId)
			throw new ApiException(['wrong_deal'], 400);

		$deal = Deal::getById($dealId);
		if (!$deal)
			throw new ApiException(['deal_not_found'], 400);

		if (!$statusCode)
			throw new ApiException(['wrong_status'], 400);

		$status = Status::getByCode($statusCode);
		if (!$status)
			throw new ApiException(['wrong_status'], 400);

		if ($deal['STATUS'] == $statusCode)
			throw new ApiException(['wrong_status'], 400);

		$role = 0;
		if ($userId == $deal['SELLER'])
			$role = 1; // продавец
		elseif ($userId == $deal['BUYER'])
			$role = 2; // покупатель

		if (!$role)
			throw new ApiException(['not_your_deal'], 400);

		if (!$deal['ALLOWED']['status'][$role][$statusCode])
			throw new ApiException(['update_not_allowed'], 400);

		$update = [
			'STATUS' => $status['ID'],
		    'STATUS_TS' => time(),
		];
		if ($statusCode == 'price' && isset($price) && $price !== false)
			$update['DELIVERY_PRICE'] = $price;
		if ($statusCode == 'send' && isset($track) && $track !== '')
			$update['TRACK'] = $track;

		Deal::update($dealId, $update);
		History::add($dealId, $status['ID'], $userId);

		$pushUser = $role == 1 ? $deal['BUYER'] : $deal['SELLER'];
		$pushRole = $role == 1 ? 'buyer' : 'seller';
		$user = User::getById($userId);
		$text = 'Изменился статус вашей сделки';
		if ($statusCode == 'cancel')
			$text = '"' . $user['nickname'] . '" отменил сделку';
		elseif ($statusCode == 'send')
			$text = 'подтвердите полуечение товара от "' . $user['nickname'] . '"';

		User::push(
			$pushUser,
			$text,
			['type' => 'deal_status', 'dealId' => intval($dealId), 'role' => $pushRole]
		);

		$deal = Deal::format($dealId, $role);

		return $deal;
	}

	/**
	 * Обновляет код посылки
	 * @param $dealId
	 * @param $track
	 * @return array|mixed
	 * @throws ApiException
	 */
	public static function addTrack($dealId, $track)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$dealId)
			throw new ApiException(['wrong_deal'], 400);

		$deal = Deal::getById($dealId);
		if (!$deal)
			throw new ApiException(['deal_not_found'], 400);

		if ($userId != $deal['SELLER'])
			throw new ApiException(['not_your_deal'], 400);

		$update = [
			'TRACK' => $track,
		];
		$deal = Deal::update($dealId, $update);

		return $deal;
	}

	/**
	 * Отправляет запрос к API почты
	 * @param $dealId
	 * @return array
	 * @throws ApiException
	 */
	public static function trackDeal($dealId)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$dealId)
			throw new ApiException(['wrong_deal'], 400);

		$deal = Deal::getById($dealId);
		if (!$deal)
			throw new ApiException(['deal_not_found'], 400);

		$role = 0;
		if ($userId == $deal['SELLER'])
			$role = 1; // продавец
		elseif ($userId == $deal['BUYER'])
			$role = 2; // покупатель

		if (!$role)
			throw new ApiException(['not_your_deal'], 400);

		return Deal::track($dealId);
	}

	/**
	 * Возвращает сделки пользователя
	 * @param $type
	 * @param $params
	 * @return array
	 * @throws ApiException
	 */
	public static function getMyDeals($type, $params)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		$role = 0;
		if ($type == 'sell')
			$role = 1;
		elseif ($type == 'buy')
			$role = 2;

		if (!$role)
			throw new ApiException(['wrong_endpoint'], 404);

		$return = Deal::getByUserFormatted($userId, $role, $params);

		return $return;
	}

	/**
	 * Возвращает данные для страницы "Мои объявления" (включает данные пользователя, объявления и сделки)
	 * @param $sub
	 * @param array $params
	 * @return mixed
	 * @throws ApiException
	 */
	public static function getMyAds($sub, $params = [])
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if ($sub == 'deals')
		{
			$params['history'] = 'Y';
			$deals = Deal::getByUserFormatted($userId, 1, $params);
			return $deals;
		}
		elseif ($sub == 'ads')
		{
			$ads = Ad::getListByUser($userId, $params, true);
			return $ads;
		}
		else
		{
			$profile = self::profile();
			$profile['share_count'] = News::getShareCount($userId);
			$profile['ads_count'] = Ad::getCountByUser($userId);
			$profile['ads'] = Ad::getListByUser($userId, [], true);
			$profile['deals'] = Deal::getByUserFormatted($userId, 1, ['history' => 'Y']);
			return $profile;
		}
	}

	/**
	 * Добавляет сообщение в чат со службой поддержки
	 * @param $userId
	 * @param $message
	 * @return array
	 * @throws ApiException
	 */
	public static function message($userId, $message)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		if (!$userId)
		{
			$session = Auth::check();
			$userId = $session['USER_ID'];
		}

		$message = htmlspecialchars(trim($message));
		if (!$message)
			throw new ApiException(['empty_message'], 400);

		$key = 'u' . '|' . $userId;
		$id = Messages::add($key, $userId, $message);

		self::updateChatInfo($userId);

		return [
			'oid' => $userId,
			'ot' => 'u',
			'id' => $id,
		    'push' => 0,
		    'role' => 1,
		    'suffix' => 0,
		    'users' => [0, $userId],
		];
	}

	/**
	 * Возвращает сообщения чата со службой поддержки
	 * @param array $params
	 * @return array
	 * @throws ApiException
	 */
	public static function chat($params = [])
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];
		$key = 'u' . '|' . $userId;

		$return = [];
		$messages = Messages::getByKey($key, $params);
		foreach ($messages as $message)
		{
			$return[] = [
				'id' => $message['ID'],
				'message' => $message['MESSAGE'],
				'user' => $message['USER'],
				'date' => date('c', MakeTimeStamp($message['DATE'])),
			];
		}

		return $return;
	}

	/**
	 * Обновляет поля чата, для работы службы поддержки
	 * @param $userId
	 * @param $isSupport
	 */
	public static function updateChatInfo($userId, $isSupport = false)
	{
		$iblockElement = new \CIBlockElement();
		$fields = [
			'SORT' => $isSupport ? 560 : 555,
		    'XML_ID' => time(),
		];
		$iblockElement->Update($userId, $fields);
	}

	/**
	 * Отправка пуш сообщения всем сессиям пользователя
	 * @param $userId
	 * @param $message
	 * @param array $add
	 */
	public static function push($userId, $message, $add = [])
	{
		if (strlen($message) > 80)
			$message = substr($message, 0, 80) . '...';

		_log_array('-------------------------- Пуш для пользователя: ' . $userId . '   ' . $message);

		$sessions = Session::getByUser($userId);
		foreach ($sessions as $session)
			Push::message($session['PUSH'], $message, $add);
	}

}