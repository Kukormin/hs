<?
class Http
{
	private $timeout = 3;
	private $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:48.0) Gecko/20100101 Firefox/48.0';
	private $header = array(
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
		'Cache-Control: max-age=0',
		'Connection: keep-alive',
	);

	public function __construct($options = array())
	{
		foreach ($options as $k => $v)
		{
			if ($k == 'userAgent')
				$this->userAgent = $v;
			elseif ($k == 'timeout')
				$this->timeout = $v;
			elseif ($k == 'auth')
				$this->header[] = 'x-auth: ' . $v;
		}
	}

	public function get($url)
	{
		return $this->send($url);
	}

	public function post($url, $post, $headers = array())
	{
		return $this->send($url, $post, $headers);
	}

	private function send($url, $post = '', $headers = array())
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if (!$headers)
			$headers = $this->header;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if ($post)
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			$headers[] = 'Content-Type: application/json';
			$headers[] = 'Content-Length: ' . iconv_strlen($post);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($ch);
		//$info = curl_getinfo($ch);
		curl_close($ch);

		$decoded = array();
		if ($result)
		{
			try
			{
				$decoded = json_decode($result, true);
			}
			catch (\Exception $e)
			{
				$decoded = array();
			}
		}

		return $decoded;
	}

	public function getUserAgent()
	{
		return $this->userAgent;
	}

	public function getHeaders()
	{
		return $this->header;
	}

}
