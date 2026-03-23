<?php
/**
 * OAuth implementation for ClothesStore application
 * Simplified version for basic OAuth functionality
 */

class OAuth {
    const VERSION = '1.0';
    const SIG_METHOD_HMACSHA1 = 'HMAC-SHA1';
    const SIG_METHOD_HMACSHA256 = 'HMAC-SHA256';
    
    protected $timestamp;
    protected $nonce;
    protected $version;
    protected $signatureMethod;
    protected $consumer;
    protected $token;
    
    public function __construct($consumer_key, $consumer_secret, $signature_method = self::SIG_METHOD_HMACSHA1) {
        $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
        $this->signatureMethod = $signature_method;
        $this->version = self::VERSION;
    }
    
    /**
     * Generate OAuth authorization header
     */
    public function getAuthorizationHeader($url, $method = 'GET', $parameters = []) {
        $this->timestamp = time();
        $this->nonce = $this->generateNonce();
        
        $oauth_params = [
            'oauth_consumer_key' => $this->consumer->key,
            'oauth_nonce' => $this->nonce,
            'oauth_signature_method' => $this->signatureMethod,
            'oauth_timestamp' => $this->timestamp,
            'oauth_version' => $this->version
        ];
        
        if ($this->token) {
            $oauth_params['oauth_token'] = $this->token->key;
        }
        
        // Create signature base string
        $base_string = $this->buildSignatureBaseString($method, $url, array_merge($oauth_params, $parameters));
        
        // Generate signature
        $signature = $this->buildSignature($base_string);
        $oauth_params['oauth_signature'] = $signature;
        
        // Build authorization header
        $auth_header = 'OAuth ';
        $auth_parts = [];
        
        foreach ($oauth_params as $key => $value) {
            $auth_parts[] = $key . '="' . rawurlencode($value) . '"';
        }
        
        $auth_header .= implode(', ', $auth_parts);
        
        return $auth_header;
    }
    
    /**
     * Set access token
     */
    public function setToken($token_key, $token_secret) {
        $this->token = new OAuthToken($token_key, $token_secret);
    }
    
    /**
     * Generate nonce
     */
    protected function generateNonce() {
        return md5(microtime() . mt_rand());
    }
    
    /**
     * Build signature base string
     */
    protected function buildSignatureBaseString($method, $url, $parameters) {
        // Sort parameters
        ksort($parameters);
        
        // Build parameter string
        $param_parts = [];
        foreach ($parameters as $key => $value) {
            $param_parts[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
        $param_string = implode('&', $param_parts);
        
        // Build base string
        $base_string = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode($param_string);
        
        return $base_string;
    }
    
    /**
     * Build signature
     */
    protected function buildSignature($base_string) {
        $key = rawurlencode($this->consumer->secret) . '&';
        if ($this->token) {
            $key .= rawurlencode($this->token->secret);
        }
        
        switch ($this->signatureMethod) {
            case self::SIG_METHOD_HMACSHA1:
                return base64_encode(hash_hmac('sha1', $base_string, $key, true));
            
            case self::SIG_METHOD_HMACSHA256:
                return base64_encode(hash_hmac('sha256', $base_string, $key, true));
            
            default:
                throw new Exception('Unsupported signature method: ' . $this->signatureMethod);
        }
    }
    
    /**
     * Make OAuth request
     */
    public function request($url, $method = 'GET', $parameters = [], $headers = []) {
        $auth_header = $this->getAuthorizationHeader($url, $method, $parameters);
        $headers['Authorization'] = $auth_header;
        
        $context_options = [
            'http' => [
                'method' => strtoupper($method),
                'header' => $this->buildHeaderString($headers),
                'ignore_errors' => true
            ]
        ];
        
        if (strtoupper($method) === 'POST' && !empty($parameters)) {
            $context_options['http']['content'] = http_build_query($parameters);
            $context_options['http']['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n";
        } elseif (strtoupper($method) === 'GET' && !empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }
        
        $context = stream_context_create($context_options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception('Failed to make OAuth request to: ' . $url);
        }
        
        return $response;
    }
    
    /**
     * Build header string from array
     */
    protected function buildHeaderString($headers) {
        $header_string = '';
        foreach ($headers as $key => $value) {
            $header_string .= $key . ': ' . $value . "\r\n";
        }
        return $header_string;
    }
}

/**
 * OAuth Consumer class
 */
class OAuthConsumer {
    public $key;
    public $secret;
    
    public function __construct($key, $secret) {
        $this->key = $key;
        $this->secret = $secret;
    }
}

/**
 * OAuth Token class
 */
class OAuthToken {
    public $key;
    public $secret;
    
    public function __construct($key, $secret) {
        $this->key = $key;
        $this->secret = $secret;
    }
}

/**
 * OAuth2 implementation (simplified)
 */
class OAuth2 {
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $accessToken;
    
    public function __construct($client_id, $client_secret, $redirect_uri = '') {
        $this->clientId = $client_id;
        $this->clientSecret = $client_secret;
        $this->redirectUri = $redirect_uri;
    }
    
    /**
     * Get authorization URL
     */
    public function getAuthorizationUrl($auth_url, $scope = '', $state = '') {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $scope,
            'state' => $state
        ];
        
        return $auth_url . '?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken($token_url, $authorization_code) {
        $post_data = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $authorization_code
        ];
        
        $context_options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($post_data)
            ]
        ];
        
        $context = stream_context_create($context_options);
        $response = file_get_contents($token_url, false, $context);
        
        if ($response === false) {
            throw new Exception('Failed to get access token');
        }
        
        $token_data = json_decode($response, true);
        
        if (isset($token_data['access_token'])) {
            $this->accessToken = $token_data['access_token'];
            return $token_data;
        } else {
            throw new Exception('Access token not found in response');
        }
    }
    
    /**
     * Make authenticated API request
     */
    public function apiRequest($url, $method = 'GET', $data = []) {
        if (!$this->accessToken) {
            throw new Exception('Access token not set');
        }
        
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];
        
        $context_options = [
            'http' => [
                'method' => strtoupper($method),
                'header' => implode("\r\n", $headers) . "\r\n",
                'ignore_errors' => true
            ]
        ];
        
        if (strtoupper($method) === 'POST' && !empty($data)) {
            $context_options['http']['content'] = json_encode($data);
        }
        
        $context = stream_context_create($context_options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception('Failed to make API request');
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Set access token
     */
    public function setAccessToken($access_token) {
        $this->accessToken = $access_token;
    }
    
    /**
     * Get current access token
     */
    public function getAccessTokenValue() {
        return $this->accessToken;
    }
}
?>