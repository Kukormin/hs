<?

namespace Local\Data;

use Local\Catalog\Brand;
use Local\Catalog\Catalog;
use Local\Catalog\Size;
use Local\Common\ExtCache;
use Local\Common\Utils;
use Local\Api\ApiException;

class User
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/User/';

	/**
	 * Путь для кеширования
	 */
	const SALT = 's0fgs6d0f6h';

	/**
	 * Обработчики событий
	 */
	public static function addEventHandlers() {
		static $added = false;
		if (!$added) {
			$added = true;
			AddEventHandler('iblock', 'OnAfterIBlockElementUpdate',
				array(__NAMESPACE__ . '\User', 'afterIBlockElementUpdate'));
			AddEventHandler('iblock', 'OnAfterIBlockElementDelete',
				array(__NAMESPACE__ . '\User', 'afterIBlockElementDelete'));
		}
	}

	/**
	 * Первый шаг для авторизации пользователя по телефону
	 * Отправка sms на указанный номер
	 * @param $phone
	 * @return mixed
	 * @throws ApiException
	 */
	public static function authByPhone($phone) {
		$phone = trim($phone);
		if (strlen($phone) == 0)
			throw new ApiException(['empty_phone'], 400);
		if (strlen($phone) != 11)
			throw new ApiException(['wrong_phone_format'], 400);

		// Ищем по телефону
		$user = self::getByPhone($phone);
		if ($user['ACTIVE'] == 'N')
			throw new ApiException(['user_blocked'], 403);

		// Если не найден, то пробуем создать
		if (!$user)
			if (self::addByPhone($phone))
				// Если пользователь создан, получаем все его поля, заодно обновляя кеш
				$user = self::getByPhone($phone, true);

		$userId = $user['ID'];
		// Теоретически не должна возникать, т.к. пользователь создается если не найден
		if (!$userId)
			throw new ApiException(['user_not_founded'], 500);

		$smsKey = self::generateSmsKey();
		self::saveSmsKey($userId, $smsKey);
		$sended = self::sendSmsKey($smsKey);
		if (!$sended)
			throw new ApiException(['sms_error'], 500);

		return array(
			'user' => $userId,
	        'sms' => $smsKey,
		);
	}

	/**
	 * Второй шаг для авторизации пользователя
	 * Проверка кода, отправленного на телефон по sms и создание сессии для заданного устройства
	 * @param $phone
	 * @param $smsKey
	 * @param $userId
	 * @param $device
	 * @return array
	 * @throws ApiException
	 */
	public static function verify($phone, $smsKey, $userId, $device) {
		$phone = trim($phone);
		if (strlen($phone) == 0)
			throw new ApiException(['empty_phone'], 400);
		if (strlen($phone) != 11)
			throw new ApiException(['wrong_phone_format'], 400);
		$smsKey = trim($smsKey);
		if (strlen($smsKey) != 4)
			throw new ApiException(['wrong_sms_code_format'], 400);

		// Ищем по телефону (без кеша, т.к. смс не в кеше)
		$user = self::getByPhone($phone, true);
		if ($userId != $user['ID'])
			throw new ApiException(['user_not_founded_by_user_id'], 400);

		if ($smsKey != $user['SMS'])
			throw new ApiException(['wrong_sms_code'], 400);

		// Сюда дошли, если всё ок
		// Генерируем новую сессию
		$authToken = self::createSession($userId, $device);

		return array(
			'token' => $authToken,
		);
	}

	/**
	 * Проверка авторизации
	 * @return array|mixed
	 * @throws ApiException
	 */
	public static function checkAuth()
	{
		$headers = getallheaders();
		$authToken = $headers['x-auth'];
		if (!$authToken)
			throw new ApiException(['not_authorized'], 401);

		$session = self::getSession($authToken);
		if (!$session)
			throw new ApiException(['not_authorized'], 401);

		return $session;
	}

	/**
	 * Возвращает ID текущего пользователя (0 - если неавторизован)
	 * @return int
	 */
	public static function getCurrentUserId()
	{
		$userId = 0;

		$headers = getallheaders();
		$authToken = $headers['x-auth'];
		if ($authToken)
		{
			$session = self::getSession($authToken);
			if ($session)
				$userId = intval($session['USER_ID']);
		}

		return $userId;
	}

	/**
	 * Возвращает пользователя по номеру телефона
	 * @param $phone
	 * @param bool $refreshCache
	 * @return mixed
	 */
	public static function getByPhone($phone, $refreshCache = false) {
		$phone = strval($phone);

		$return = array();
		$extCache = new ExtCache(
			array(
				__FUNCTION__,
			    $phone,
			),
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
			$rsItems = $iblockElement->GetList(array(), array(
				'IBLOCK_ID' => $iblockId,
				'NAME' => $phone,
			), false, false, array(
				'ID', 'NAME', 'ACTIVE', 'TMP_ID',
			));
			if ($item = $rsItems->Fetch())
			{
				$return = array(
					'ID' => intval($item['ID']),
					'NAME' => $item['NAME'],
				    'ACTIVE' => $item['ACTIVE'],
				    'SMS' => $item['TMP_ID'],
				);
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
		$session = User::checkAuth();
		$userId = $session['USER_ID'];

		$profile = self::getById($userId);
		return $profile;
	}

	/**
	 * Возвращает пользователя по ID
	 * @param $id
	 * @param bool $refreshCache
	 * @return mixed
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
			false // не используем теговый кеш, чтоб не удалять кеш при измененях в пользователях
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('user');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array(), array(
			    'IBLOCK_ID' => $iblockId,
			    'ID' => $id,
			), false, false, array(
				'ID', 'IBLOCK_ID', 'NAME', 'CODE',
			    'PROPERTY_NAME',
			    'PROPERTY_CITY',
			    'PROPERTY_EMAIL',
			    'PROPERTY_PHOTO',
			    'PROPERTY_STREET',
			    'PROPERTY_FLAT',
			    'PROPERTY_INDEX',
			    'PROPERTY_FIO',
			    'PROPERTY_SIZE',
			    'PROPERTY_BRAND',
			    'PROPERTY_SECTION',
			));
			if ($item = $rsItems->Fetch())
			{
				$photo = array();
				if ($item['PROPERTY_PHOTO_VALUE'])
				{
					$file = new \CFile();
					$photo = array(
						'id' => intval($item['PROPERTY_PHOTO_VALUE']),
						'url' => $file->GetPath($item['PROPERTY_PHOTO_VALUE']),
					);
				}
				$size = array();
				foreach ($item['PROPERTY_SIZE_VALUE'] as $tmp)
					$size[] = intval($tmp);
				$brand = array();
				foreach ($item['PROPERTY_BRAND_VALUE'] as $tmp)
					$brand[] = intval($tmp);
				$section = array();
				foreach ($item['PROPERTY_SECTION_VALUE'] as $tmp)
					$section[] = intval($tmp);
				$return = array(
					'id' => intval($item['ID']),
					'phone' => $item['NAME'],
					'active' => $item['ACTIVE'],
					'name' => $item['PROPERTY_NAME_VALUE'],
					'city' => $item['PROPERTY_CITY_VALUE'],
					'nickname' => $item['CODE'],
					'email' => $item['PROPERTY_EMAIL_VALUE'],
					'photo' => $photo,
					'address' => array(
						'street' => $item['PROPERTY_STREET_VALUE'],
						'flat' => $item['PROPERTY_FLAT_VALUE'],
						'index' => $item['PROPERTY_INDEX_VALUE'],
						'fio' => $item['PROPERTY_FIO_VALUE'],
					),
					'sizes' => $size,
					'brands' => $brand,
					'sections' => $section,
				);
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
	private static function addByPhone($phone) {
		$iblockElement = new \CIBlockElement();

		$iblockId = Utils::getIBlockIdByCode('user');
		$id = $iblockElement->Add(array(
			'IBLOCK_ID' => $iblockId,
			'NAME' => $phone,
			'ACTIVE' => 'Y',
		));
		if (!$id)
			throw new ApiException(['user_add_error'], 500, $iblockElement->LAST_ERROR);

		return $id;
	}

	/**
	 * Генерирует случайное число для отправки по SMS
	 * @return int
	 */
	private static function generateSmsKey() {
		return rand(1001, 9999);
	}

	/**
	 * Отправляет смс с ключом
	 */
	private static function sendSmsKey() {
		// TODO:
		$sended = true;

		return $sended;
	}

	/**
	 * Сохраняет код, отправленный по SMS
	 * @param $userId
	 * @param $smsKey
	 */
	private static function saveSmsKey($userId, $smsKey) {
		$iblockElement = new \CIBlockElement();

		$iblockElement->Update($userId, array(
			'TMP_ID' => $smsKey,
		));
	}

	public static function update($data)
	{
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = User::checkAuth();
		$userId = $session['USER_ID'];

		$iblockElement = new \CIBlockElement();
		$iblockId = Utils::getIBlockIdByCode('user');

		$fields = array();
		if ($data['nickname'])
		{
			$nickname = htmlspecialchars(trim($data['nickname']));
			$id = self::getIdByNickName($nickname);
			if ($id == 0)
				$fields['CODE'] = $nickname;
			elseif ($id != $userId)
				throw new ApiException(['nickname_already_exists'], 400);
		}
		if ($fields)
			$iblockElement->Update($userId, $fields);

		$properties = array();
		if ($data['name'])
			$properties['NAME'] = htmlspecialchars(trim($data['name']));
		if ($data['city'])
			$properties['CITY'] = htmlspecialchars(trim($data['city']));
		if ($data['email'])
			$properties['EMAIL'] = htmlspecialchars(trim($data['email']));
		if ($data['address'])
		{
			$properties['STREET'] = htmlspecialchars(trim($data['address']['street']));
			$properties['FLAT'] = htmlspecialchars(trim($data['address']['flat']));
			$properties['INDEX'] = htmlspecialchars(trim($data['address']['index']));
			$properties['FIO'] = htmlspecialchars(trim($data['address']['fio']));
		}
		if ($data['sizes'])
		{
			$sizeIds = array();
			foreach($data['sizes'] as $id)
			{
				$size = Size::getById($id);
				if ($size['ACTIVE'] == 'Y')
					$sizeIds[] = $size['ID'];
			}
			if ($sizeIds)
				$properties['SIZE'] = $sizeIds;
		}
		if ($data['brands'])
		{
			$brandIds = array();
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
		if ($data['sections'])
		{
			$sectionIds = array();
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
		if ($properties)
			$iblockElement->SetPropertyValuesEx($userId, $iblockId, $properties);

		return self::getById($userId, true);
	}

	/**
	 * Создает сессию для указанного пользователя
	 * @param $userId
	 * @param $device
	 * @return string
	 * @throws ApiException
	 */
	private static function createSession($userId, $device) {
		$deviceToken = trim($device['uuid']);
		if (strlen($deviceToken) == 0)
			throw new ApiException(['empty_device_uuid'], 400);

		$authToken = md5($userId . '|' . $deviceToken . '|' . static::SALT);
		$session = self::getSession($authToken);
		// Если не найден, то пробуем создать
		if (!$session)
			if (self::addSession($authToken, $userId, $device))
				// Если пользователь создан, получаем все его поля, заодно обновляя кеш
				$session = self::getSession($authToken, true);

		if (!$session)
			throw new ApiException(['session_add_error'], 500);

		return $authToken;
	}

	/**
	 * Получает сессию по токену авторизации
	 * @param $authToken
	 * @param bool $refreshCache
	 * @return array|mixed
	 */
	public static function getSession($authToken, $refreshCache = false) {
		$return = array();
		$extCache = new ExtCache(
			array(
				__FUNCTION__,
				$authToken,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20,
			false // не используем теговый кеш, чтоб не удалять кеш при измененях
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('session');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array(), array(
				'IBLOCK_ID' => $iblockId,
				'NAME' => $authToken,
			), false, false, array(
				'ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'XML_ID',
			));
			if ($item = $rsItems->Fetch())
			{
				$return = array(
					'ID' => intval($item['ID']),
					'NAME' => $item['NAME'],
					'CODE' => $item['CODE'],
					'SIZE' => $item['PREVIEW_TEXT'],
					'USER_ID' => intval($item['XML_ID']),
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Добавляет элемент сессии
	 * @param $authToken
	 * @param $userId
	 * @param $device
	 * @return bool
	 * @throws ApiException
	 */
	private static function addSession($authToken, $userId, $device) {
		$iblockElement = new \CIBlockElement();

		$iblockId = Utils::getIBlockIdByCode('session');
		$id = $iblockElement->Add(array(
			'IBLOCK_ID' => $iblockId,
			'NAME' => $authToken,
			'CODE' => $device['uuid'],
		    'PREVIEW_TEXT' => $device['x'] . 'x' . $device['y'],
		    'XML_ID' => $userId,
		));
		if (!$id)
			throw new ApiException(['session_add_error'], 500, $iblockElement->LAST_ERROR);

		return $id;
	}

	/**
	 * Обработчики события изменения элемента, для сброса кеша пользователей или сессий
	 * @param $arFields
	 */
	public static function afterIBlockElementUpdate(&$arFields)
	{
		if ($arFields['IBLOCK_ID'] == Utils::getIBlockIdByCode('user') && $arFields['ID'])
		{
			$name = $arFields['NAME'];
			if (!$name)
				$name = self::getNameById($arFields['ID']);
			if ($name)
				self::getByPhone($name, true);
			self::getById($arFields['ID'], true);

			// если пользователя деактивируют, то нужно удалить все его сессии
			if ($arFields['ACTIVE'] == 'N')
				self::deleteSessionsByUserId($arFields['ID']);
		}
		if ($arFields['IBLOCK_ID'] == Utils::getIBlockIdByCode('session') && $arFields['NAME'])
			self::getSession($arFields['NAME'], true);
	}

	/**
	 * Обработчики события удаления элемента, для сброса кеша пользователей или сессий
	 * @param $arFields
	 */
	public static function afterIBlockElementDelete($arFields)
	{
		if ($arFields['IBLOCK_ID'] == Utils::getIBlockIdByCode('user') && $arFields['ID'])
		{
			$name = $arFields['NAME'];
			if (!$name)
				$name = self::getNameById($arFields['ID']);
			if ($name)
				self::getByPhone($name, true);
			self::getById($arFields['ID'], true);

			// если пользователя удаляют, то нужно удалить все его сессии
			self::deleteSessionsByUserId($arFields['ID']);
		}
		if ($arFields['IBLOCK_ID'] == Utils::getIBlockIdByCode('session') && $arFields['NAME'])
			self::getSession($arFields['NAME'], true);
	}

	/**
	 * Удаляет все сессии пользователя
	 * @param $userId
	 */
	private static function deleteSessionsByUserId($userId)
	{
		$iblockId = Utils::getIBlockIdByCode('session');

		$iblockElement = new \CIBlockElement();
		$rsItems = $iblockElement->GetList(array(), array(
			'IBLOCK_ID' => $iblockId,
			'XML_ID' => $userId,
		), false, false, array(
			'ID',
		));
		while ($item = $rsItems->Fetch())
			$iblockElement->Delete($item['ID']);
	}

	/**
	 * Находит название элемента по ID
	 * @param $id
	 * @return string
	 */
	private static function getNameById($id)
	{
		$name = '';
		$iblockElement = new \CIBlockElement();
		$rsItems = $iblockElement->GetList(array(), array(
			'ID' => $id,
		), false, false, array(
			'NAME',
		));
		if ($item = $rsItems->Fetch())
			$name = $item['NAME'];

		return $name;
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
		$rsItems = $iblockElement->GetList(array(), array(
			'CODE' => $code,
			'IBLOCK_ID' => $iblockId,
		), false, false, array(
			'ID',
		));
		if ($item = $rsItems->Fetch())
			$id = $item['ID'];

		return $id;
	}

	// TODO: проверка на возможность удаления пользователя

}