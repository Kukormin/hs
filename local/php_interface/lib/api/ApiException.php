<?

namespace Local\Api;

class ApiException extends \Exception
{
	protected $status = '';

	public function __construct($message = "", $status = '', $code = 0)
	{
		parent::__construct($message, $code);
		$this->status = $status;
	}

	public function getHttpStatus()
	{
		return $this->status;
	}
}