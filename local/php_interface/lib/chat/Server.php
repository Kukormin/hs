<?

namespace Local\Chat;

class Server
{
    public function __construct($config) {
        $this->config = $config;
    }

    public function start() {
        $pid = @file_get_contents($this->config['pid']);
        if ($pid) {
            if (posix_getpgid($pid)) {
                die("already started\r\n");
            } else {
                unlink($this->config['pid']);
            }
        }

        $server = stream_socket_server($this->config['websocket'], $errorNumber, $errorString);
        stream_set_blocking($server, 0);

        if (!$server) {
            die("error: stream_socket_server: $errorString ($errorNumber)\r\n");
        }


        file_put_contents($this->config['pid'], posix_getpid());

	    iconv_set_encoding("internal_encoding", "ISO-8859-1");
	    iconv_set_encoding("output_encoding", "ISO-8859-1");
	    iconv_set_encoding("input_encoding", "ISO-8859-1");

        $worker = new Daemon($server);
        if (!empty($this->config['timer'])) {
            $worker->timer = $this->config['timer'];
        }
        $worker->start();
    }

    public function stop() {
        $pid = @file_get_contents($this->config['pid']);
        if ($pid) {
            posix_kill($pid, SIGTERM);
            for ($i=0;$i<10;$i++) {
                sleep(1);

                if (!posix_getpgid($pid)) {
                    unlink($this->config['pid']);
                    return;
                }
            }

            die("don't stopped\r\n");
        } else {
            die("already stopped\r\n");
        }
    }

    public function restart() {
        $pid = @file_get_contents($this->config['pid']);
        if ($pid) {
            $this->stop();
        }

        $this->start();
    }
}
