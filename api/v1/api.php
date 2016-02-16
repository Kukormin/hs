<?
use Local\Api\v1;
use Local\Api\ApiException;

include_once $_SERVER['DOCUMENT_ROOT'] . 'api/bxinit.php';

try
{
	$api = new v1($_REQUEST['request']);
	echo $api->processAPI();
}
catch (ApiException $e)
{
	header('HTTP/1.1 ' . $e->getHttpStatus());
	echo json_encode(Array(
		'error' => $e->getMessage(),
		'code' => $e->getCode(),
	), JSON_UNESCAPED_UNICODE);
}
catch (\Exception $e)
{
	header('HTTP/1.1 500 Internal Server Error');
	echo json_encode(Array(
		'error' => $e->getMessage(),
		'code' => $e->getCode(),
	), JSON_UNESCAPED_UNICODE);
}