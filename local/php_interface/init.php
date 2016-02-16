<?

\Bitrix\Main\Loader::includeModule('iblock');

$lib = '/local/php_interface/lib/';
CModule::AddAutoloadClasses(
	'',
	array(
		'Local\\Common\\StaticCache' => $lib . 'common/StaticCache.php',
		'Local\\Common\\ExtCache' => $lib . 'common/ExtCache.php',
		'Local\\Common\\Utils' => $lib . 'common/Utils.php',
		'Local\\Api\\Api' => $lib . 'api/api.php',
		'Local\\Api\\v1' => $lib . 'api/v1.php',
		'Local\\Api\\ApiException' => $lib . 'api/ApiException.php',
		'Local\\Data\\Faq' => $lib . 'data/faq.php',
	)
);

if(!function_exists('DebugMessage')) {
	function DebugMessage($message, $title = false, $color = '#008B8B') {
		?><table border="0" cellpadding="5" cellspacing="0" style="border:1px solid <?=$color?>;margin:2px;"><tr><td style="color:<?=$color?>;font-size:11px;font-family:Verdana;"><?
			if(strlen($title)) {
				?><p>[<?=$title?>]</p><?
			}
			if (is_array($message) || is_object($message)) {
				echo '<pre>'; print_r($message); echo '</pre>';
			}
			else {
				var_dump($message);
			}
		?></td></tr></table><?
	}
}