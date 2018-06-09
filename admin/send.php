<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

/** @global CMain $APPLICATION */

$iblockElement = new \CIBlockElement();
$usersIblockId = \Local\Common\Utils::getIBlockIdByCode('user');
$dealsIblockId = \Local\Common\Utils::getIBlockIdByCode('deal');

$key = $_REQUEST['key'];
$message = $_REQUEST['message'];

if ($message && $key)
{
	$ar = explode('|', $key);
	$chatCode = $ar[0];
	$itemId = $ar[1];
	$id = \Local\Data\Messages::add($key, 0, $message);
	if ($chatCode == 'u')
		\Local\User\User::updateChatInfo($itemId, true);
	elseif ($chatCode == 'd')
		\Local\Data\Deal::updateChatInfo($itemId, true);

	if ($message['USER'])
	{
		$class = 'seller';
		if ($deal['BUYER'] == $message['USER'])
			$class = 'buyer';
		$user = \Local\User\User::getById($message['USER']);
		$userName = $user['nickname'];
	}
	else
	{
		$class = 'support';
		$userName = 'Служба поддержки';
	}
	?>
	<dl class="support">
		<dt>[<?= ConvertTimeStamp(time(), 'FULL') ?>] <b>Служба поддержки</b></dt>
		<dd><?= $message ?></dd>
	</dl><?
}

