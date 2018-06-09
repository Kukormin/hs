<?
$iblockElement = new \CIBlockElement();
$usersIblockId = \Local\Common\Utils::getIBlockIdByCode('user');
$dealsIblockId = \Local\Common\Utils::getIBlockIdByCode('deal');
$adsIblockId = \Local\Common\Utils::getIBlockIdByCode('ad');

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
	'>SORT' => 550,
]);
while ($item = $rsData->Fetch())
{
	$cl = '';
	if ($_REQUEST['id'])
	{
		if ($_REQUEST['id'] == $item['ID'])
			$cl = ' class="active"';
	}
	else
	{
		if (!$firstItem)
		{
			$firstItem = $item;
			$cl = ' class="active"';
		}
	}

	if ($item['XML_ID'] > $maxXmlId)
		$maxXmlId = $item['XML_ID'];

	include('li.php');
}