<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

/** @global CMain $APPLICATION */

$iblockElement = new \CIBlockElement();
$usersIblockId = \Local\Common\Utils::getIBlockIdByCode('user');
$dealsIblockId = \Local\Common\Utils::getIBlockIdByCode('deal');

$rsData = $iblockElement->GetList([], [
	'ID' => $_REQUEST['id'],
]);
if ($item = $rsData->Fetch())
{
	$cl = '';
	include('li.php');
}
