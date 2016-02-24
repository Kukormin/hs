<?
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

//
// Служебный скрипт импорта брендов
//

$allBrands = Local\Catalog\Brand::getAll(true);
$byName = array();
foreach ($allBrands as $brand)
	$byName[$brand['NAME']] = true;

$iblockElement = new \CIBlockElement();
$iblockId = Local\Common\Utils::getIBlockIdByCode('brand');

$f = fopen('br.txt', "rb");
if ($f)
{
	while (!feof($f))
	{
		$s = trim(fgets($f));
		if ($s && !$byName[$s])
		{
			$id = $iblockElement->Add(array(
				'IBLOCK_ID' => $iblockId,
				'NAME' => $s,
			));
		}
	}
}