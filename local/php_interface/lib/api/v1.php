<?

namespace Local\Api;

use Local\Data\Faq;
use Local\Data\User;
use Local\Data\Ad;
use Local\Catalog\Condition;
use Local\Catalog\Color;
use Local\Catalog\Catalog;
use Local\Catalog\Size;
use Local\Catalog\Payment;
use Local\Catalog\Delivery;
use Local\Catalog\Brand;

class v1 extends Api
{
	/**
	 * Возвращает все вопросы и ответы вместе с разделами
	 * @return array|mixed
	 */
	protected function faq() {
		return Faq::getAll(true);
	}

	protected function auth($args) {
		$method = $args[0];
		if ($method == 'phone')
			return User::authByPhone($this->post['phone']);
		elseif ($method == 'verify')
			return User::verify($this->post['phone'], $this->post['code'], $this->post['user'],
				$this->post['device']);
		else
			throw new ApiException(['wrong_endpoint'], 404);
	}

	protected function catalog($args) {
		$method = $args[0];
		if ($method == 'condition')
			return Condition::getAppData();
		elseif ($method == 'color')
			return Color::getAppData();
		elseif ($method == 'section')
			return Catalog::getAppData();
		elseif ($method == 'size')
			return Size::getAppData($args[1]);
		elseif ($method == 'payment')
			return Payment::getAppData();
		elseif ($method == 'delivery')
			return Delivery::getAppData();
		elseif ($method == 'brand')
			return Brand::getAppData();
		elseif ($method == 'addbrand')
			return Brand::add($this->post['name']);
		else
			throw new ApiException(['wrong_endpoint'], 404);
	}

	protected function ad($args) {
		$method = $args[0];
		if ($method == 'add')
			return Ad::add($this->post['section'], $this->post['brand'], $this->post['condition'],
				$this->post['color'], $this->post['size'], $this->post['material'], $this->post['features'],
				$this->post['purchase'], $this->post['price'], $this->post['payment'], $this->post['delivery']);
		else
			throw new ApiException(['wrong_endpoint'], 404);
	}
}