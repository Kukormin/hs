<?
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

_log_array($_POST);

$shopId = 53177;
$shopPassword = 'v457f35MCV45v73f3da';

$tmp = $_POST['action'] . ';' .
	$_POST['orderSumAmount'] . ';' .
	$_POST['orderSumCurrencyPaycash'] . ';' .
	$_POST['orderSumBankPaycash'] . ';' .
	$shopId . ';' .
	$_POST['invoiceId'] . ';' .
	$_POST['customerNumber'] . ';' .
	$shopPassword;

$hash = md5($tmp);

if (strtolower($hash) != strtolower($_POST['md5']))
	$code = 1;
else
	$code = 0;

print '<?xml version="1.0" encoding="UTF-8"?>';
?><checkOrderResponse performedDatetime="<?= $_POST['requestDatetime'] ?>" code="<?= $code ?>" invoiceId="<?=
$_POST['invoiceId'] ?>" shopId="<?= $configs['shopId'] ?>" /><?
