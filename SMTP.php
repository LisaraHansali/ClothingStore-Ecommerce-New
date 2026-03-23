<?php
/**
 * SMTP - Simple Mail Transfer Protocol implementation
 * Simplified version for the ClothesStore application
 */

class SMTP {
    const VERSION = '1.0.0';
    const CRLF = "\r\n";
    const DEFAULT_PORT = 25;
    const MAX_LINE_LENGTH = 998;
    const MAX_REPLY_LENGTH = 512;
    
    // SMTP response codes
    const SMTP_CONNECT_SUCCESS = 220;
    const SMTP_DATA_SUCCESS = 354;
    const SMTP_AUTHENTICATE_SUCCESS = 235;
    const SMTP_GENERIC_SUCCESS = 250;
    const SMTP_USER_NOT_LOCAL = 251;
    const SMTP_ABORT = 421;
    const SMTP_AUTH_CHALLENGE = 334;
    
    public $Version = self::VERSION;
    public $SMTP_PORT = self::DEFAULT_PORT;
    public $CRLF = self::CRLF;
    public $Debugoutput = 'echo';
    
    protected $smtp_conn;
    protected $error = [];
    protected $helo_rply;
    protected $server_caps;
    protected $last_reply;
    
    /**
     * Connect to SMTP server
     */
    public function connect($host, $port = null, $timeout = 30, $options = []) {
        static $streamok;
        
        if (is_null($streamok)) {
            $streamok = function_exists('stream_socket_client');
        }
        
        $port = (int)$port;
        if ($port <= 0) {
            $port = self::DEFAULT_PORT;
        }
        
        if ($streamok) {
            $socket_context = stream_context_create($options);
            set_error_handler([$this, 'errorHandler']);
            $this->smtp_conn = stream_socket_client(
                $host . ':' . $port,
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT,
                $socket_context
            );
            restore_error_handler();
        } else {
            set_error_handler([$this, 'errorHandler']);
            $this->smtp_conn = fsockopen(
                $host,
                $port,
                $errno,
                $errstr,
                $timeout
            );
            restore_error_handler();
        }
        
        if (!is_resource($this->smtp_conn)) {
            $this->setError(
                'Failed to connect to server',
                '',
                (string)$errno,
                (string)$errstr
            );
            return false;
        }
        
        if (!$this->sendCommand('', 'SMTP connect')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Send SMTP command
     */
    public function sendCommand($command, $commandstring, $expect) {
        if (!$this->connected()) {
            $this->setError("Called $commandstring without being connected");
            return false;
        }
        
        $this->client_send($command . static::CRLF, $commandstring);
        
        $this->last_reply = $this->get_lines();
        $matches = [];
        if (preg_match('/^([\d]{3})[ -](?:([\d\.\-]{1,}) )?/', $this->last_reply, $matches)) {
            $code = (int)$matches[1];
            $code_ex = (count($matches) > 2 ? $matches[2] : null);
            if (!in_array($code, (array)$expect, true)) {
                $this->setError(
                    "$commandstring command failed",
                    $this->last_reply,
                    (string)$code,
                    (string)$code_ex
                );
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Authenticate with SMTP server
     */
    public function authenticate($username, $password, $authtype = null, $realm = '', $workstation = '', $oauth = null) {
        if (!$this->server_caps) {
            $this->setError('Authentication is not allowed before HELO/EHLO');
            return false;
        }
        
        if (array_key_exists('EHLO', $this->server_caps)) {
            if (!array_key_exists('AUTH', $this->server_caps)) {
                $this->setError('Authentication is not allowed at this stage');
                return false;
            }
        }
        
        // For this simplified version, we'll just do basic LOGIN authentication
        if (!$this->sendCommand('AUTH LOGIN', 'AUTH LOGIN', self::SMTP_AUTH_CHALLENGE)) {
            return false;
        }
        
        if (!$this->sendCommand(base64_encode($username), 'Username', self::SMTP_AUTH_CHALLENGE)) {
            return false;
        }
        
        if (!$this->sendCommand(base64_encode($password), 'Password', self::SMTP_AUTHENTICATE_SUCCESS)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Send data to SMTP server
     */
    public function data($msg_data) {
        if (!$this->sendCommand('DATA', 'DATA', self::SMTP_DATA_SUCCESS)) {
            return false;
        }
        
        $lines = explode(static::CRLF, $msg_data);
        foreach ($lines as $line) {
            if (strlen($line) > 0 && $line[0] === '.') {
                $line = '.' . $line;
            }
            $this->client_send($line . static::CRLF, 'DATA');
        }
        
        $savetimelimit = ini_get('max_execution_time');
        if (0 !== $savetimelimit && $savetimelimit < 120) {
            ini_set('max_execution_time', 120);
        }
        
        if (!$this->sendCommand(static::CRLF . '.', 'DATA END', self::SMTP_GENERIC_SUCCESS)) {
            return false;
        }
        
        if (0 !== $savetimelimit) {
            ini_set('max_execution_time', $savetimelimit);
        }
        
        return true;
    }
    
    /**
     * Check if connected to SMTP server
     */
    public function connected() {
        if (is_resource($this->smtp_conn)) {
            $sock_status = stream_get_meta_data($this->smtp_conn);
            if ($sock_status['eof']) {
                $this->close();
                return false;
            }
            return true;
        }
        return false;
    }
    
    /**
     * Close SMTP connection
     */
    public function close() {
        $this->setError('');
        $this->server_caps = null;
        $this->helo_rply = null;
        
        if (is_resource($this->smtp_conn)) {
            fclose($this->smtp_conn);
            $this->smtp_conn = null;
        }
    }
    
    /**
     * Send client command to server
     */
    protected function client_send($data, $command = '') {
        if ($command !== '') {
            $this->do_debug("CLIENT -> SERVER: $command", self::DEBUG_CLIENT);
        }
        
        return fwrite($this->smtp_conn, $data);
    }
    
    /**
     * Get server response lines
     */
    protected function get_lines() {
        if (!is_resource($this->smtp_conn)) {
            return '';
        }
        
        $data = '';
        $endtime = 0;
        stream_set_timeout($this->smtp_conn, 300);
        
        if ($this->Timelimit > 0) {
            $endtime = time() + $this->Timelimit;
        }
        
        $selR = [$this->smtp_conn];
        $selW = null;
        $selX = null;
        
        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            if (!stream_select($selR, $selW, $selX, 60)) {
                $this->do_debug('SMTP -> get_lines(): timed-out (' . $this->Timeout . ' sec)', self::DEBUG_LOWLEVEL);
                break;
            }
            
            $str = @fgets($this->smtp_conn, self::MAX_REPLY_LENGTH);
            $this->do_debug("SMTP -> get_lines(): \$data is \"$str\"", self::DEBUG_LOWLEVEL);
            $data .= $str;
            
            if ((isset($str[3]) and $str[3] == ' ')) {
                break;
            }
            
            $info = stream_get_meta_data($this->smtp_conn);
            if ($info['timed_out']) {
                $this->do_debug('SMTP -> get_lines(): timed-out (' . $this->Timeout . ' sec)', self::DEBUG_LOWLEVEL);
                break;
            }
            
            if ($endtime and time() > $endtime) {
                $this->do_debug('SMTP -> get_lines(): timelimit reached (' . $this->Timelimit . ' sec)', self::DEBUG_LOWLEVEL);
                break;
            }
        }
        
        return $data;
    }
    
    /**
     * Set error message
     */
    protected function setError($message, $detail = '', $smtp_code = '', $smtp_code_ex = '') {
        $this->error = [
            'error' => $message,
            'detail' => $detail,
    /**
     * Set error message
     */
    protected function setError($message, $detail = '', $smtp_code = '', $smtp_code_ex = '') {
        $this->error = [
            'error' => $message,
            'detail' => $detail,
            'smtp_code' => $smtp_code,
            'smtp_code_ex' => $smtp_code_ex
        ];
    }
    
    /**
     * Get error information
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Error handler for socket operations
     */
    protected function errorHandler($errno, $errmsg, $errfile = '', $errline = 0) {
        $notice = 'Connection failed.';
        $this->setError($notice, $errno, $errmsg);
    }
    
    /**
     * Debug output
     */
    protected function do_debug($str, $level = 0) {
        if ($this->do_verp) {
            switch ($this->Debugoutput) {
                case 'error_log':
                    error_log($str);
                    break;
                case 'html':
                    echo htmlentities($str, ENT_QUOTES, 'UTF-8'), "<br>\n";
                    break;
                case 'echo':
                default:
                    echo $str, "\n";
                    break;
            }
        }
    }
    
    // Debug levels
    const DEBUG_OFF = 0;
    const DEBUG_CLIENT = 1;
    const DEBUG_SERVER = 2;
    const DEBUG_CONNECTION = 3;
    const DEBUG_LOWLEVEL = 4;
    
    public $do_verp = false;
    public $Timeout = 300;
    public $Timelimit = 30;
}
?>