<?

namespace Local\Api;

use Local\Data\Faq;

class v1 extends Api
{
	/**
	 * Возвращает все вопросы и ответы вместе с разделами
	 * @return array|mixed
	 */
	protected function faq() {
		return Faq::getAll();
	}
}