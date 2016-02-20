<?

namespace Local\Api;

use Local\Common\Utils;

class ApiException extends \Exception
{
	/**
	 * @var int|string http статус
	 */
	protected $status = '';
	/**
	 * @var array строковые коды ошибок
	 */
	protected $errors = array();

	/**
	 * Выкидывает исключение
	 * @param array $errors строковые коды ошибок
	 * @param int $status HTTP статус
	 * @param string $message сообщение
	 */
	public function __construct($errors = [], $status = 500, $message = '')
	{
		parent::__construct($message);
		$this->status = $status;
		$this->errors = $errors;
	}

	public function getHttpStatus()
	{
		return Utils::getHttpStatusByCode($this->status);
	}

	public function getErrors()
	{
		return $this->errors;
	}
}