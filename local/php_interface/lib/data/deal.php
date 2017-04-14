<?

namespace Local\Data;

use Local\Api\ApiException;
use Local\Catalog\Delivery;
use Local\Catalog\Payment;
use Local\Common\ExtCache;
use Local\Common\Tracking;
use Local\Common\Utils;
use Local\User\Auth;
use Local\User\User;

/**
 * Class Deal Сделки
 * @package Local\Data
 */
class Deal
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/Deal/';

	/**
	 * Количество сделок за раз
	 */
	const DEFAULT_COUNT = 10;

	/**
	 * Проверка товара
	 */
	const CHECK_ID = 18;

	/**
	 * Добавление сделки
	 * @param $adIds
	 * @param $name
	 * @param $userId
	 * @param $sellerId
	 * @param $paymentCode
	 * @param $deliveryCode
	 * @param $deliveryPrice
	 * @param $check
	 * @param $address
	 * @return bool
	 * @throws ApiException
	 */
	public static function add($adIds, $name, $userId, $sellerId, $paymentCode,
	                           $deliveryCode, $deliveryPrice, $check, $address)
	{
		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('deal');
		$status = Status::getByCode('new');
		$payment = Payment::getByCode($paymentCode);
		$delivery = Delivery::getByCode($deliveryCode);
		$price = 0;
		foreach ($adIds as $adId)
		{
			$ad = Ad::getById($adId);
			$price += $ad['PRICE'];
		}
		$checkPrice = 0;
		if ($check)
			$checkPrice = self::getCheckPrice($price);
		$id = $iblockElement->Add(array(
			'IBLOCK_ID' => $iblockId,
			'NAME' => $name,
			'PROPERTY_VALUES' => array(
				'AD' => $adIds,
				'SELLER' => $sellerId,
				'BUYER' => $userId,
				'STATUS' => $status['ID'],
				'STATUS_TS' => time(),
				'PAYMENT' => $payment['ID'],
				'DELIVERY' => $delivery['ID'],
				'DELIVERY_PRICE' => $deliveryPrice,
				'CHECK' => $check ? 18 : false,
				'CHECK_PRICE' => $checkPrice,
				'ADDRESS' => $address,
				'BUYER_RATING' => 0,
				'SELLER_RATING' => 0,
			),
		));
		if (!$id)
			throw new ApiException(['deal_add_error'], 500, $iblockElement->LAST_ERROR);

		// Всем объявлениям устанавливаем can_buy = false
		foreach ($adIds as $adId)
			Ad::updateCanBuy($adId, false);

		// сбрасываем кеш
		self::clearUserCache($userId);
		self::clearUserCache($sellerId);

		History::add($id, $status['ID'], $userId);

		$user = User::getById($userId);
		User::push(
			$sellerId,
			'Ваш товар хочет купить "' . $user['nickname'] . '"!',
			array('type' => 'new_deal', 'dealId' => intval($id), 'role' => 'seller')
		);

		return $id;
	}

	/**
	 * Возвращает стоимость услуги "Проверка товара"
	 * @param $price int цена товара
	 * @return int|mixed
	 */
	private static function getCheckPrice($price)
	{
		$checkPrice = Options::get('check_price');
		$checkPoint = Options::get('check_free_point');
		if ($price >= $checkPoint)
			$checkPrice = 0;
		return $checkPrice;
	}

	/**
	 * Возвращает все сделки заданного объявления
	 * @param $adId
	 * @param bool $refreshCache
	 * @return array|mixed
	 */
	/*public static function getByAd($adId, $refreshCache = false)
	{
		$return = array();
		$adId = intval($adId);

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$adId,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20,
			false // не используем теговый кеш, чтоб не удалять кеш при измененях в сделках
		);
		if (!$refreshCache && $extCache->initCache())
		{
			$return = $extCache->getVars();
		}
		else
		{
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('deal');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array('ID' => 'DESC'), array(
					'IBLOCK_ID' => $iblockId,
					'=PROPERTY_AD' => $adId,
				), false, false, array(
					'ID',
					'PROPERTY_STATUS',
				));
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$status = Status::getCodeById($item['PROPERTY_STATUS_ENUM_ID']);
				$return['by_status'][$status][] = $id;
				if ($status != 'cancel')
					$return['current'] = $id;
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}*/

	/**
	 * Возвращает все сделки пользователя
	 * @param $userId
	 * @param $role
	 * @param $params
	 * @param bool $refreshCache
	 * @return array|mixed
	 */
	public static function getByUser($userId, $role, $params, $refreshCache = false)
	{
		$return = array();
		$userId = intval($userId);

		$filter = array();
		$count = self::DEFAULT_COUNT;
		if (isset($params['status']))
		{
			$status = Status::getByCode($params['status']);
			if ($status && intval($params['max']) > 0)
			{
				$filter[] = array(
					'LOGIC' => 'OR',
					'>PROPERTY_STATUS' => $status['ID'],
					array(
						'=PROPERTY_STATUS' => $status['ID'],
						'<ID' => intval($params['max']),
					),
				);
			}
		}
		elseif (intval($params['max']) > 0)
			$filter['<ID'] = intval($params['max']);
		if ($params['history'] == 'Y')
		{
			$status = Status::getByCode('complete');
			if ($status)
				$filter['=PROPERTY_STATUS'] = $status['ID'];
		}

		if (intval($params['count']) > 0)
			$count = intval($params['count']);

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$userId,
				$role,
				$filter,
				$count,
			),
			static::CACHE_PATH . __FUNCTION__ . '/' . $userId . '/',
			86400 * 20,
			false // не используем теговый кеш, чтоб не удалять кеш при измененях в сделках
		);
		if (!$refreshCache && $extCache->initCache())
		{
			$return = $extCache->getVars();
		}
		else
		{
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('deal');
			$filter['IBLOCK_ID'] = $iblockId;
			if ($role == 1)
				$filter['=PROPERTY_SELLER'] = $userId;
			elseif ($role == 2)
				$filter['=PROPERTY_BUYER'] = $userId;

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array(
					'PROPERTY_STATUS' => 'ASC',
					'ID' => 'DESC'
				), $filter, false, array('nTopCount' => $count), array(
					'ID',
				));
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
	 * Возвращает сделки пользователя
	 * @param $userId
	 * @param $role int 1-продавец, 2-покупатель
	 * @param array $params
	 * @return array
	 */
	public static function getByUserFormatted($userId, $role, $params = array())
	{
		$return = array();

		$dealsIds = self::getByUser($userId, $role, $params);
		foreach ($dealsIds as $dealId)
			$return[] = Deal::format($dealId, $role);

		return $return;
	}

	/**
	 * Возвращает сделку по ID
	 * @param $dealId
	 * @param bool $refreshCache
	 * @return array|mixed
	 */
	public static function getById($dealId, $refreshCache = false)
	{
		$return = array();
		$dealId = intval($dealId);

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$dealId,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20,
			false // не используем теговый кеш, чтоб не удалять кеш при измененях в сделках
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('deal');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(
				array('ID' => 'DESC'),
				array(
					'IBLOCK_ID' => $iblockId,
					'=ID' => $dealId,
				),
				false,
				false,
				array(
					'ID',
					'NAME',
					'DETAIL_TEXT',
					'PROPERTY_AD',
					'PROPERTY_SELLER',
					'PROPERTY_BUYER',
					'PROPERTY_STATUS',
					'PROPERTY_STATUS_TS',
					'PROPERTY_PAYMENT',
					'PROPERTY_DELIVERY',
					'PROPERTY_DELIVERY_PRICE',
					'PROPERTY_CHECK',
					'PROPERTY_CHECK_PRICE',
					'PROPERTY_ADDRESS',
					'PROPERTY_BUYER_RATING',
					'PROPERTY_BUYER_RATING_TEXT',
					'PROPERTY_SELLER_RATING',
					'PROPERTY_SELLER_RATING_TEXT',
					'PROPERTY_TRACK',
				)
			);
			while ($item = $rsItems->Fetch())
			{
				$return = array(
					'NAME' => $item['NAME'],
					'ID' => intval($item['ID']),
					'AD' => $item['PROPERTY_AD_VALUE'],
					'SELLER' => intval($item['PROPERTY_SELLER_VALUE']),
					'BUYER' => intval($item['PROPERTY_BUYER_VALUE']),
					'STATUS' => Status::getCodeById($item['PROPERTY_STATUS_VALUE']),
					'STATUS_TS' => intval($item['PROPERTY_STATUS_TS_VALUE']),
				    'PAYMENT' => intval($item['PROPERTY_PAYMENT_VALUE']),
				    'DELIVERY' => intval($item['PROPERTY_DELIVERY_VALUE']),
				    'DELIVERY_PRICE' => intval($item['PROPERTY_DELIVERY_PRICE_VALUE']),
				    'CHECK' => intval($item['PROPERTY_CHECK_VALUE']) ? true : false,
				    'CHECK_PRICE' => intval($item['PROPERTY_CHECK_PRICE_VALUE']),
				    'ADDRESS' => $item['PROPERTY_ADDRESS_VALUE'],
				    'BUYER_RATING' => intval($item['PROPERTY_BUYER_RATING_VALUE']),
				    'BUYER_RATING_TEXT' => $item['PROPERTY_BUYER_RATING_TEXT_VALUE'],
				    'SELLER_RATING' => intval($item['PROPERTY_SELLER_RATING_VALUE']),
				    'SELLER_RATING_TEXT' => $item['PROPERTY_SELLER_RATING_TEXT_VALUE'],
				    'TRACK' => $item['PROPERTY_TRACK_VALUE'],
				    'DETAIL_TEXT' => $item['DETAIL_TEXT'],
				    'HISTORY' => json_decode($item['DETAIL_TEXT']),
				);
				$return['ALLOWED'] = self::getAllowedUpdates($return);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает сделку в формате для отправки в приложение
	 * @param $dealId
	 * @param $role
	 * @return array
	 */
	public static function format($dealId, $role)
	{
		$deal = self::getById($dealId);
		$ads = array();
		foreach ($deal['AD'] as $adId)
			$ads[] = Ad::shortById($adId);
		$return = array(
			'id' => $deal['ID'],
			'ads' => $ads,
			'seller' => User::publicProfile($deal['SELLER']),
			'buyer' => User::publicProfile($deal['BUYER']),
			'status' => $deal['STATUS'],
			'last_status_date' => date('c', MakeTimeStamp($deal['STATUS_TS'])),
			'payment' => Payment::getCodeById($deal['PAYMENT']),
			'delivery' => Delivery::getCodeById($deal['DELIVERY']),
			'delivery_price' => $deal['DELIVERY_PRICE'],
			'check' => $deal['CHECK'],
			'check_price' => $deal['CHECK_PRICE'],
			'address' => $deal['ADDRESS'],
			'rating' => array(
				'seller' => $deal['SELLER_RATING'],
				'seller_text' => $deal['SELLER_RATING_TEXT'],
				'buyer' => $deal['BUYER_RATING'],
				'buyer_text' => $deal['BUYER_RATING_TEXT'],
			),
		    'allowed' => array(
			    'status' => array_keys($deal['ALLOWED']['status'][$role]),
			    'support' => $deal['ALLOWED']['support'][$role],
		    ),
		    'track' => $deal['TRACK'],
		);

		return $return;
	}

	/**
	 * Возвращает статусы, в которые можно перевести сделку, и кто из пользователей может обратиться в службу поддержки
	 * @param $deal
	 * @return array
	 */
	public static function getAllowedUpdates($deal)
	{
		$status = $deal['STATUS'];
		$payment = Payment::getCodeById($deal['PAYMENT']);
		$delivery = Delivery::getCodeById($deal['DELIVERY']);

		$return = array(
			'status' => array(
				1 => array(),
				2 => array(),
				3 => array(),
			),
			'support' => array(
				1 => false,
				2 => false,
			),
		);

		if ($payment == 'application')
		{
			if ($status == 'new')
			{
				// Ответвление на согласование доставки
				if ($delivery != 'personal')
					$return['status'][1]['price'] = true;
				$return['status'][1]['confirm'] = true;
				$return['status'][1]['cancel'] = true;
				$return['status'][2]['cancel'] = true;
			}
			// Ветвь "Согласование доставки"
			elseif ($status == 'price')
			{
				$return['status'][1]['cancel'] = true;
				$return['status'][2]['confirm'] = true;
				$return['status'][2]['cancel'] = true;
			}
			elseif ($status == 'confirm')
			{
				// Если выбрана "Проверка товара", нужно отправлять товар на проверку
				$next = ($deal['CHECK'] && $delivery != 'personal') ? 'check' : 'send';
				$return['status'][1][$next] = true;
				$return['status'][1]['cancel'] = true;
				$return['status'][3]['cancel'] = true;
				$return['support'][2] = true;
			}
			elseif ($status == 'check')
			{
				$return['status'][3]['send'] = true;
				$return['status'][3]['cancel'] = true;
				$return['support'][1] = true;
				$return['support'][2] = true;
			}
			elseif ($status == 'send')
			{
				$return['status'][2]['complete'] = true;
				$return['status'][3]['complete'] = true;
				$return['status'][3]['cancel'] = true;
				$return['support'][1] = true;
				$return['support'][2] = true;
			}
		}
		elseif ($payment == 'agreement' && $delivery == 'personal')
		{
			if ($status == 'new')
			{
				$return['status'][1]['send'] = true;
				$return['status'][1]['cancel'] = true;
				$return['status'][2]['cancel'] = true;
			}
			elseif ($status == 'send')
			{
				$return['status'][1]['cancel'] = true;
				$return['status'][2]['complete'] = true;
				$return['status'][2]['cancel'] = true;
			}
		}

		return $return;
	}

	/**
	 * Обновляет сделку
	 * @param $dealId
	 * @param $update
	 * @return array|mixed
	 */
	public static function update($dealId, $update)
	{
		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('deal');

		$iblockElement->SetPropertyValuesEx($dealId, $iblockId, $update);
		$deal = self::getById($dealId, true);
		// сбрасываем кеш
		self::clearUserCache($deal['SELLER']);
		self::clearUserCache($deal['BUYER']);

		$can_buy = $deal['STATUS'] == 'cancel';
		foreach ($deal['AD'] as $adId)
		{
			$ad = Ad::getById($adId);
			if ($ad['CAN_BUY'] != $can_buy)
				Ad::updateCanBuy($ad['ID'], $can_buy);
		}

		return $deal;
	}

	public static function detail($dealId)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$dealId)
			throw new ApiException(['wrong_deal'], 400);

		$deal = self::getById($dealId);
		if (!$deal)
			throw new ApiException(['deal_not_found'], 400);

		$role = 0;
		if ($userId == $deal['SELLER'])
			$role = 1; // продавец
		elseif ($userId == $deal['BUYER'])
			$role = 2; // покупатель

		if (!$role)
			throw new ApiException(['not_your_deal'], 400);

		$deal = self::format($dealId, $role);
		$deal['history'] = History::get($dealId);

		return $deal;
	}

	/**
	 * Отправляет запрос в API почты
	 * @param $dealId
	 * @return array
	 * @throws ApiException
	 */
	public static function track($dealId)
	{
		$deal = self::getById($dealId);
		if (!$deal['TRACK'])
			throw new ApiException(['not_track_number'], 400);

		return Tracking::track($deal['TRACK']);
	}

	/**
	 * Обновляет поля чата, для работы службы поддержки
	 * @param $dealId
	 * @param $isSupport
	 */
	public static function updateChatInfo($dealId, $isSupport = false)
	{
		$iblockElement = new \CIBlockElement();
		$fields = array(
			'SORT' => $isSupport ? 560 : 555,
			'XML_ID' => time(),
		);
		$iblockElement->Update($dealId, $fields);
	}

	/**
	 * Обновляет только время последнего сообщения в чате
	 * @param $dealId
	 */
	public static function updateChatXmlId($dealId)
	{
		$iblockElement = new \CIBlockElement();
		$fields = array(
			'XML_ID' => time(),
		);
		$iblockElement->Update($dealId, $fields);
	}

	/**
	 * Добавляет сообщение в чат сделки
	 * @param $userId
	 * @param $dealId
	 * @param $message
	 * @param $support bool true - сообщение в службу поддержки
	 * @return array
	 * @throws ApiException
	 */
	public static function message($userId, $dealId, $message, $support)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		if (!$userId)
		{
			$session = Auth::check();
			$userId = $session['USER_ID'];
		}

		if (!$dealId)
			throw new ApiException(['wrong_deal'], 400);

		$deal = self::getById($dealId);
		if (!$deal)
			throw new ApiException(['deal_not_found'], 400);

		$role = 0;
		$pushUser = 0;
		$pushRole = '';
		if ($userId == $deal['SELLER'])
		{
			$role = 1; // продавец
			$pushUser = $deal['BUYER'];
			$pushRole = 'buyer';
		}
		elseif ($userId == $deal['BUYER'])
		{
			$role = 2; // покупатель
			$pushUser = $deal['SELLER'];
			$pushRole = 'seller';
		}

		if (!$role)
			throw new ApiException(['not_your_deal'], 400);

		$message = htmlspecialchars(trim($message));
		if (!$message)
			throw new ApiException(['empty_message'], 400);

		if ($support)
			self::updateChatInfo($dealId);
		else
		{
			self::updateChatXmlId($dealId);
			$pUser = User::getById($pushUser);
			User::push(
				$pushUser,
				$pUser['nickname'] . ': ' . $message,
				array('type' => 'deal_message', 'dealId' => intval($dealId), 'role' => $pushRole)
			);
		}

		$suffix = !$support ? 0 : $role;
		$key = 'd' . '|' . $dealId . '|' . $suffix;
		$id = Messages::add($key, $userId, $message);

		return array(
			'id' => $id,
			'role' => $role,
			'suffix' => $suffix,
		    'push' => $support ? 0 : $pushUser,
		    'users' => array(0, $deal['SELLER'], $deal['BUYER']),
		);
	}

	/**
	 * Возвращает сообщения чата сделки
	 * @param $dealId
	 * @param $support
	 * @param array $params
	 * @return array
	 * @throws ApiException
	 */
	public static function chat($dealId, $support, $params = array())
	{
		if (!$dealId)
			throw new ApiException(['wrong_deal'], 400);

		$deal = self::getById($dealId);
		if (!$deal)
			throw new ApiException(['deal_not_found'], 400);

		if ($support)
		{
			// Проверяем авторизацию (выкинет исключение, если неавторизован)
			$session = Auth::check();
			$userId = $session['USER_ID'];

			$role = 0;
			if ($userId == $deal['SELLER'])
				$role = 1; // продавец
			elseif ($userId == $deal['BUYER'])
				$role = 2; // покупатель

			if (!$role)
				throw new ApiException(['not_your_deal'], 400);

			$suffix = $role;
		}
		else
			$suffix = 0;

		$key = 'd' . '|' . $dealId . '|' . $suffix;

		$return = array();
		$messages = Messages::getByKey($key, $params);
		foreach ($messages as $message)
		{
			$return[] = array(
				'id' => $message['ID'],
				'message' => $message['MESSAGE'],
			    'user' => $message['USER'],
			    'date' => date('c', MakeTimeStamp($message['DATE'])),
			);
		}

		return $return;
	}

	/**
	 * Сохраняет оценку пользователя сделки
	 * @param $dealId
	 * @param $rating
	 * @param $text
	 * @return array
	 * @throws ApiException
	 */
	public static function rating($dealId, $rating, $text)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$dealId)
			throw new ApiException(['wrong_deal'], 400);

		$deal = self::getById($dealId);
		if (!$deal)
			throw new ApiException(['deal_not_found'], 400);

		$role = 0;
		if ($userId == $deal['SELLER'])
			$role = 1; // продавец
		elseif ($userId == $deal['BUYER'])
			$role = 2; // покупатель

		if (!$role)
			throw new ApiException(['not_your_deal'], 400);

		$rating = intval($rating);
		if ($rating < 1)
			$rating = 1;
		if ($rating > 5)
			$rating = 5;

		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('deal');

		$key = $role == 1 ? 'SELLER_RATING' : 'BUYER_RATING';
		$update = array(
			$key => $rating,
		    $key . '_TEXT' => $text,
		);

		$iblockElement->SetPropertyValuesEx($dealId, $iblockId, $update);
		self::getById($dealId, true);

		return array(
			'id' => $dealId,
		);
	}

	/**
	 * Очищает кеш сделок для пользователя
	 * @param $userId
	 */
	private static function clearUserCache($userId)
	{
		$phpCache = new \CPHPCache();
		$phpCache->CleanDir(static::CACHE_PATH . 'getByUser/' . $userId);
	}
}