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

	public static function add($sectionId, $brandId, $conditionId, $colorId, $sizeId, $material, $features,
	                           $purchase, $price, $payment, $delivery) {

		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();

		$errors = array();

		// пользователь
		$userId = $session['USER_ID'];

		// Раздел каталога
		$sectionId = intval($sectionId);
		$section = Catalog::getSectionById($sectionId);
		if (!$section || $section['ACTIVE'] != 'Y')
			$errors[] = 'wrong_section';

		// Бренд
		$brandId = intval($brandId);
		$brand = Brand::getById($brandId);
		if (!$brand || $brand['ACTIVE'] != 'Y')
			$errors[] = 'wrong_brand';

		// Состояние
		$conditionId = intval($conditionId);
		$condition = Condition::getById($conditionId);
		if (!$condition || $condition['ACTIVE'] != 'Y')
			$errors[] = 'wrong_condition';

		// Цвет
		$colorId = intval($colorId);
		$color = Color::getById($colorId);
		if (!$color || $color['ACTIVE'] != 'Y')
			$errors[] = 'wrong_color';

		// Размер
		$sizeId = intval($sizeId);
		if ($section && $sizeId)
		{
			$size = Size::getBySectionAndId($sectionId, $sizeId);
			if (!$size || $size['ACTIVE'] != 'Y')
				$errors[] = 'wrong_size';
		}

		// Материал
		$material = htmlspecialchars(trim($material));

		// Особенности и комментарии
		$features = htmlspecialchars(trim($features));
		if (!$features)
			$errors[] = 'empty_features';

		// Цены
		$purchase = intval($purchase);
		if ($purchase < 0)
			$errors[] = 'wrong_purchase';
		if ($price <= 0)
			$errors[] = 'wrong_price';

		// Способы оплаты
		$paymentIds = array();
		$paymentError = false;
		foreach ($payment as $code)
		{
			$arPayment = Payment::getByCode($code);
			if ($arPayment)
				$paymentIds[] = $arPayment['ID'];
			else
				$paymentError = true;
		}
		if ($paymentError || !$paymentIds)
			$errors[] = 'wrong_paymemt';

		// Способы отправки
		$deliveryIds = array();
		$deliveryError = false;
		foreach ($delivery as $code => $p)
		{
			$arDelivery = Delivery::getByCode($code);
			if ($arDelivery)
				$deliveryIds[] = $arDelivery['ID'];
			else
				$deliveryError = true;
		}
		if ($deliveryError || !$deliveryIds)
			$errors[] = 'wrong_delivery';

		// Исключение, если ошибки в параметрах
		if ($errors)
			throw new ApiException($errors, 400);

		$fileIds = array();
		$f = new \CFile();
		foreach ($_FILES as $file)
		{
			$file['MODULE_ID'] = '_ad';
			$fileId = $f->SaveFile($file, 'ad');
			if (!$fileId)
				throw new ApiException(['photo_upload_error'], 500);
			$fileIds[] = $fileId;
		}
		if (!$fileIds)
			throw new ApiException(['least_one_photo_needed'], 400);

		//
		// Добавление объявления
		//
		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('ad');
		$name = $section['NAME'] . ' ' . $brand['NAME'];
		$id = $iblockElement->Add(array(
			'IBLOCK_ID' => $iblockId,
			'NAME' => $name,
			'PROPERTY_VALUES' => array(
				'USER' => $userId,
				'CATEGORY' => $sectionId,
				'BRAND' => $brandId,
				'CONDITION' => $conditionId,
				'COLOR' => $colorId,
				'SIZE' => $sizeId,
				'MATERIAL' => $material,
				'FEATURES' => $features,
				'PURCHASE' => $purchase,
				'PRICE' => $price,
				'PAYMENT' => $paymentIds,
				'DELIVERY' => $deliveryIds,
				'DELIVERY_PRICES' => json_encode($delivery),
				'PHOTO' => $fileIds,
			),
		));
		if (!$id)
			throw new ApiException(['ad_add_error'], 500, $iblockElement->LAST_ERROR);

		// Добавляем объявление в ленту
		Feed::addAd($id, $name);

		return array(
			'ID' => $id,
		);
	}

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
			), false, false, array(
				'ID', 'IBLOCK_ID', 'NAME',
				'PROPERTY_USER',
				'PROPERTY_CATEGORY',
				'PROPERTY_BRAND',
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
			));
			if ($item = $rsItems->Fetch())
			{
				$photo = array();
				foreach ($item['PROPERTY_PHOTO_VALUE'] as $id)
					$photo[] = Utils::getFileArray($id);
				$payment = array();
				foreach ($item['PROPERTY_PAYMENT_VALUE'] as $id => $p)
					$payment[] = Payment::getCodeById($id);
				$delivery = array();
				$deliveryPrice = json_decode($item['PROPERTY_DELIVERY_PRICES_VALUE'], true);
				foreach ($item['PROPERTY_DELIVERY_VALUE'] as $id)
				{
					$code = Delivery::getCodeById($id);
					$price = $deliveryPrice[$code];
					$delivery[$code] = $price;
				}

				$return = array(
					'id' => intval($item['ID']),
					'name' => $item['NAME'],
					'user' => User::publicProfile($item['PROPERTY_USER_VALUE']),
					'section' => intval($item['PROPERTY_CATEGORY_VALUE']),
					'brand' => intval($item['PROPERTY_BRAND_VALUE']),
					'condition' => intval($item['PROPERTY_CONDITION_VALUE']),
					'color' => intval($item['PROPERTY_COLOR_VALUE']),
					'size' => intval($item['PROPERTY_SIZE_VALUE']),
					'material' => $item['PROPERTY_MATERIAL_VALUE'],
					'features' => $item['PROPERTY_FEATURES_VALUE'],
					'purchase' => intval($item['PROPERTY_PURCHASE_VALUE']),
					'price' => intval($item['PROPERTY_PRICE_VALUE']),
					'payment' => $payment,
					'delivery' => $delivery,
					'photo' => $photo,
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	public static function shortById($id)
	{
		$ad = self::getById($id);
		return array(
			'id' => $ad['id'],
			'name' => $ad['name'],
			'user' => $ad['user'],
			'size' => $ad['size'],
			'purchase' => $ad['purchase'],
			'price' => $ad['price'],
			'photo' => array_shift($ad['photo']),
		);
	}

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

			$iblockId = Utils::getIBlockIdByCode('ad');
			$elementsFilter['IBLOCK_ID'] = $iblockId;

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

	public static function getList($params)
	{
		$return = array();

		$ids = self::getIds($params, true);
		foreach ($ids as $id)
			$return[] = self::shortById($id);

		return $return;
	}
}