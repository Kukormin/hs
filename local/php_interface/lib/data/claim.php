<?

namespace Local\Data;

use Local\Api\ApiException;
use Local\Common\ExtCache;
use Local\Common\Utils;
use Local\User\Auth;

/**
 * Class Claim Причины жалоб
 * @package Local\Data
 */
class Claim
{
	/**
	 * Путь для кеширования
	 */
	const CACHE_PATH = 'Local/Data/Claim/';

	/**
	 * Возвращает все причины жалоб
	 * (учитывает теговый кеш)
	 * @param bool $refreshCache для принудительного сброса кеша
	 * @return array|mixed
	 */
	public static function getAll($refreshCache = false) {
		$return = array();

		$extCache = new ExtCache(
			array(
				__FUNCTION__,
			),
			static::CACHE_PATH . __FUNCTION__ . '/',
			86400 * 20
		);
		if(!$refreshCache && $extCache->initCache()) {
			$return = $extCache->getVars();
		} else {
			$extCache->startDataCache();

			$iblockId = Utils::getIBlockIdByCode('claim');

			$iblockElement = new \CIBlockElement();
			$rsItems = $iblockElement->GetList(array('SORT' => 'ASC'), array(
				'IBLOCK_ID' => $iblockId,
			), false, false, array(
				'ID', 'NAME', 'ACTIVE',
			));
			while ($item = $rsItems->Fetch())
			{
				$id = intval($item['ID']);
				$return[$id] = array(
					'ID' => $id,
				    'NAME' => $item['NAME'],
				    'ACTIVE' => $item['ACTIVE'],
				);
			}

			$extCache->endDataCache($return);
		}

		return $return;
	}

	/**
	 * Возвращает активные причины жалоб
	 * @return array
	 */
	public static function getAppData() {
		$all = self::getAll();

		$return = array();
		foreach ($all as $item)
			if ($item['ACTIVE'] == 'Y')
				$return[] = array(
					'id' => $item['ID'],
					'name' => $item['NAME'],
				);

		return $return;
	}

	/**
	 * Возвращает причину жалобы по ID
	 * @param $id
	 * @return mixed
	 */
	public static function getById($id) {
		$all = self::getAll();
		return $all[$id];
	}

	/**
	 * Добавление жалобы на объявление
	 * @param $adId
	 * @param $reasonId
	 * @return array
	 * @throws ApiException
	 */
	public static function add($adId, $reasonId) {
		// Проверяем авторизацию (выкинет исключение, если неавторизован)
		$session = Auth::check();
		$userId = $session['USER_ID'];

		if (!$adId)
			throw new ApiException(['wrong_ad'], 400);

		$ad = Ad::getById($adId);
		if (!$ad)
			throw new ApiException(['ad_not_found'], 400);

		if (!$reasonId)
			throw new ApiException(['wrong_reason'], 400);

		$reason = self::getById($reasonId);
		if (!$reason)
			throw new ApiException(['wrong_reason'], 400);

		$emailFields = array(
			"DATE_TIME" => date('d.m.Y H:i'),
			"AD" => $adId,
			"REASON" => $reason['NAME'],
			"USER" => $userId,
		);
		$event = new \CEvent();
		$event->Send("AD_CLAIM", SITE_ID, $emailFields);

		return array();
	}
}