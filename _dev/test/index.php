<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8"/>
	<link href="styles.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<p>
	<input id="url" type="text"
	       value="http<?= $_SERVER['HTTPS'] ? 's' : '' ?>://<?= $_SERVER['SERVER_NAME'] ?>/api/v1" size="80" />
	<input id="test_all" type="button" value="Запустить все запросы"/>
</p>
<p>
	Картинки для добавления <input id="picture" name="picture" type="file" multiple />
</p>
<table class="results">
	<tr>
		<th>Запрос</th>
		<th>Вариант</th>
		<th>Method</th>
		<th>URI</th>
		<th>Авт</th>
		<th>GET или POST параметры</th>
		<th colspan="2">Действия</th>
		<th>Ответ</th>
	</tr><?

	include 'tests.php';
	foreach ($arTests as $i => $arTest)
	{
		$needAuth = '';
		if ($arTest['AUTH'])
			if ($arTest['AUTH'] == 'x')
			{
				$auth = $arTest['AUTH'];
				$needAuth = ' data-na="1"';
			}
			else
				$auth = substr($arTest['AUTH'], 0, 6) . '...';
		else
			$auth = '';

		?>
		<tr class="test" id="r<?= $i ?>">
		<td><?= $arTest['NAME'] ?></td>
		<td><?= $arTest['VAR'] ?></td>
		<td><?= $arTest['METHOD'] ?></td>
		<td><?= $arTest['URI'] ?></td>
		<td data-auth="<?= $arTest['AUTH'] ?>"<?= $needAuth ?>><?= $auth ?></td>
		<td><input name="txt<?= $i ?>" type="text" size="100" value="<?= htmlspecialchars($arTest['DATA']) ?>" /></td>
		<td><input type="button" value="Запустить"/></td>
		<td></td>
		<td data-need="<?= $arTest['NEED'] ?>"></td>
		</tr>
		<tr class="response hidden"></tr><?
	}

	?></table><?

?>
<script type="text/javascript" charset="utf-8" src="/_dev/test/jquery-2.1.0.min.js"></script>
<script type="text/javascript" charset="utf-8" src="scripts.js"></script><?

?></body><?
?></html><?
