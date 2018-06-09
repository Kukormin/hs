<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

/** @global CMain $APPLICATION */

$return = [];

$iblockElement = new \CIBlockElement();
$usersIblockId = \Local\Common\Utils::getIBlockIdByCode('user');
$dealsIblockId = \Local\Common\Utils::getIBlockIdByCode('deal');
$adsIblockId = \Local\Common\Utils::getIBlockIdByCode('ad');

$rsData = $iblockElement->GetList([], [
	'ID' => $_REQUEST['id'],
]);
if ($item = $rsData->Fetch())
{
	$messages = [];
	$deal = [];

	if ($item['IBLOCK_ID'] == $usersIblockId)
	{
		$user = \Local\User\User::getById($item['ID']);
		$key = 'u|' . $item['ID'];
		$messages[$key] = \Local\Data\Messages::getAllByKey($key);
	}
	elseif ($item['IBLOCK_ID'] == $dealsIblockId)
	{
		$deal = \Local\Data\Deal::getById($item['ID']);
		for ($i = 0; $i <= 2; $i++)
		{
			$key = 'd|' . $item['ID'] . '|' . $i;
			$messages[$key] = \Local\Data\Messages::getAllByKey($key);
		}
	}
	elseif ($item['IBLOCK_ID'] == $adsIblockId)
	{
		$ad = \Local\Data\Ad::getById($item['ID']);
		$key = 'a|' . $item['ID'];
		$messages[$key] = \Local\Data\Messages::getAllByKey($key);
	}



	$i = 0;
	foreach ($messages as $key => $chat)
	{
		ob_start();
		include('messages_chat.php');
		$return[$i] = ob_get_contents();
		ob_end_clean();

		$i++;
	}

}

$maxXmlId = 0;

ob_start();
include('menu.php');
$return['MENU'] = ob_get_contents();
ob_end_clean();

$return['MAX'] = $maxXmlId;


header('Content-Type: application/json');
echo json_encode($return);