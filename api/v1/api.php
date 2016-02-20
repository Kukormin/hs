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
	$return = array(
		'errors' => $e->getErrors(),
	);
	if ($e->getMessage())
		$return['message'] = $e->getMessage();
	echo json_encode($return, JSON_UNESCAPED_UNICODE);
}
catch (\Exception $e)
{
	header('HTTP/1.1 500 Internal Server Error');
	echo json_encode(Array(
		'errors' => ['unknown_error'],
		'code' => $e->getCode(),
		'message' => $e->getMessage(),
	), JSON_UNESCAPED_UNICODE);
}