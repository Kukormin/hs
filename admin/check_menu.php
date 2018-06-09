<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

/** @global CMain $APPLICATION */

$iblockElement = new \CIBlockElement();
$usersIblockId = \Local\Common\Utils::getIBlockIdByCode('user');
$dealsIblockId = \Local\Common\Utils::getIBlockIdByCode('deal');
$adsIblockId = \Local\Common\Utils::getIBlockIdByCode('ad');

$maxXmlId = 0;

$firstItem = false;
$rsData = $iblockElement->GetList([
	'XML_ID' => 'desc',
	'ID' => 'desc',
], [
	'IBLOCK_ID' => [
		$usersIblockId,
		$dealsIblockId,
		$adsIblockId,
	],
	'=SORT' => 555,
], false, [
	'nTopCount' => 1,
]);
while ($item = $rsData->Fetch())
{
	if ($item['XML_ID'] > $maxXmlId)
		$maxXmlId = $item['XML_ID'];
}

echo $maxXmlId;