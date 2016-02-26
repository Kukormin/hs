<?

namespace Local\Common;

use Local\User\Session;
use Local\User\User;

class Handlers
{
	/**
	 * Добавление обработчиков
	 */
	public static function addEventHandlers() {
		static $added = false;
		if (!$added) {
			$added = true;
			AddEventHandler('iblock', 'OnBeforeIBlockElementDelete',
				array(__NAMESPACE__ . '\Handlers', 'beforeIBlockElementDelete'));
			AddEventHandler('iblock', 'OnAfterIBlockElementUpdate',
				array(__NAMESPACE__ . '\Handlers', 'afterIBlockElementUpdate'));
			AddEventHandler('iblock', 'OnAfterIBlockElementDelete',
				array(__NAMESPACE__ . '\Handlers', 'afterIBlockElementDelete'));
			AddEventHandler('iblock', 'OnAfterIBlockAdd',
				array(__NAMESPACE__ . '\Utils', 'afterIBlockUpdate'));
			AddEventHandler('iblock', 'OnAfterIBlockUpdate',
				array(__NAMESPACE__ . '\Utils', 'afterIBlockUpdate'));
			AddEventHandler('iblock', 'OnIBlockDelete',
				array(__NAMESPACE__ . '\Utils', 'afterIBlockUpdate'));
		}
	}

	/**
	 * Обработчик события перед удалением элемента, с возможностью отмены удаления
	 * @param $id
	 * @return bool
	 */
	public static function beforeIBlockElementDelete($id)
	{
		$iblockId = self::getIblockById($id);
		if ($iblockId == Utils::getIBlockIdByCode('user'))
		{
			global $APPLICATION;
			// TODO:
			$APPLICATION->throwException("\nНельзя удалить пользователя, у которого есть сделки");
			return false;
		}

		return true;
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
				User::getByPhone($name, true);
			User::getById($arFields['ID'], true);

			// если пользователя деактивируют, то нужно удалить все его сессии
			if ($arFields['ACTIVE'] == 'N')
				Session::deleteByUserId($arFields['ID']);
		}
		if ($arFields['IBLOCK_ID'] == Utils::getIBlockIdByCode('session') && $arFields['NAME'])
			Session::getByToken($arFields['NAME'], true);
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
				User::getByPhone($name, true);
			User::getById($arFields['ID'], true);

			// если пользователя удаляют, то нужно удалить все его сессии
			Session::deleteByUserId($arFields['ID']);
		}
		if ($arFields['IBLOCK_ID'] == Utils::getIBlockIdByCode('session') && $arFields['NAME'])
			Session::getByToken($arFields['NAME'], true);
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
	 * Находит ID инфоблока по ID элемента
	 * @param $id
	 * @return string
	 */
	private static function getIblockById($id)
	{
		$iblock = '';
		$iblockElement = new \CIBlockElement();
		$rsItems = $iblockElement->GetList(array(), array(
			'ID' => $id,
		), false, false, array(
			'IBLOCK_ID',
		));
		if ($item = $rsItems->Fetch())
			$iblock = $item['IBLOCK_ID'];

		return $iblock;
	}

	/**
	 * обработчик на редактирование ИБ для сброса кеша
	 */
	public static function afterIBlockUpdate() {
		Utils::getAllIBlocks(true);
	}

}