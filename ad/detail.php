<?
/** @var CMain $APPLICATION */

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');


$ad = \Local\Data\Ad::detail($_REQUEST['id']);

$APPLICATION->SetTitle($ad['NAME']);

$brand = \Local\Catalog\Brand::getById($ad['brand']);
$section = \Local\Catalog\Catalog::getSectionById($ad['section']);
$color = \Local\Catalog\Color::getById($ad['color']);
$condition = \Local\Catalog\Condition::getById($ad['condition']);

$sections = [];
while ($section)
{
	array_unshift($sections, $section['NAME']);

	$section = \Local\Catalog\Catalog::getSectionById($section['PARENT']);
}

$sectionText = implode(' - ', $sections);

?>
<div class="ad">
	<div class="img"><img src="<?= $ad['photo'][0]['url'] ?>"></div>
	<div class="title"><h4><?= $ad['name'] ?></h4></div>
	<div class="desc"><?= $ad['description'] ?></div>
	<div class="features"><?= $ad['features'] ?></div>
	<div class="purchase price"><?= $ad['purchase'] ?><u>₽</u></div>
	<div class="price"><?= $ad['price'] ?><u>₽</u></div>
	<dl>
		<dt>Бренд:</dt>
		<dd><?= $brand['NAME'] ?></dd>
	</dl>
	<dl>
		<dt>Раздел:</dt>
		<dd><?= $sectionText ?></dd>
	</dl>
	<dl>
		<dt>Материал:</dt>
		<dd><?= $ad['material'] ?></dd>
	</dl>
	<dl>
		<dt>Цвет:</dt>
		<dd><?= $color['NAME'] ?></dd>
	</dl>
	<dl>
		<dt>Состояние:</dt>
		<dd><?= $condition['NAME'] ?></dd>
	</dl>
</div><?


require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');