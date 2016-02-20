<?

namespace Local\Api;

use Local\Data\Faq;
use Local\Data\User;
use Local\Data\Ad;
use Local\Catalog\Condition;
use Local\Catalog\Color;

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
				$this->post['device'], $this->post['x'], $this->post['y']);
	}

	protected function catalog($args) {
		$method = $args[0];
		if ($method == 'condition')
			return Condition::getAppData();
		if ($method == 'color')
			return Color::getAppData();
	}

	protected function ad($args) {
		$method = $args[0];
		if ($method == 'add')
			return Ad::add();
	}
}