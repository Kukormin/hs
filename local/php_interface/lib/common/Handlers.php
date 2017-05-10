<?

namespace Local\Common;

use Local\User\Session;
use Local\User\User;

/**
 * Class Handlers Обработчики событий
 * @package Local\Common
 */
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
			AddEventHandler('iblock', 'OnBeforeIBlockElementUpdate',
				array(__NAMESPACE__ . '\Handlers', 'beforeIBlockElementUpdate'));
			AddEventHandler('iblock', 'OnAfterIBlockElementUpdate',
				array(__NAMESPACE__ . '\Handlers', 'afterIBlockElementUpdate'));
			AddEventHandler('iblock', 'OnAfterIBlockElementDelete',
				array(__NAMESPACE__ . '\Handlers', 'afterIBlockElementDelete'));
			AddEventHandler('iblock', 'OnAfterIBlockAdd',
				array(__NAMESPACE__ . '\Handlers', 'afterIBlockUpdate'));
			AddEventHandler('iblock', 'OnAfterIBlockUpdate',
				array(__NAMESPACE__ . '\Handlers', 'afterIBlockUpdate'));
			AddEventHandler('iblock', 'OnIBlockDelete',
				array(__NAMESPACE__ . '\Handlers', 'afterIBlockUpdate'));
			AddEventHandler('main', 'OnBuildGlobalMenu',
				array(__NAMESPACE__ . '\Handlers', 'buildGlobalMenu'));
		}
	}

	public static function buildGlobalMenu(&$adminMenu, &$moduleMenu) {
		// Добавляем пункты меню в админку
		$moduleMenu[] = array(
			'parent_menu' => 'global_menu_services',
			'section' => 'chat',
			'sort' => 60,
			'text' => 'Чаты',
			'title' => 'Чаты в рамках сделок и вопросы в службу поддержки',
			'url' => 'http://hi-shopper-app.ru/admin/newchat.php',
			'icon' => 'forum_menu_icon',
			'items_id' => 'chat',
		);
	}

	/**
	 * Обработчик события перед удалением элемента, с возможностью отмены удаления
	 * @param $id
	 * @return bool
	 */
	public static function beforeIBlockElementDelete($id)
	{
		/*global $APPLICATION;
		$iblockId = self::getIblockByElementId($id);
		if ($iblockId == Utils::getIBlockIdByCode('user'))
		{
			// TODO:
			$APPLICATION->throwException("\nНельзя удалить пользователя, у которого есть сделки");
			return false;
		}
		if ($iblockId == Utils::getIBlockIdByCode('deal'))
		{
			$APPLICATION->throwException("\nНельзя удалять сделки");
			return false;
		}*/

		return true;
	}

	/**
	 * Обработчик события перед изменением элемента с возможностью отмены изменений
	 * @param $arFields
	 * @return bool
	 */
	public static function beforeIBlockElementUpdate(&$arFields)
	{
		/*global $APPLICATION;
		if ($arFields['IBLOCK_ID'] == Utils::getIBlockIdByCode('deal'))
		{
			$APPLICATION->throwException("\nЗапрещено редактировать сделку");
			return false;
		}*/

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
	private static function getIblockByElementId($id)
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