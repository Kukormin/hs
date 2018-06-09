<?

/** @var array $chat */
/** @var array $deal */

foreach ($chat as $message)
{
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
	<dl class="<?= $class ?>">
		<dt>[<?= $message['DATE'] ?>] <b><?= $userName ?></b></dt>
		<dd><?= $message['MESSAGE'] ?></dd>
	</dl><?
}
