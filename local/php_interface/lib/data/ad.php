<?

namespace Local\Data;

use Local\Catalog\Delivery;
use Local\Catalog\Gender;
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
use Local\User\Favorite;
use Local\User\User;

// TODO: добавить индексы в БД

/**
 * Class Ad Объявления (товары)
 * @package Local\Data
 */
class Ad
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/Ad/';

	/**
	 * Количество объявлений за раз
	 */
	const DEFAULT_COUNT = 10;

	/**
	 * Можно купить
	 */
	const CAN_BUY_ID = 20;

	/**
	 * Проверка параметров перед созданием или изменением объявления
	 * @param $params
	 * @return array
	 * @throws ApiException
	 */
	private static function checkParams($params)
	{
		$errors = array();
		$props = array();

		// Раздел каталога
		$props['CATEGORY'] = intval($params['section']);
		$section = Catalog::getSectionById($props['CATEGORY']);
		if (!$section || $section['ACTIVE'] != 'Y')
			$errors[] = 'wrong_section';

		// Бренд
		$props['BRAND'] = intval($params['brand']);
		$brand = Brand::getById($props['BRAND']);
		if (!$brand || $brand['ACTIVE'] != 'Y')
			$errors[] = 'wrong_brand';

		// Половой признак
		if ($params['gender'])
		{
			$gender = Gender::getByCode($params['gender']);
			if (!$gender)
				$errors[] = 'wrong_gender';
			else
				$props['GENDER'] = $gender['ID'];
		}

		// Состояние
		$props['CONDITION'] = intval($params['condition']);
		$condition = Condition::getById($props['CONDITION']);
		if (!$condition || $condition['ACTIVE'] != 'Y')
			$errors[] = 'wrong_condition';

		// Цвет
		$props['COLOR'] = intval($params['color']);
		$color = Color::getById($props['COLOR']);
		if (!$color || $color['ACTIVE'] != 'Y')
			$errors[] = 'wrong_color';

		// Размер
		$props['SIZE'] = intval($params['size']);
		if ($section && $props['SIZE'])
		{
			$size = Size::getBySectionAndId($props['CATEGORY'], $props['SIZE']);
			if (!$size || $size['ACTIVE'] != 'Y')
				$errors[] = 'wrong_size';
		}

		// Материал
		$props['MATERIAL'] = htmlspecialchars(trim($params['material']));

		// Особенности и комментарии
		$props['FEATURES'] = htmlspecialchars(trim($params['features']));
		if (!$props['FEATURES'])
			$errors[] = 'empty_features';

		// Цены
		$props['PURCHASE'] = intval($params['purchase']);
		if ($props['PURCHASE'] < 0)
			$errors[] = 'wrong_purchase';
		$props['PRICE'] = intval($params['price']);
		if ($props['PRICE'] <= 0)
			$errors[] = 'wrong_price';

		// Способы оплаты
		$paymentIds = array();
		$paymentAgreement = false; // содержит ли оплату "По договоренности"
		$paymentError = false;
		foreach ($params['payment'] as $code)
		{
			$arPayment = Payment::getByCode($code);
			if ($arPayment)
			{
				$paymentIds[] = $arPayment['ID'];
				if ($code == 'agreement')
					$paymentAgreement = true;
			}
			else
				$paymentError = true;
		}
		sort($paymentIds);
		if ($paymentError || !$paymentIds)
			$errors[] = 'wrong_paymemt';
		$props['PAYMENT'] = $paymentIds;

		// Способы отправки
		$deliveryIds = array();
		$deliveryError = false;
		$deliveryPersonal = false; // присутствует ли в доставках личная встреча
		foreach ($params['delivery'] as $code => $p)
		{
			$arDelivery = Delivery::getByCode($code);
			if ($arDelivery)
			{
				$deliveryIds[] = $arDelivery['ID'];
				if ($code == 'personal')
					$deliveryPersonal = true;
			}
			else
				$deliveryError = true;
		}
		sort($deliveryIds);
		if ($deliveryError || !$deliveryIds)
			$errors[] = 'wrong_delivery';
		$props['DELIVERY'] = $deliveryIds;
		$props['DELIVERY_PRICES'] = json_encode($params['delivery']);

		// Если оплата только по договоренности и доставка не содержит личную встречу
		if ($paymentAgreement && count($paymentIds) == 1 && !$deliveryPersonal)
			$errors[] = 'wrong_payment_delivery';

		// Исключение, если ошибки в параметрах
		if ($errors)
			throw new ApiException($errors, 400);

		$props['NAME'] = $section['NAME'] . ' ' . $brand['NAME'];
		$props['CAN_BUY'] = self::CAN_BUY_ID;

		return $props;
	}

	/**
	 * Добавляет объявление
	 * @param $params
	 * @return array
	 * @throws ApiException
	 */
	public static function add($params)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		$props = self::checkParams($params);

		$fileIds = array();
		foreach ($_FILES as $file)
			$fileIds[] = $file;
		if (!$fileIds)
			throw new ApiException(['least_one_photo_needed'], 400);
		$props['PHOTO'] = $fileIds;

		$props['USER'] = $userId;
		$name = $props['NAME'];
		unset($props['NAME']);

		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('ad');
		$id = $iblockElement->Add(array(
			'IBLOCK_ID' => $iblockId,
			'NAME' => $name,
			'PROPERTY_VALUES' => $props,
		));
		if (!$id)
			throw new ApiException(['ad_add_error'], 500, $iblockElement->LAST_ERROR);

		// обновляем кеш
		self::getCountByUser($userId, true);
		self::clearListCache();

		// Добавляем объявление в ленту
		Feed::addAd($id, $name);

		return array(
			'id' => $id,
		);
	}

	/**
	 * Обновляет объявление
	 * @param $adId
	 * @param $params
	 * @return array
	 * @throws ApiException
	 */
	public static function update($adId, $params)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$adId)
			throw new ApiException(['wrong_ad'], 400);

		$ad = self::getById($adId);
		if (!$ad)
			throw new ApiException(['ad_not_found'], 400);

		if ($ad['USER'] != $userId)
			throw new ApiException(['not_your_ad'], 400);

		if (!$ad['CAN_BUY'])
			throw new ApiException(['ad_with_deal'], 400);

		$props = self::checkParams($params);

		$fileIds = array();
		foreach ($_FILES as $file)
			$fileIds[] = $file;
		if ($fileIds)
			$props['PHOTO'] = $fileIds;

		$name = $props['NAME'];
		unset($props['NAME']);

		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('ad');
		$refreshCache = false;

		if ($name != $ad['name'])
		{
			$res = $iblockElement->Update($adId, array('NAME' => $name));
			if (!$res)
				throw new ApiException(['ad_update_error'], 500, $iblockElement->LAST_ERROR);
			$refreshCache = true;
		}

		$update = array();
		foreach ($props as $code => $value)
		{
			if ($ad[$code] != $value)
				$update[$code] = $value;
		}
		if ($update)
		{
			$iblockElement->SetPropertyValuesEx($adId, $iblockId, $update);
			$refreshCache = true;
		}

		if ($refreshCache)
		{
			// обновляем кеш
			self::getById($adId, true);
			self::clearListCache();
		}

		return array(
			'id' => $adId,
		);
	}

	/**
	 * Обновляет свойство "Можно купить" в объявлении
	 * @param $adId
	 * @param $can_buy
	 * @return mixed
	 */
	public static function updateCanBuy($adId, $can_buy)
	{
		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('ad');
		$value = $can_buy ? self::CAN_BUY_ID : false;
		$iblockElement->SetPropertyValuesEx($adId, $iblockId, array('CAN_BUY' => $value));

		// обновляем кеш
		self::clearListCache();
		self::getById($adId, true);

		return $adId;
	}

	/**
	 * Удаление объявления
	 * @param $adId
	 * @return array
	 * @throws ApiException
	 */
	public static function delete($adId)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$adId)
			throw new ApiException(['wrong_ad'], 400);

		$ad = self::getById($adId);
		if (!$ad)
			throw new ApiException(['ad_not_found'], 400);

		if ($ad['USER'] != $userId)
			throw new ApiException(['not_your_ad'], 400);

		if (!$ad['CAN_BUY'])
			throw new ApiException(['ad_with_deal'], 400);

		$iblockElement = new \CIBlockElement();
		$res = $iblockElement->Update($adId, array('ACTIVE' => 'N'));
		if (!$res)
			throw new ApiException(['ad_delete_error'], 500, $iblockElement->LAST_ERROR);

		// обновляем кеш
		self::getById($adId, true);
		self::getCountByUser($userId, true);
		self::clearListCache();

		return array(
			'id' => $adId,
		);
	}

	/**
	 * Возвращает объявление по ID
	 * @param $id
	 * @param bool $refreshCache
	 * @return array|mixed
	 */
	public static function getById($id, $refreshCache = false) {
		$id = intval($id);

		$return = array();
		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$id,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20,
			false // не используем теговый кеш, чтоб не удалять кеш при измененях в объявлениях
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('ad');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array(), array(
				'IBLOCK_ID' => $iblockId,
				'ID' => $id,
				'ACTIVE' => 'Y',
			), false, false, array(
				'ID', 'IBLOCK_ID', 'NAME',
				'PROPERTY_USER',
				'PROPERTY_CATEGORY',
				'PROPERTY_BRAND',
				'PROPERTY_GENDER',
				'PROPERTY_CONDITION',
				'PROPERTY_COLOR',
				'PROPERTY_SIZE',
				'PROPERTY_MATERIAL',
				'PROPERTY_FEATURES',
				'PROPERTY_PURCHASE',
				'PROPERTY_PRICE',
				'PROPERTY_PAYMENT',
				'PROPERTY_DELIVERY',
				'PROPERTY_DELIVERY_PRICES',
				'PROPERTY_PHOTO',
				'PROPERTY_CAN_BUY',
			));
			if ($item = $rsItems->Fetch())
			{
				$payment = $item['PROPERTY_PAYMENT_VALUE'];
				sort($payment);
				$delivery = $item['PROPERTY_DELIVERY_VALUE'];
				sort($delivery);
				$return = array(
					'ID' => intval($item['ID']),
					'NAME' => $item['NAME'],
					'USER' => intval($item['PROPERTY_USER_VALUE']),
					'CATEGORY' => intval($item['PROPERTY_CATEGORY_VALUE']),
					'BRAND' => intval($item['PROPERTY_BRAND_VALUE']),
					'GENDER' => intval($item['PROPERTY_GENDER_VALUE']),
					'CONDITION' => intval($item['PROPERTY_CONDITION_VALUE']),
					'COLOR' => intval($item['PROPERTY_COLOR_VALUE']),
					'SIZE' => intval($item['PROPERTY_SIZE_VALUE']),
					'MATERIAL' => $item['PROPERTY_MATERIAL_VALUE'],
					'FEATURES' => $item['PROPERTY_FEATURES_VALUE'],
					'PURCHASE' => intval($item['PROPERTY_PURCHASE_VALUE']),
					'PRICE' => intval($item['PROPERTY_PRICE_VALUE']),
					'PAYMENT' => $payment,
					'DELIVERY' => $delivery,
					'DELIVERY_PRICES' => $item['PROPERTY_DELIVERY_PRICES_VALUE'],
					'PHOTO' => $item['PROPERTY_PHOTO_VALUE'],
					'CAN_BUY' => intval($item['PROPERTY_CAN_BUY_ENUM_ID']) ? true : false,
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает массив объявления, в формате для отправки в приложение
	 * @param $adId
	 * @return array
	 */
	public static function shortById($adId)
	{
		$ad = self::getById($adId);
		return array(
			'id' => $ad['ID'],
			'name' => $ad['NAME'],
			'user' => User::publicProfile($ad['USER']),
			'color' => $ad['COLOR'],
			'size' => Size::getById($ad['SIZE'])['NAME'],
			'purchase' => $ad['PURCHASE'],
			'price' => $ad['PRICE'],
			'photo' => Utils::getFileArray(array_shift($ad['PHOTO'])),
			'can_buy' => $ad['CAN_BUY'],
		);
	}

	/**
	 * Возвращает ID объявлений с учетом параметров (фильтрации и пагинации)
	 * @param array $params
	 * @param bool $refreshCache
	 * @return array|mixed
	 */
	public static function getIds($params = array(), $refreshCache = false)
	{
		$return = array();

		$elementsFilter = array();
		$count = self::DEFAULT_COUNT;

		if (intval($params['max']) > 0)
			$elementsFilter['<ID'] = intval($params['max']);
		if (intval($params['count']) > 0)
			$count = intval($params['count']);
		if ($params['section'])
		{
			$filter = array();
			foreach ($params['section'] as $item)
			{
				$id = intval($item);
				if ($id > 0)
					$filter[$id] = $id;
			}
			sort($filter);
			$elementsFilter['=PROPERTY_CATEGORY'] = $filter;
		}
		if ($params['user'])
		{
			$filter = array();
			foreach ($params['user'] as $item)
			{
				$id = intval($item);
				if ($id > 0)
					$filter[$id] = $id;
			}
			sort($filter);
			$elementsFilter['=PROPERTY_USER'] = $filter;
		}
		if ($params['brand'])
		{
			$filter = array();
			foreach ($params['brand'] as $item)
			{
				$id = intval($item);
				if ($id > 0)
					$filter[$id] = $id;
			}
			sort($filter);
			$elementsFilter['=PROPERTY_BRAND'] = $filter;
		}
		if ($params['gender'])
		{
			$filter = array();
			foreach ($params['gender'] as $item)
			{
				$gender = Gender::getByCode($item);
				$id = $gender['ID'];
				if ($id > 0)
					$filter[$id] = $id;
			}
			sort($filter);
			$elementsFilter['=PROPERTY_GENDER'] = $filter;
		}
		if ($params['condition'])
		{
			$filter = array();
			foreach ($params['condition'] as $item)
			{
				$id = intval($item);
				if ($id > 0)
					$filter[$id] = $id;
			}
			sort($filter);
			$elementsFilter['=PROPERTY_CONDITION'] = $filter;
		}
		if ($params['color'])
		{
			$filter = array();
			foreach ($params['color'] as $item)
			{
				$id = intval($item);
				if ($id > 0)
					$filter[$id] = $id;
			}
			sort($filter);
			$elementsFilter['=PROPERTY_COLOR'] = $filter;
		}
		if ($params['size'])
			$elementsFilter['!PROPERTY_SIZE'] = Size::getExcludedSizes($params['size']);
		if (intval($params['price_from']) > 0)
			$elementsFilter['>=PROPERTY_PRICE'] = intval($params['price_from']);
		if (intval($params['price_to']) > 0)
			$elementsFilter['<=PROPERTY_PRICE'] = intval($params['price_to']);
		if (isset($params['payment']))
		{
			$payment = Payment::getByCode($params['payment']);
			if ($payment)
				$elementsFilter['=PROPERTY_PAYMENT'] = $payment['ID'];
		}
		if ($params['delivery'])
		{
			$filter = array();
			foreach ($params['delivery'] as $item)
			{
				$delivery = Delivery::getByCode($item);
				if ($delivery)
					$filter[$delivery['ID']] = $delivery['ID'];
			}
			sort($filter);
			$elementsFilter['=PROPERTY_DELIVERY'] = $filter;
		}
		if ($params['can_buy'] == 'Y')
			$elementsFilter['=PROPERTY_CAN_BUY'] = self::CAN_BUY_ID;
		if ($params['exclude'])
			$elementsFilter['!ID'] = $params['exclude'];

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$elementsFilter,
			    $count,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			7200,
			false
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('ad');
			$elementsFilter['IBLOCK_ID'] = $iblockId;
			$elementsFilter['ACTIVE'] = 'Y';

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(
				array('ID' => 'DESC'),
				$elementsFilter,
				false,
				array('nTopCount' => $count),
				array(
					'ID',
				)
			);
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$return[] = $id;
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает объявления с учетом параметров (фильтрации и пагинации)
	 * @param array $params
	 * @param bool $additional добавить данные по количеству комментариев и избранного
	 * @return array
	 */
	public static function getList($params = array(), $additional = false)
	{
		$return = array();

		$userId = Auth::getCurrentUserId();

		$ids = self::getIds($params);
		foreach ($ids as $id)
		{
			$ad = self::shortById($id);
			if ($additional)
			{
				$ad['additional'] = array(
					'comments' => Comments::getCountByAd($id),
					'favorites' => Favorite::getCountByAd($id),
				);
				if ($userId)
					$ad['additional']['my_favorite'] = Favorite::check($userId, $id);
			}
			$return[] = $ad;
		}

		return $return;
	}

	/**
	 * Возвращает только активные объявления указанного пользователя
	 * @param $userId
	 * @param array $params
	 * @param bool $additional
	 * @return array
	 */
	public static function getListByUser($userId, $params = array(), $additional = false)
	{
		$params['user'] = array($userId);
		$params['can_buy'] = 'Y';
		return self::getList($params, $additional);
	}

	/**
	 * Добавляет комментарий к объявлению
	 * @param $adId
	 * @param $message
	 * @return array
	 * @throws ApiException
	 */
	public static function comment($adId, $message)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$adId)
			throw new ApiException(['wrong_ad'], 400);

		$ad = self::getById($adId);
		if (!$ad)
			throw new ApiException(['ad_not_found'], 400);

		$message = htmlspecialchars(trim($message));
		if (!$message)
			throw new ApiException(['empty_message'], 400);

		$id = Comments::add($adId, $userId, $message);

		return array(
			'id' => $id,
		);
	}

	/**
	 * Возвращает комментарии объявления
	 * @param $adId
	 * @param array $params
	 * @return array
	 * @throws ApiException
	 */
	public static function comments($adId, $params = array())
	{
		if (!$adId)
			throw new ApiException(['wrong_ad'], 400);

		$ad = self::getById($adId);
		if (!$ad)
			throw new ApiException(['ad_not_found'], 400);

		$return = array();
		$comments = Comments::getByAd($adId, $params);
		foreach ($comments as $comment)
		{
			$return[] = array(
				'id' => $comment['ID'],
				'message' => $comment['MESSAGE'],
			    'user' => User::publicProfile($comment['USER']),
			);
		}

		return $return;
	}

	/**
	 * Объявление детально
	 * @param $adId
	 * @return array
	 * @throws ApiException
	 */
	public static function detail($adId)
	{
		if (!$adId)
			throw new ApiException(['wrong_ad'], 400);

		$ad = self::getById($adId);
		if (!$ad)
			throw new ApiException(['ad_not_found'], 400);

		$photo = array();
		foreach ($ad['PHOTO'] as $id)
			$photo[] = Utils::getFileArray($id);

		$add = array(
			'comments' => Comments::getCountByAd($adId),
			'favorites' => Favorite::getCountByAd($adId),
		);
		$userId = Auth::getCurrentUserId();
		if ($userId)
			$add['my_favorite'] = Favorite::check($userId, $adId);

		$return = array(
			'id' => intval($ad['ID']),
			'name' => $ad['NAME'],
			'user' => User::publicProfile($ad['USER']),
			'section' => $ad['CATEGORY'],
			'brand' => $ad['BRAND'],
			'gender' => Gender::getCodeById($ad['GENDER']),
			'condition' => $ad['CONDITION'],
			'color' => $ad['COLOR'],
			'size' => Size::getById($ad['SIZE'])['NAME'],
			'material' => $ad['MATERIAL'],
			'features' => $ad['FEATURES'],
			'purchase' => $ad['PURCHASE'],
			'price' => $ad['PRICE'],
			'payment' => Payment::format($ad['PAYMENT']),
			'delivery' => Delivery::format($ad['DELIVERY'], $ad['DELIVERY_PRICES']),
			'photo' => $photo,
			'can_buy' => $ad['CAN_BUY'],
		    'comments' => self::comments($adId),
		    'similar' => self::getList(array(
			    'section' => $ad['CATEGORY'],
			    'exclude' => $adId,
		    )),
		    'additional' => $add,
		);

		return $return;
	}

	/**
	 * Возвращает количество объявлений у пользователя
	 * @param $userId
	 * @param bool $refreshCache
	 * @return int|mixed
	 */
	public static function getCountByUser($userId, $refreshCache = false)
	{
		$userId = intval($userId);

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$userId,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			7200,
			false
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('ads');
			$iblockElement = new \CIBlockElement();
			$count = $iblockElement->GetList(
				array(),
				array(
					'IBLOCK_ID' => $iblockId,
					'=PROPERTY_USER' => $userId,
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
	 * Поделиться объявлением в приложении
	 * @param $adId
	 * @return array
	 * @throws ApiException
	 */
	public static function share($adId)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$adId)
			throw new ApiException(['wrong_ad'], 400);

		$ad = self::getById($adId);
		if (!$ad)
			throw new ApiException(['ad_not_found'], 400);

		if ($ad['USER'] == $userId)
			throw new ApiException(['self_ad'], 400);

		$id = Feed::addShare($adId, $userId, $ad['name']);

		if ($id)
			News::share('app', $userId, $adId);

		return array(
			'id' => $id,
		);
	}

	/**
	 * Поделиться объявлением в социальной сети
	 * @param $adId
	 * @param $name
	 * @return array
	 * @throws ApiException
	 */
	public static function socialShare($adId, $name)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$adId)
			throw new ApiException(['wrong_ad'], 400);

		$ad = self::getById($adId);
		if (!$ad)
			throw new ApiException(['ad_not_found'], 400);

		$id = News::share($name, $userId, $adId);

		return array(
			'id' => $id,
		);
	}

	/**
	 * Очищает кеш списка объявлений
	 */
	private static function clearListCache()
	{
		$phpCache = new \CPHPCache();
		$phpCache->CleanDir(static::CACHE_PATH . 'getIds');
	}
}