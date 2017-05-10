<?

	$name = '';
	$title = '';
	$type = '';
	if ($item['IBLOCK_ID'] == $dealsIblockId)
	{
		$deal = \Local\Data\Deal::getById($item['ID']);
		$type = 'deal';
		$title = 'Сделка';
		$href = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $item['IBLOCK_ID'] .
			'&type=main&ID=' . $item['ID'] . '&lang=ru&find_section_section=-1';
		$name = $deal['NAME'];
	}
	elseif ($item['IBLOCK_ID'] == $usersIblockId)
	{
		$user = \Local\User\User::getById($item['ID']);
		$type = 'user';
		$title = 'Пользователь';
		$href = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $item['IBLOCK_ID'] .
			'&type=user&ID=' . $item['ID'] . '&lang=ru&find_section_section=-1';
		$name = $user['nickname'];
		if ($user['name'])
			$name .= ' (' . $user['name'] . ')';
	}
	elseif ($item['IBLOCK_ID'] == $adsIblockId)
	{
		$ad = \Local\Data\Ad::getById($item['ID']);
		$type = 'ad';
		$title = 'Объявление';
		$href = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $item['IBLOCK_ID'] .
			'&type=main&ID=' . $item['ID'] . '&lang=ru&find_section_section=-1';
		$name = $ad['NAME'];
	}

	if ($name)
	{
		$x = '';
		if ($item['SORT'] == 555)
			$x = '<i></i>';
		?>
		<li<?= $cl ?>><a data-id="<?= $item['ID'] ?>"
	                 href="<?= $href ?>"><?= $x ?><?= $title ?>: <?= $name ?></a></li><?
	}
