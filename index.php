<?
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

die();
?>
<style>
	p {margin: 3px 0;}
</style><?

$sport = array(
	'Бег' => array(
		'100м',
		'400м',
		'3км',
		'10км',
	),
	'Игровые виды' => array(
		'футбол',
		'волейбол',
		'баскетбол',
		'гандбол',
	),
	'Плавание' => array(
		'кроль',
		'брасс',
		'спина',
		'баттерфляй',
	),
);
$data = array(
	'Антон' => array(2,3,6,7),
	'Вадим' => array(4,8,10,11),
	'Егор' => array(5,6,8,10),
	'Илья' => array(9,10,11,12),
	'Олег' => array(1,2,4,10),
);

$s = file_get_contents('ans.txt');
$ans = unserialize($s);
if (!$ans)
	$ans = array();
$try = count($ans);

if ($_POST['post'] == 1)
{
	$k = 0;
	$correct = true;
	foreach ($data as $v => $ar)
	{
		$k++;
		$post = $_POST['n' . $k];
		for ($i = 1; $i <= 12; $i++)
		{
			$q = in_array($i, $ar);
			$a = in_array($i, $post);
			if ($a)
				$ans[$try]['ITEMS'][$k][$i] = 1;
			if ($q != $a)
				$correct = false;
		}
	}
	$ans[$try]['CORRECT'] = $correct;

	file_put_contents('ans.txt', serialize($ans));

	LocalRedirect($APPLICATION->GetCurDir());
}

$post = $ans[$try - 1];

if ($post['CORRECT'] || $try >= 3)
{
	if ($post['CORRECT'])
	{
		?>
		<p>Задачка эта для кого-то</p>
		<p>Могла стать даже пыткой</p><?

		if ($try == 1)
		{
			?>
			<p>Но ты с ней справился отлично</p>
			<p>Всего с одной попытки</p><?
		}
		elseif ($try == 2)
		{
			?>
			<p>Но ты с ней справился неплохо</p>
			<p>Затратив две попытки</p><?
		}
		elseif ($try == 2)
		{
			?>
			<p>Но ты с ней справился неплохо</p>
			<p>Затратив три попытки</p><?
		}

		?>
		<p>Вот тебе целеуказанье</p>
		<p>Такое новое заданье:</p><?
	}
	else
	{
		?>
		<p>Смотрю, задачка оказалась</p>
		<p>Для вас чуть ли не пыткой</p>
		<p>Ведь даже вы не уложились</p>
		<p>В дозволенные 3 попытки</p>
		<p>Но здесь не будет наказанья</p>
		<p>А просто новое заданье:</p><?
	}

	?>
	<hr />
	<p>Я вышел из "бункера"</p>
	<p>Дорогой юго-восточной,</p>
	<p>Любовался природой</p>
	<p>Считая листочки.</p>
	<p>Десять тысяч листочков,</p>
	<p>Десять тысяч кустов,</p>
	<p>Десять тысяч деревьев,</p>
	<p>Десять тысяч цветов.</p>
	<p>Шел не очень-то долго</p>
	<p>И увидел табличку,</p>
	<p>На ней будет подсказка -</p>
	<p>Опять десять тысяч</p>
<?
}
else
{

	?>
	<p>Спортивные друзья</p>
	<p>Проверят здесь тебя -</p>
	<p>Такая вот игра...</p>
	<p>Давно уже пора</p>
	<p>Загадку разгадать,</p>
	<p>А можно угадать</p>
	<p>Попробовать тебе -</p>
	<p>Прислушаться к судьбе.</p>
	<p>Внимательно смотри:</p>
	<p>Попыток только 3</p>
	<hr/>
	<form method="POST">
	<?


	$k = 0;
	foreach ($data as $v => $ar)
	{
		$k++;
		?>
		<label for="n<?= $k ?>"><?= $v ?></label>
		<br/>
		<select id="n<?= $k ?>" name="n<?= $k ?>[]" multiple size="15"><?

			$value = 0;
			foreach ($sport as $vid => $disc)
			{
				?>
				<optgroup label="<?= $vid ?>"><?

				foreach ($disc as $name)
				{
					$value++;
					$selected = $post['ITEMS'][$k][$value] ? ' selected' : '';
					?>
					<option value="<?= $value ?>"<?= $selected ?>><?= $name ?></option><?
				}

				?>
				</optgroup><?
			}
			?>
		</select><br/><br/><?
	}

	?>
	<p style="color:red;">Осталось попыток: <?= (3 - $try) ?><p>
	<input type="submit" value="Проверить"/>
	<input type="hidden" name="post" value="1"/>
	</form><?

	if ($try > 0)
	{
		?>
		<hr/>
		<h1>История попыток</h1><?

		foreach ($ans as $j => $post)
		{
			?><h2>Попытка <?= ($j + 1) ?></h2><?
			$k = 0;
			foreach ($data as $v => $ar)
			{
				?>
				<p>&nbsp;&nbsp;&nbsp;&nbsp;<b><?= $v ?></b>: <?
				$k++;
				$value = 0;
				foreach ($sport as $vid => $disc)
				{
					foreach ($disc as $name)
					{
						$value++;
						if ($post['ITEMS'][$k][$value])
							echo $name . ', ';
					}
				}
				?>
				</p><?
			}
		}
	}
}

