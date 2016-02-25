<?

namespace Local\Data;

use Local\Catalog\Delivery;
use Local\Catalog\Payment;
use Local\Catalog\Catalog;
use Local\Catalog\Brand;
use Local\Catalog\Condition;
use Local\Catalog\Color;
use Local\Catalog\Size;
use Local\Common\Utils;
use Local\Api\ApiException;

class Ad
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/Ad/';

	public static function add($sectionId, $brandId, $conditionId, $colorId, $sizeId, $material, $features,
	                           $purchase, $price, $payment, $delivery) {

		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = User::checkAuth();

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
		foreach ($delivery as $code => $price)
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
		$id = $iblockElement->Add(array(
			'IBLOCK_ID' => $iblockId,
			'NAME' => $section['NAME'] . ' ' . $brand['NAME'],
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

		return array(
			'ID' => $id,
		);
	}
}