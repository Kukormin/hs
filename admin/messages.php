<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

/** @global CMain $APPLICATION */

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
	?>
	<div class="hero-unit"><?

	if ($item['IBLOCK_ID'] == $usersIblockId)
	{
		$user = \Local\User\User::getById($item['ID']);
		$key = 'u|' . $item['ID'];
		$messages[$key] = \Local\Data\Messages::getAllByKey($key);

		$href = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $item['IBLOCK_ID'] .
			'&type=main&ID=' . $item['ID'] . '&lang=ru&find_section_section=-1';
		?>
		<p>Пользователь: <a target="_blank" href="<?= $href ?>"><?= $item['ID'] ?></a></p><?
	}
	elseif ($item['IBLOCK_ID'] == $dealsIblockId)
	{
		$deal = \Local\Data\Deal::getById($item['ID']);
		for ($i = 0; $i <= 2; $i++)
		{
			$key = 'd|' . $item['ID'] . '|' . $i;
			$messages[$key] = \Local\Data\Messages::getAllByKey($key);
		}

		$href = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $item['IBLOCK_ID'] .
			'&type=user&ID=' . $item['ID'] . '&lang=ru&find_section_section=-1';
		?>
		<p>Сделка: <a target="_blank" href="<?= $href ?>"><?= $item['ID'] ?></a></p><?

		$href = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $usersIblockId .
			'&type=main&ID=' . $deal['SELLER'] . '&lang=ru&find_section_section=-1';
		?>
		<p>Продавец: <a target="_blank" href="<?= $href ?>"><?= $deal['SELLER'] ?></a></p><?

		$href = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $usersIblockId .
			'&type=main&ID=' . $deal['BUYER'] . '&lang=ru&find_section_section=-1';
		?>
		<p>Покупатель: <a target="_blank" href="<?= $href ?>"><?= $deal['BUYER'] ?></a></p><?
	}
	elseif ($item['IBLOCK_ID'] == $adsIblockId)
	{
		$ad = \Local\Data\Ad::getById($item['ID']);
		$key = 'a|' . $item['ID'];
		$messages[$key] = \Local\Data\Messages::getAllByKey($key);

		$href = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $item['IBLOCK_ID'] .
			'&type=main&ID=' . $item['ID'] . '&lang=ru&find_section_section=-1';
		?>
		<p>Объявление: <a target="_blank" href="<?= $href ?>"><?= $item['ID'] ?></a></p><?

		$href = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $usersIblockId .
			'&type=main&ID=' . $ad['USER'] . '&lang=ru&find_section_section=-1';
		?>
		<p>Продавец: <a target="_blank" href="<?= $href ?>"><?= $ad['USER'] ?></a></p>
		<p><a href="javascript:void(0)" data-oid="<?= $ad['USER'] ?>" class="btn btn-primary">Чат с продавцом</a></p><?
	}

	?>
	</div>
	<div class="row-fluid"><?

	$i = 0;
	foreach ($messages as $key => $chat)
	{
		$h3 = '';
		if ($item['IBLOCK_ID'] == $usersIblockId)
			$h3 = 'Чат с пользователем';
		elseif ($item['IBLOCK_ID'] == $dealsIblockId)
		{
			if ($i == 0)
				$h3 = 'Чат сделки (общий)';
			elseif ($i == 1)
				$h3 = 'Чат с продавцом';
			elseif ($i == 2)
				$h3 = 'Чат с покупателем';
		}
		elseif ($item['IBLOCK_ID'] == $adsIblockId)
			$h3 = 'Жалобы на объявление';

		?>
		<div class="span4">
		<h3><?= $h3 ?></h3>
		<form class="chat_form" method="POST">
			<input type="hidden" name="KEY" value="<?= $key ?>"/><?

			if ($item['IBLOCK_ID'] != $adsIblockId)
			{
				?>
				<div class="ta"><textarea name="MESSAGE" rows=3 cols=45></textarea></div><?
			}

			?>
			<div class="chat" id="chat<?= $i ?>"><?

				include('messages_chat.php');

				?>
			</div>
		</form>
		</div><?

		$i++;
	}

	?>
	</div><?

}
