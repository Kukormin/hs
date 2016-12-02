<?
namespace Local\Common;

/**
 * Class Tracking Отслеживание посылок
 */
class Tracking
{
	const WDSL = 'https://tracking.russianpost.ru/rtm34?wsdl';
	const LOGIN = 'cTkUJPjERACatK';
	const PASS = 'tk0GbMhC3SIZ';

	public static function track($code)
	{
		$return = array();

		$client = new \SoapClient(self::WDSL, array(
			'trace' => 1,
			'soap_version' => SOAP_1_2
		));
		$params = array(
			'OperationHistoryRequest' => array(
				'Barcode' => $code,
				'MessageType' => '0',
				'Language' => 'RUS',
			),
			'AuthorizationHeader' => array(
				'login' => self::LOGIN,
				'password' => self::PASS,
			)
		);
		$result = $client->getOperationHistory(new \SoapParam($params, 'OperationHistoryRequest'));
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
					'Date' => $date,
					'OperType' => $operType,
					'OperAttr' => $operAttr,
					'OperationAddress' => $operAddress,
					'DestinationAddress' => $destAddress,
					'Name' => $name,
					'Sndr' => $sndr,
					'Rcpn' => $rcpn,
				);
				$return[] = $item;
			};

		return $return;
	}


}
