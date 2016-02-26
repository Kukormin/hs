<?

namespace Local\User;

use Local\Catalog\Brand;
use Local\Catalog\Catalog;
use Local\Catalog\Gender;
use Local\Catalog\Size;
use Local\Common\ExtCache;
use Local\Common\Utils;
use Local\Api\ApiException;
use Local\Data\Feed;

class User
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/User/User/';

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
		$session = Auth::check();
		$userId = $session['USER_ID'];

		$profile = self::getById($userId);
		$profile['follow'] = Follower::get($userId);
		return $profile;
	}

	/**
	 * Возвращает публичный профиль пользователя
	 * @param $userId
	 * @return array
	 */
	public static function publicProfile($userId)
	{
		$profile = self::getById($userId);
		return array(
			'id' => $profile['id'],
			'city' => $profile['city'],
			'nickname' => $profile['nickname'],
			'photo' => $profile['photo'],
		);
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
			    'PROPERTY_GENDER',
			    'PROPERTY_SIZE',
			    'PROPERTY_BRAND',
			    'PROPERTY_SECTION',
			));
			if ($item = $rsItems->Fetch())
			{
				$photo = array();
				if ($item['PROPERTY_PHOTO_VALUE'])
					$photo = Utils::getFileArray($item['PROPERTY_PHOTO_VALUE']);
				$gender = '';
				if ($item['PROPERTY_GENDER_ENUM_ID'])
					$gender = Gender::getCodeById($item['PROPERTY_GENDER_ENUM_ID']);
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
					'name' => $item['PROPERTY_NAME_VALUE'],
					'city' => $item['PROPERTY_CITY_VALUE'],
					'nickname' => $item['CODE'],
					'email' => $item['PROPERTY_EMAIL_VALUE'],
					'photo' => $photo,
					'gender' => $gender,
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
	public static function addByPhone($phone) {
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

		$id = self::getIdByNickName($nickname);
		return array(
			'used' => $id ? 1 : 0,
		);
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

		$fields = array();
		if ($data['nickname'] && $profile['nickname'] != $data['nickname'])
		{
			$nickname = htmlspecialchars(trim($data['nickname']));
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

		$properties = array();
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
		if (isset($data['brands']) && $profile['brands'] != $data['brands'])
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
		if (isset($data['sections']) && $profile['sections'] != $data['sections'])
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

		self::push($publisherId, 'Пользователь %' . $userId . '% добавил вас в свой список избранных пользователей');

		return Follower::get($userId);
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

		self::push($publisherId, 'Пользователь %' . $userId . '% удалил вас из своего списка избранных пользователей');

		return Follower::get($userId);
	}

	public static function push($userId, $message)
	{
		// TODO:
	}

}