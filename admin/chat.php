<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");

/** @var string $by */
/** @var string $order */
/** @var string $type */
/** @var string $need_answer */
/** @var string $deal_id */
/** @var string $user_id */
/** @var string $DETAIL */
/** @var string $REQUEST_METHOD */
/** @var string $MESSAGE */
/** @var string $KEY */
/** @var string $updatestatus */
/** @global CMain $APPLICATION */

$iblockElement = new \CIBlockElement();
$usersIblockId = \Local\Common\Utils::getIBlockIdByCode('user');
$dealsIblockId = \Local\Common\Utils::getIBlockIdByCode('deal');

if ($DETAIL)
{
	$rsItem = $iblockElement->GetByID($DETAIL);
	$item = $rsItem->Fetch();


	// Обработка добавления сообщения
	$activeTab = 'chat0';
	if ($REQUEST_METHOD == "POST" && check_bitrix_sessid()) {
		if ($MESSAGE)
		{
			\Local\Data\Messages::add($KEY, 0, $MESSAGE);
			if ($item['IBLOCK_ID'] == $usersIblockId)
				\Local\User\User::updateChatInfo($item['ID'], true);
			elseif ($item['IBLOCK_ID'] == $dealsIblockId)
				\Local\Data\Deal::updateChatInfo($item['ID'], true);
			$activeTab = 'chat' . substr($KEY, -1);
		}
		if ($updatestatus && $item['IBLOCK_ID'] == $dealsIblockId)
		{
			$status = \Local\Data\Status::getByCode($updatestatus);
			$deal = \Local\Data\Deal::update($item['ID'], array('STATUS' => $status['ID']));
			\Local\Data\History::add($item['ID'], $status['ID'], 0);
			$activeTab = 'deal';
		}
	}

	$messages = array();
	$deal = array();
	if ($item['IBLOCK_ID'] == $usersIblockId)
	{
		$user = \Local\User\User::getById($item['ID']);
		$APPLICATION->SetTitle('Чаты: Пользователь: ' . $user['nickname'] . ' (' . $user['name'] . ')');
		$aTabs = array(
			array(
				"DIV" => "chat0",
				"TAB" => 'Вопросы в службу поддержки',
				"ICON" => "main_user_edit",
				"TITLE" => 'Вопросы в службу поддержки'
			),
		);
		$key = 'u|' . $item['ID'];
		$messages[$key] = \Local\Data\Messages::getAllByKey($key);
	}
	elseif ($item['IBLOCK_ID'] == $dealsIblockId)
	{
		$deal = \Local\Data\Deal::getById($item['ID']);
		$APPLICATION->SetTitle('Чаты: Сделка: ' . $deal['NAME']);
		$aTabs = array(
			array(
				"DIV" => "chat0",
				"TAB" => 'Чат сделки',
				"ICON" => "main_user_edit",
				"TITLE" => 'Чат сделки'
			),
			array(
				"DIV" => "chat1",
				"TAB" => 'Чат с продавцом',
				"ICON" => "main_user_edit",
				"TITLE" => 'Чат с продавцом'
			),
			array(
				"DIV" => "chat2",
				"TAB" => 'Чат с покупателем',
				"ICON" => "main_user_edit",
				"TITLE" => 'Чат с покупателем'
			),
			array(
				"DIV" => "deal",
				"TAB" => 'Информация о сделке',
				"ICON" => "main_user_edit",
				"TITLE" => 'Информация о сделке'
			),
		);
		for ($i = 0; $i <= 2; $i++)
		{
			$key = 'd|' . $item['ID'] . '|' . $i;
			$messages[$key] = \Local\Data\Messages::getAllByKey($key);
		}
	}

	$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);
	if ($activeTab)
		$_REQUEST[$tabControl->name."_active_tab"] = $activeTab;

	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	?>
	<style>
		.chat {

		}
		.chat > dl {
			margin: 10px 0;
			padding: 7px 10px;
			border-radius: 6px;
		}
		.chat > dl > dt {
			margin: 0;
			padding: 0;
		}
		.chat > dl > dd {
			margin: 4px 0 0;
		}
		.chat > .seller {
			margin-right: 50px;
			background: #c7ecfc;
		}
		.chat > .buyer {
			margin-right: 50px;
			background: #e5f6fd;
		}
		.chat > .support {
			margin-left: 50px;
			background: #e6eecc;
		}
		.ta {
			padding: 0 12px 8px 0;
		}
		textarea {
			width: 100%;
		}
		#ws_status {
			display: inline-block;
			width: 10px;
			height: 10px;
			border-radius: 5px;
			background: red;
			margin-bottom: 5px;
		}
		#ws_status.connected {
			background: #01B10E;
		}
	</style>
	<div id="ws_status"></div><?

	$tabControl->Begin();

	foreach ($messages as $key => $chat)
	{
		$tabControl->BeginNextTab();

		?>
		<form class="chat_form" method="POST"><?
			echo bitrix_sessid_post();
			?>
			<input type="hidden" name="KEY" value="<?= $key ?>"/>
			<div class="chat"><?
				foreach ($chat as $message)
				{
					if ($message['USER'])
					{
						$class = 'seller';
						if ($deal['BUYER'] == $message['USER'])
							$class = 'buyer';
						$user = \Local\User\User::getById($message['USER']);
						$url = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=9&type=user&ID=' . $message['USER'] . '&lang=ru&find_section_section=-1';
						$userName = '<a href="' . $url . '">' . $user['nickname'] . '</a> (' . $user['name'] . ')';
					}
					else
					{
						$class = 'support';
						$userName = 'Служба поддержки';
					}
					?>
					<dl class="<?= $class ?>">
					<dt>[<?= $message['DATE'] ?>] <b><?= $userName ?></b></dt>
					<dd><?= $message['MESSAGE'] ?></dd>
					</dl><?
				}

				?>
			</div>
			<div class="ta"><textarea name="MESSAGE" rows=3 cols=45></textarea></div>
			<div><input type="submit" name="send" value="Ответить" class="adm-btn-save"></div>
		</form><?
	}

	if ($item['IBLOCK_ID'] == $dealsIblockId)
	{
		$tabControl->BeginNextTab();

		$deal = \Local\Data\Deal::getById($item['ID']);

		$url = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=2&type=main&ID=' . $deal['ID'] . '&lang=ru&find_section_section=-1';
		$dealName = '<a href="' . $url . '">' . $deal['ID'] . '</a>';
		$url = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=1&type=main&ID=' . $deal['AD'] . '&lang=ru&find_section_section=-1';
		$adName = '<a href="' . $url . '">' . $deal['NAME'] . '</a>';
		$seller = \Local\User\User::getById($deal['SELLER']);
		$url = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=9&type=user&ID=' . $seller['id'] . '&lang=ru&find_section_section=-1';
		$sellerName = '<a href="' . $url . '">' . $seller['nickname'] . '</a> (' . $seller['name'] . ')';
		$buyer = \Local\User\User::getById($deal['BUYER']);
		$url = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=9&type=user&ID=' . $buyer['id'] . '&lang=ru&find_section_section=-1';
		$buyerName = '<a href="' . $url . '">' . $buyer['nickname'] . '</a> (' . $buyer['name'] . ')';
		$status = \Local\Data\Status::getByCode($deal['STATUS']);

		?>
		<tr>
			<td class="adm-detail-content-cell-l">Сделка:</td>
			<td class="adm-detail-content-cell-r"><?= $dealName ?></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l">Объявление:</td>
			<td class="adm-detail-content-cell-r"><?= $adName ?></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l">Продавец:</td>
			<td class="adm-detail-content-cell-r"><?= $sellerName ?></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l">Покупатель:</td>
			<td class="adm-detail-content-cell-r"><?= $buyerName ?></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l">Статус:</td>
			<td class="adm-detail-content-cell-r"><?= $status['NAME'] ?></td>
		</tr>
		<?

		foreach ($deal['ALLOWED']['status'][3] as $statusCode => $v)
		{
			$status = \Local\Data\Status::getByCode($statusCode);
			?>
			<tr>
				<td class="adm-detail-content-cell-l">Перевести в статус "<?= $status['NAME'] ?>"</td>
				<td class="adm-detail-content-cell-r">
					<form method="POST"><?
						echo bitrix_sessid_post();
						?><input type="submit" name="updatestatus" value="<?= $statusCode ?>"
					             class="adm-btn-save"></form>
				</td>
			</tr><?
		}
	}

	$tabControl->End();

	//$tabControl->ShowWarnings("impform", $message);

}
else
{
	$APPLICATION->SetTitle('Чаты');

	$sTableID = "tbl_chats"; // ID таблицы
	$oSort = new \CAdminSorting($sTableID, "ID", "desc"); // объект сортировки
	$arOrder = (strtoupper($by) === "ID" ? array($by => $order) : array(
		$by => $order,
		"ID" => "ASC"
	));
	$lAdmin = new \CAdminList($sTableID, $oSort); // основной объект списка

	//
	// ФИЛЬТР
	//

	// опишем элементы фильтра
	$FilterArr = Array(
		'type',
		'deal_id',
		'user_id',
	);

	// инициализируем фильтр
	$lAdmin->InitFilter($FilterArr);

	if (!$type)
		$type = 'A';

	// создадим массив фильтрации
	$iblock = 0;
	if ($type == 'A')
		$iblock = array(
			$usersIblockId,
			$dealsIblockId
		);
	elseif ($type == 'D')
		$iblock = $dealsIblockId;
	elseif ($type == 'U')
		$iblock = $usersIblockId;
	$arFilter = Array(
		"IBLOCK_ID" => $iblock,
	);
	if ($deal_id && ($type == 'A' || $type == 'D'))
		$arFilter['=ID'] = $deal_id;
	if ($user_id && ($type == 'A' || $type == 'U'))
		$arFilter['=ID'] = $user_id;
	if ($need_answer)
		$arFilter['=SORT'] = 555;
	else
		$arFilter['>SORT'] = 550;

	// ==========================================================
	// ВЫБОРКА ЭЛЕМЕНТОВ СПИСКА
	// ==========================================================
	$rsData = $iblockElement->GetList($arOrder, $arFilter);
	$rsData = new CAdminResult($rsData, $sTableID);
	$rsData->NavStart();
	$lAdmin->NavText($rsData->GetNavPrint(''));

	//
	// ПОДГОТОВКА СПИСКА К ВЫВОДУ
	//

	$lAdmin->AddHeaders(array(
		array(
			"id" => "ID",
			"content" => "ID",
			"sort" => "id",
			"align" => "right",
			"default" => true,
		),
		array(
			"id" => "OBJECT",
			"content" => 'Пользователь или сделка',
			"default" => true,
		),
		array(
			"id" => "NA",
			"sort" => "SORT",
			"content" => 'Требуется ответ',
			"default" => true,
		),
		array(
			"id" => "DATE",
			"sort" => "XML_ID",
			"content" => 'Дата',
			"default" => true,
		),
	));

	$arModelByTopicId = array();
	while ($item = $rsData->NavNext(true, "f_"))
	{

		// создаем строку. результат - экземпляр класса CAdminListRow
		$row =& $lAdmin->AddRow($item['ID'], $item);

		if ($item['IBLOCK_ID'] == $dealsIblockId)
		{
			$deal = \Local\Data\Deal::getById($item['ID']);
			$url = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $item['IBLOCK_ID'] . '&type=main&ID=' . $item['ID'] . '&lang=ru&find_section_section=-1';
			$row->AddViewField("OBJECT", 'Сделка: <a href="' . $url . '">' . $deal['NAME'] . '</a>');
		}
		if ($item['IBLOCK_ID'] == $usersIblockId)
		{
			$user = \Local\User\User::getById($item['ID']);
			$url = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $item['IBLOCK_ID'] . '&type=user&ID=' . $item['ID'] . '&lang=ru&find_section_section=-1';
			$row->AddViewField("OBJECT", 'Пользователь: <a href="' . $url . '">' . $user['nickname'] . '</a> (' . $user['name'] . ')');
		}

		$row->AddViewField("NA", $item['SORT'] == 555 ? 'Да' : '');
		$row->AddViewField("DATE", ConvertTimeStamp($item['XML_ID'], "FULL"));

		// сформируем контекстное меню
		$arActions = Array();

		$arActions[] = array(
			"ICON" => "edit",
			"DEFAULT" => true,
			"TEXT" => 'Перейти к сообщениям',
			"ACTION" => $lAdmin->ActionRedirect('chat.php?DETAIL=' . $item['ID']),
		);

		// применим контекстное меню к строке
		$row->AddActions($arActions);

	}

	// резюме таблицы
	$lAdmin->AddFooter(array(
		array(
			"title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"),
			"value" => $rsData->SelectedRowsCount()
		),
		// кол-во элементов
		array(
			"counter" => true,
			"title" => GetMessage("MAIN_ADMIN_LIST_CHECKED"),
			"value" => "0"
		),
		// счетчик выбранных элементов
	));

	// альтернативный вывод
	$lAdmin->CheckListMode();

	require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

	?>
	<style>
		#ws_status {
			display: inline-block;
			width: 10px;
			height: 10px;
			border-radius: 5px;
			background: red;
			margin-bottom: 5px;
		}
		#ws_status.connected {
			background: #01B10E;
		}
	</style><?

	//
	// ВЫВОД ФИЛЬТРА
	//

	// создадим объект фильтра
	$oFilter = new CAdminFilter($sTableID . "_filter", array(
			"Тип",
			"Требуется ответ",
			"ID сделки",
			"ID пользователя",
		));
	?>
	<form name="find_form" method="get" action="<? echo $APPLICATION->GetCurPage(); ?>">
		<? $oFilter->Begin(); ?>
		<tr>
			<td>Тип:</td>
			<td>
				<select name="type">
					<option value="A"<? if ($type == "A")
						echo " selected" ?>>(любой)
					</option>
					<option value="U"<? if ($type == "U")
						echo " selected" ?>>Чаты со службой поддержки
					</option>
					<option value="D"<? if ($type == "D")
						echo " selected" ?>>Чаты в рамках сделок
					</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>Требуется ответ:</td>
			<td><input type="checkbox" name="need_answer" value="1" <?= $need_answer ? 'checked' : '' ?> /></td>
		</tr>
		<tr>
			<td>ID сделки:</td>
			<td><input type="text" name="deal_id" size="10" value="<?= htmlspecialcharsex($deal_id) ?>"/></td>
		</tr>
		<tr>
			<td>ID пользователя:</td>
			<td><input type="text" name="user_id" size="10" value="<?= htmlspecialcharsex($user_id) ?>"/></td>
		</tr>

		<?
		$oFilter->Buttons(array(
				"table_id" => $sTableID,
				"url" => $APPLICATION->GetCurPage(),
				"form" => "find_form"
			));
		$oFilter->End();
	?>
		<div id="ws_status"></div>
	</form><?

	// выведем таблицу списка элементов
	$lAdmin->DisplayList();
}

$assetInstance = \Bitrix\Main\Page\Asset::getInstance();
$assetInstance->addJs('/admin/jquery.js');
$assetInstance->addJs('/admin/chat.js');

// завершение страницы
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>