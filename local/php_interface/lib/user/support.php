<?

namespace Local\User;
use Local\Data\Deal;
use Local\Data\Messages;

/**
 * Class Support Служба поддержки
 * @package Local\User
 */
class Support
{

	public static function message($key, $message)
	{
		$id = Messages::add($key, 0, $message);

		$ar = explode('|', $key);
		$type = $ar[0];
		$oid = $ar[1];
		$users = [];
		$suffix = 0;
		if ($type == 'u')
		{
			User::updateChatInfo($oid, true);
			$users = [0, $oid];
			$suffix = 0;
			User::push(
				$oid,
				'Служба поддержки: ' . $message,
				['type' => 'support']
			);
		}
		elseif ($type == 'd')
		{
			Deal::updateChatInfo($oid, true);
			$deal = Deal::getById($oid);
			$users = [0];
			if ($ar[2] != 1)
			{
				$users[] = $deal['BUYER'];
				User::push(
					$deal['BUYER'],
					'Служба поддержки: ' . $message,
					['type' => 'deal_support', 'dealId' => intval($oid), 'role' => 'buyer']
				);
			}
			if ($ar[2] < 2)
			{
				$users[] = $deal['SELLER'];
				User::push(
					$deal['SELLER'],
					'Служба поддержки: ' . $message,
					['type' => 'deal_support', 'dealId' => intval($oid), 'seller']
				);
			}
			$suffix = $ar[2];
		}

		return [
			'oid' => $oid,
			'ot' => $type,
			'id' => $id,
			'role' => 0,
			'suffix' => $suffix,
			'push' => 0,
			'users' => $users,
		];
	}

}