<?
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

$wsdlurl = 'https://tracking.russianpost.ru/rtm34?wsdl';
$client = new SoapClient($wsdlurl, array(
		'trace' => 1,
		'soap_version' => SOAP_1_2
	));
$params = array(
	'OperationHistoryRequest' => array(
		'Barcode' => '62009201026161',
		'MessageType' => '0',
		'Language' => 'RUS'
	),
	'AuthorizationHeader' => array(
		'login' => 'iRxVGBkwzPuDuQ',
		'password' => 'lapNdMaUkwF1'
	)
);

$result = $client->getOperationHistory(new SoapParam($params, 'OperationHistoryRequest'));
if ($result->OperationHistoryData && $result->OperationHistoryData->historyRecord)
	foreach ($result->OperationHistoryData->historyRecord as $record)
	{
		$date = $record->OperationParameters->OperDate;
		$operType = '';
		if ($record->OperationParameters->OperType && $record->OperationParameters->OperType->Name)
			$operType = $record->OperationParameters->OperType->Name;
		$operAttr = '';
		if ($record->OperationParameters->OperAttr && $record->OperationParameters->OperAttr->Name)
			$operAttr = $record->OperationParameters->OperAttr->Name;
		$operAddress = '';
		if ($record->AddressParameters && $record->AddressParameters->OperationAddress)
			$operAddress = $record->AddressParameters->OperationAddress->Description;
		$destAddress = '';
		if ($record->AddressParameters && $record->AddressParameters->DestinationAddress)
			$destAddress = $record->AddressParameters->DestinationAddress->Description;
		$name = '';
		if ($record->ItemParameters)
			$name = $record->ItemParameters->ComplexItemName;
		$sndr = '';
		if ($record->UserParameters)
			$sndr = $record->UserParameters->Sndr;
		$rcpn = '';
		if ($record->UserParameters)
			$rcpn = $record->UserParameters->Rcpn;
		$item = array(
			'Date' => $record->OperationParameters->OperDate,
			'OperType' => $operType,
			'OperAttr' => $operAttr,
			'OperationAddress' => $operAddress,
			'DestinationAddress' => $destAddress,
			'Name' => $name,
			'Sndr' => $sndr,
		    'Rcpn' => $rcpn,
		);
		debugmessage($item);
	};