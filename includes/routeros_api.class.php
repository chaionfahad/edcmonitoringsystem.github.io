<?php
/**
 * RouterOS API client for MikroTik
 * Based on the standard RouterOS PHP API protocol
 */
class RouterOSAPI {
    private $socket = null;
    private $host;
    private $port;
    private $username;
    private $password;
    private $timeout;
    private $connected = false;
    private $error = '';

    public function __construct($host, $username, $password, $port = 8728, $timeout = 5) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout;
    }

    public function connect() {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        if (!$this->socket) {
            $this->error = "Connection timed out ($this->timeout sec) or failed: $errstr ($errno)";
            return false;
        }
        stream_set_timeout($this->socket, $this->timeout);
        if (!$this->socket) {
            $this->error = "Connection failed: $errstr ($errno)";
            return false;
        }

        // Read the initial API banner (MikroTik sends a "!" message on connect)
        $this->readWord();

        // Login
        if (!$this->login()) {
            return false;
        }

        $this->connected = true;
        return true;
    }

    private function login() {
        // Send login command
        $this->writeWord('/login');
        $this->writeWord('=name=' . $this->username);
        $this->writeWord('=password=' . $this->password);
        $this->writeWord('');

        $response = $this->readResponse();

        if (isset($response[0]) && strpos($response[0], '!trap') !== false) {
            $this->error = "Login failed: Invalid credentials or API access not enabled.";
            return false;
        }

        return true;
    }

    public function isConnected() {
        return $this->connected;
    }

    public function getError() {
        return $this->error;
    }

    /**
     * Execute a command on the MikroTik router.
     * @param string $command - The command to execute (e.g., '/ppp/active/print')
     * @param array $params - Query parameters (e.g., ['?name=something'])
     * @return array|false - Parsed response or false on failure
     */
    public function command($command, $params = []) {
        if (!$this->connected) {
            $this->error = "Not connected to router.";
            return false;
        }

        $this->writeWord($command);
        foreach ($params as $param) {
            $this->writeWord($param);
        }
        $this->writeWord('');

        return $this->readResponse();
    }

    /**
     * Get PPPoE active sessions.
     * Returns array of active PPPoE users with their details.
     */
    public function getPPPoEActive() {
        $result = $this->command('/ppp/active/print', ['=.proplist=.id,name,address,uptime,caller-id']);
        return $result;
    }

    /**
     * Check if a specific PPPoE user is online.
     */
    public function checkPPPoEUser($username) {
        $result = $this->command('/ppp/active/print', [
            '?name=' . $username
        ]);
        return is_array($result) && count($result) > 0;
    }

    /**
     * Get ARP table entries.
     */
    public function getARPTable() {
        return $this->command('/ip/arp/print', ['.proplist=.id,address,mac-address,interface']);
    }

    /**
     * Get DHCP leases.
     */
    public function getDHCPLeases() {
        return $this->command('/ip/dhcp-server/lease/print', ['.proplist=.id,address,mac-address,host-name,status']);
    }

    /**
     * Ping an IP address from the router.
     */
    public function ping($ipAddress, $count = 2) {
        $result = $this->command('/ping', [
            '=address=' . $ipAddress,
            '=count=' . $count,
        ]);
        return $result;
    }

    public function disconnect() {
        if ($this->socket) {
            $this->writeWord('/quit');
            fclose($this->socket);
        }
        $this->connected = false;
        $this->socket = null;
    }

    public function __destruct() {
        $this->disconnect();
    }

    // ---- Private API protocol helpers ----

    private function readWord() {
        $length = $this->readLength();
        if ($length === false || $length === 0) {
            return '';
        }
        $data = fread($this->socket, $length);
        return $data;
    }

    private function readLength() {
        $byte = fread($this->socket, 1);
        if ($byte === false || $byte === '') {
            return false;
        }

        $byte = ord($byte);
        if ($byte < 128) {
            return $byte;
        }
        if ($byte < 192) {
            $byte2 = ord(fread($this->socket, 1));
            return (($byte - 128) << 8) + $byte2;
        }
        if ($byte < 224) {
            $byte2 = ord(fread($this->socket, 1));
            $byte3 = ord(fread($this->socket, 1));
            return (($byte - 192) << 16) + ($byte2 << 8) + $byte3;
        }
        $byte2 = ord(fread($this->socket, 1));
        $byte3 = ord(fread($this->socket, 1));
        $byte4 = ord(fread($this->socket, 1));
        return (($byte - 224) << 24) + ($byte2 << 16) + ($byte3 << 8) + $byte4;
    }

    private function writeWord($word) {
        $this->writeLength(strlen($word));
        if ($word !== '') {
            fwrite($this->socket, $word);
        }
    }

    private function writeLength($length) {
        if ($length < 128) {
            fwrite($this->socket, chr($length));
        } elseif ($length < 16384) {
            fwrite($this->socket, chr(($length >> 8) + 128) . chr($length & 0xFF));
        } elseif ($length < 2097152) {
            fwrite($this->socket, chr(($length >> 16) + 192) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF));
        } else {
            fwrite($this->socket, chr(($length >> 24) + 224) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF));
        }
    }

    private function readResponse() {
        $parsed = [];

        while (true) {
            // Read one sentence (until blank word)
            $sentence = [];
            while (true) {
                $word = $this->readWord();
                if ($word === false || $word === '') {
                    break;
                }
                $sentence[] = $word;
            }

            if (empty($sentence)) {
                break;
            }

            $type = $sentence[0];

            if ($type === '!trap') {
                $this->error = 'Command trapped an error';
                return false;
            }

            if ($type === '!fatal') {
                $this->error = 'Fatal error from router';
                return false;
            }

            if ($type === '!re') {
                $row = [];
                for ($i = 1; $i < count($sentence); $i++) {
                    $line = $sentence[$i];
                    if (strpos($line, '=') === 0) {
                        // Strip leading '=', then split on first '=' to get key=value
                        $withoutPrefix = substr($line, 1);
                        $pos = strpos($withoutPrefix, '=');
                        if ($pos !== false) {
                            $key = substr($withoutPrefix, 0, $pos);
                            $val = substr($withoutPrefix, $pos + 1);
                            $row[$key] = $val;
                        }
                    }
                }
                $parsed[] = $row;
            }

            if ($type === '!done') {
                break;
            }
        }

        return $parsed;
    }
}
