<?

namespace Local\User;

use Local\Api\ApiException;

class Auth
{
	/**
	 * Первый шаг для авторизации пользователя по телефону
	 * Отправка sms на указанный номер
	 * @param $phone
	 * @return mixed
	 * @throws ApiException
	 */
	public static function step1($phone) {
		$phone = trim($phone);
		if (strlen($phone) == 0)
			throw new ApiException(['empty_phone'], 400);
		if (strlen($phone) != 11)
			throw new ApiException(['wrong_phone_format'], 400);

		// Ищем по телефону
		$user = User::getByPhone($phone);
		if ($user['ACTIVE'] == 'N')
			throw new ApiException(['user_blocked'], 403);

		// Если не найден, то пробуем создать
		if (!$user)
			if (User::addByPhone($phone))
				// Если пользователь создан, получаем все его поля, заодно обновляя кеш
				$user = User::getByPhone($phone, true);

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
	public static function step2($phone, $smsKey, $userId, $device) {
		$phone = trim($phone);
		if (strlen($phone) == 0)
			throw new ApiException(['empty_phone'], 400);
		if (strlen($phone) != 11)
			throw new ApiException(['wrong_phone_format'], 400);
		$smsKey = trim($smsKey);
		if (strlen($smsKey) != 4)
			throw new ApiException(['wrong_sms_code_format'], 400);

		// Ищем по телефону (без кеша, т.к. смс не в кеше)
		$user = User::getByPhone($phone, true);
		if ($userId != $user['ID'])
			throw new ApiException(['user_not_founded_by_user_id'], 400);

		if ($smsKey != $user['SMS'])
			throw new ApiException(['wrong_sms_code'], 400);

		// Сюда дошли, если всё ок
		// Генерируем новую сессию
		$authToken = Session::create($userId, $device);

		return array(
			'token' => $authToken,
		);
	}

	/**
	 * Проверка авторизации
	 * @return array|mixed
	 * @throws ApiException
	 */
	public static function check()
	{
		$headers = getallheaders();
		$authToken = $headers['x-auth'];
		if (!$authToken)
			throw new ApiException(['not_authorized'], 401);

		$session = Session::getByToken($authToken);
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
			$session = Session::getByToken($authToken);
			if ($session)
				$userId = intval($session['USER_ID']);
		}

		return $userId;
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

}