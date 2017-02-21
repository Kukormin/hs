<?php

namespace Local\Chat;

use Local\Api\ApiException;
use Local\Data\Deal;
use Local\User\Session;
use Local\User\User;

class Daemon
{
	const SOCKET_BUFFER_SIZE = 1024;
	const MAX_SOCKET_BUFFER_SIZE = 10240;
	const MAX_SOCKETS = 1000;
	const SOCKET_MESSAGE_DELIMITER = "\n";

    protected $pid;
    private $_handshakes = array();
	protected $clients = array();
	protected $_server = null;
	protected $_read = array();//read buffers
	protected $_write = array();//write buffers
	protected $_user = array();
	protected $connectByUser = array();
	public $timer = null;
	protected $logFile = '';

	public function __construct($server) {
		$this->_server = $server;
		$this->pid = posix_getpid();
		$this->logFile = __DIR__ . '/../../../../_log/chat.log';
	}

	public function start() {
		$this->onStart();

		if ($this->timer) {
			$timer = $this->_createTimer();
		}

		while (true) {
			//prepare the array of sockets that need to be processed
			$read = $this->clients;

			if ($this->_server) {
				$read[] = $this->_server;
			}

			if ($this->timer) {
				$read[] = $timer;
			}

			if (!$read) {
				return;
			}

			$write = array();

			if ($this->_write) {
				foreach ($this->_write as $connectionId => $buffer) {
					if ($buffer) {
						$write[] = $this->getConnectionById($connectionId);
					}
				}
			}

			$except = $read;

			stream_select($read, $write, $except, null);//update the array of sockets that can be processed

			if ($this->timer && in_array($timer, $read)) {
				unset($read[array_search($timer, $read)]);
				fread($timer, self::SOCKET_BUFFER_SIZE);
				$this->onTimer();
			}

			if ($read) {//data were obtained from the connected clients
				foreach ($read as $client) {
					if ($this->_server == $client) { //the server socket got a request from a new client
						if ((count($this->clients) < self::MAX_SOCKETS) && ($client = @stream_socket_accept($this->_server, 0))) {
							stream_set_blocking($client, 0);
							$clientId = $this->getIdByConnection($client);
							$this->clients[$clientId] = $client;
							$this->_onOpen($clientId);
						}
					} else {
						$connectionId = $this->getIdByConnection($client);

						if (!$this->_read($connectionId)) { //connection has been closed or the buffer was overwhelmed
							$this->close($connectionId);
							continue;
						}

						$this->_onMessage($connectionId);
					}
				}
			}

			if ($write) {
				foreach ($write as $client) {
					if (is_resource($client)) {//verify that the connection is not closed during the reading
						$this->_sendBuffer($client);
					}
				}
			}

			if ($except) {
				foreach ($except as $client) {
					$this->_onError($this->getIdByConnection($client));
				}
			}
		}
	}

	protected function _onError($connectionId) {
		echo "An error has occurred: $connectionId\n";
		die();
	}

	protected function _write($connectionId, $data, $delimiter = '') {
		@$this->_write[$connectionId] .=  $data . $delimiter;
	}

	protected function _sendBuffer($connect) {
		$connectionId = $this->getIdByConnection($connect);
		$written = fwrite($connect, $this->_write[$connectionId], self::SOCKET_BUFFER_SIZE);
		$this->_write[$connectionId] = substr($this->_write[$connectionId], $written);
	}

	protected function _readFromBuffer($connectionId) {
		$data = '';

		if (false !== ($pos = strpos($this->_read[$connectionId], self::SOCKET_MESSAGE_DELIMITER))) {
			$data = substr($this->_read[$connectionId], 0, $pos);
			$this->_read[$connectionId] = substr($this->_read[$connectionId], $pos + strlen(self::SOCKET_MESSAGE_DELIMITER));
		}

		return $data;
	}

	protected function _read($connectionId) {
		$data = fread($this->getConnectionById($connectionId), self::SOCKET_BUFFER_SIZE);

		if (!strlen($data)) return false;

		@$this->_read[$connectionId] .= $data;//add the data into the read buffer
		return strlen($this->_read[$connectionId]) < self::MAX_SOCKET_BUFFER_SIZE;
	}

	protected function _createTimer() {
		$pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

		$pid = pcntl_fork();//create a fork

		if ($pid == -1) {
			die("error: pcntl_fork\r\n");
		} elseif ($pid) { //parent
			fclose($pair[0]);
			return $pair[1];//one of the pair will be in the parent
		} else { //child process
			fclose($pair[1]);
			$parent = $pair[0];//second of the pair will be in the child

			while (true) {
				fwrite($parent, '1');

				usleep($this->timer * 1000000);
			}
		}

		return 0;
	}

    protected function _onOpen($connectionId) {
        $this->_handshakes[$connectionId] = '';//mark the connection that it needs a handshake
    }

    protected function _onMessage($connectionId) {
        if (isset($this->_handshakes[$connectionId])) {
            if ($this->_handshakes[$connectionId]) {//if the client has already made a handshake
                return;//then there does not need to read before sending the response from the server
            }

            if (!$this->_handshake($connectionId)) {
                $this->close($connectionId);
            }
        } else {
            while (($data = $this->_decode($connectionId)) && mb_check_encoding($data['payload'], 'utf-8')) {//decode buffer (there may be multiple messages)
                $this->onMessage($connectionId, $data['payload'], $data['type']);//call user handler
            }
        }
    }

    protected function close($connectionId) {
        if (isset($this->_handshakes[$connectionId])) {
            unset($this->_handshakes[$connectionId]);
        } elseif (isset($this->clients[$connectionId])) {
            $this->onClose($connectionId);//call user handler
        }

	    @fclose($this->getConnectionById($connectionId));

        if (isset($this->clients[$connectionId])) {
            unset($this->clients[$connectionId]);
        } elseif ($this->getIdByConnection($this->_server) == $connectionId) {
            $this->_server = null;
        }

	    if (isset($this->_user[$connectionId]))
	    {
		    $userId = $this->_user[$connectionId];
		    if (isset($this->connectByUser[$userId]))
			    unset($this->connectByUser[$userId]);
		    unset($this->_user[$connectionId]);
	    }


        unset($this->_write[$connectionId]);
        unset($this->_read[$connectionId]);
    }

    protected function sendToClient($connectionId, $data, $type = 'text') {
        if (!isset($this->_handshakes[$connectionId]) && isset($this->clients[$connectionId])) {
            $this->_write($connectionId, $this->_encode($data, $type));
        }
    }

    protected function _handshake($connectionId) {
        //read the headers from the connection
        if (!strpos($this->_read[$connectionId], "\r\n\r\n")) {
            return true;
        }

        preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $this->_read[$connectionId], $match);

        if (empty($match[1])) {
            return false;
        }

        $headers = explode("\r\n", $this->_read[$connectionId]);
        $info = array();

        foreach ($headers as $header) {
            if (($explode = explode(':', $header)) && isset($explode[1])) {
                $info[trim($explode[0])] = trim($explode[1]);
            } elseif (($explode = explode(' ', $header)) && isset($explode[1])) {
                $info[$explode[0]] = $explode[1];
            }
        }

	    $authToken = $info['x-auth'];
	    if (!$authToken)
		    $authToken = $info['X-Auth'];

	    /*$userId = 0;
	    if ($authToken)
	    {
		    $session = Session::getByToken($authToken);
		    if ($session)
			    $userId = $session['USER_ID'];
	    }*/

	    if (strpos($info['User-Agent'], 'AppleWebKit') !== false)
	        $userId = 54;
	    else
		    $userId = 2767;
	    $info['USER_ID'] = $userId;
	    if (!$userId)
		    return false;

	    $this->log(print_r($info, true));
        /*$source = explode(':', stream_socket_get_name($this->clients[$connectionId], true));
        $info['Ip'] = $source[0];*/

        $this->_read[$connectionId] = '';
        $this->_user[$connectionId] = $userId;
	    $this->connectByUser[$userId] = $connectionId;

        //send a header according to the protocol websocket
        $SecWebSocketAccept = base64_encode(pack('H*', sha1($match[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: {$SecWebSocketAccept}\r\n\r\n";

        $this->_write($connectionId, $upgrade);
        unset($this->_handshakes[$connectionId]);

        $this->onOpen($connectionId, $info);

        return true;
    }

    protected function _encode($payload, $type = 'text')
    {
        $frameHead = array();
        $payloadLength = iconv_strlen($payload);

        switch ($type) {
            case 'text':
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = 129;
                break;

            case 'close':
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = 136;
                break;

            case 'ping':
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = 137;
                break;

            case 'pong':
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = 138;
                break;
        }

        if ($payloadLength > 65535) {
            $ext = pack('NN', 0, $payloadLength);
            $secondByte = 127;
        } elseif ($payloadLength > 125) {
            $ext = pack('n', $payloadLength);
            $secondByte = 126;
        } else {
            $ext = '';
            $secondByte = $payloadLength;
        }

        return $data  = chr($frameHead[0]) . chr($secondByte) . $ext . $payload;
    }

    protected function _decode($connectionId)
    {
        $data = $this->_read[$connectionId];
	    $l = iconv_strlen($data);

        if ($l < 2) return false;

        $unmaskedPayload = '';
        $decodedData = array();

        // estimate frame type:
        $firstByteBinary = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        $isMasked = $secondByteBinary[0] == '1';
        $payloadLength = ord($data[1]) & 127;

        switch ($opcode) {
            // text frame:
            case 1:
                $decodedData['type'] = 'text';
                break;

            case 2:
                $decodedData['type'] = 'binary';
                break;

            // connection close frame:
            case 8:
                $decodedData['type'] = 'close';
                break;

            // ping frame:
            case 9:
                $decodedData['type'] = 'ping';
                break;

            // pong frame:
            case 10:
                $decodedData['type'] = 'pong';
                break;

            default:
                $decodedData['type'] = '';
        }

        if ($payloadLength === 126) {
            if ($l < 4) return false;
            $payloadOffset = 8;
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
        } elseif ($payloadLength === 127) {
            if ($l < 10) return false;
            $payloadOffset = 14;
            for ($tmp = '', $i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp) + $payloadOffset;
        } else {
            $payloadOffset = 6;
            $dataLength = $payloadLength + $payloadOffset;
        }

	    $log = array(
		    $data,
		    $l,
		    $firstByteBinary,
		    $secondByteBinary,
		    $opcode,
		    $isMasked,
		    $payloadLength,
		    $decodedData,
		    $dataLength,
	    );
	    //$this->log(print_r($log, true));

        if ($l < $dataLength) {
            return false;
        } else {
            $this->_read[$connectionId] = iconv_substr($data, $dataLength);
        }

        if ($isMasked) {
            if ($payloadLength === 126) {
                $mask = iconv_substr($data, 4, 4);
            } elseif ($payloadLength === 127) {
                $mask = iconv_substr($data, 10, 4);
            } else {
                $mask = iconv_substr($data, 2, 4);
            }

            for ($i = $payloadOffset; $i < $dataLength; $i++) {
                $j = $i - $payloadOffset;
                if (isset($data[$i])) {
                    $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
                }
            }
            $decodedData['payload'] = $unmaskedPayload;
        } else {
            $payloadOffset = $payloadOffset - 4;
            $decodedData['payload'] = iconv_substr($data, $payloadOffset, $dataLength - $payloadOffset);
        }

        return $decodedData;
    }

    protected function getConnectionById($connectionId) {
        if (isset($this->clients[$connectionId])) {
            return $this->clients[$connectionId];
        } elseif ($this->getIdByConnection($this->_server) == $connectionId) {
            return $this->_server;
        }
    }

    protected function getIdByConnection($connection) {
        return intval($connection);
    }

    protected function onOpen($connectionId, $info) {}
    protected function onClose($connectionId) {}

    protected function onMessage($connectionId, $data, $type) {
	    if (!strlen($data)) {
		    return false;
	    }

	    $userId = $this->_user[$connectionId];
	    if (!$userId) {
		    return false;
	    }

	    try
	    {
		    $params = json_decode($data, true);

		    $return = array();
		    if ($params['chat'] == 'deal')
			    $return = Deal::message($userId, $params['deal'], $params['message'], false);
		    elseif ($params['chat'] == 'dealsupport')
			    $return = Deal::message($userId, $params['deal'], $params['message'], true);
		    elseif ($params['chat'] == 'usersupport')
			    $return = User::message($userId, $params['message']);

		    if ($return['push'])
		    {
			    $ar = array(
				    'message' => $params['message'],
				    'user' => $return['push'],
				    'connect' => $this->connectByUser[$return['push']],
			    );
			    $this->log(print_r($ar, true));
			    if (isset($this->connectByUser[$return['push']]))
			    {
				    $message = json_encode(array(
					    'connect' => $this->connectByUser[$return['push']],
					    'result' => 'new',
					    'errors' => array(),
				    ), JSON_UNESCAPED_UNICODE);
				    $this->sendToClient($this->connectByUser[$return['push']], $message);
			    }
		    }

		    $message = json_encode(array(
			    'result' => $return,
			    'errors' => array(),
		    ), JSON_UNESCAPED_UNICODE);
	    }
	    catch (ApiException $e)
	    {
		    $return = array(
			    'result' => null,
			    'errors' => $e->getErrors(),
		    );
		    if ($e->getMessage())
			    $return['message'] = $e->getMessage();
		    $message = json_encode($return, JSON_UNESCAPED_UNICODE);
	    }
	    catch (\Exception $e)
	    {
		    $message = json_encode(Array(
			    'errors' => array('unknown_error'),
			    'code' => $e->getCode(),
			    'message' => $e->getMessage(),
		    ), JSON_UNESCAPED_UNICODE);
	    }

	    $this->sendToClient($connectionId, $message);
    }

    protected function onServiceMessage($connectionId, $data) {}
    protected function onServiceOpen($connectionId) {}
    protected function onServiceClose($connectionId) {}

    protected function onMasterMessage($data) {}
    protected function onMasterClose($connectionId) {}

    protected function onStart() {}

	public function log($msg)
	{
		$msg = $msg . "\n";
		file_put_contents($this->logFile, date('Y-m-d H:i:s') . ' ' . 'pid:'. posix_getpid() . ' ' . $msg,
			FILE_APPEND | LOCK_EX);
	}
}