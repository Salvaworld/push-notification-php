<?php

namespace SalvaWorld\PushNotification;

class ApnJwtGenerator {
    
    private $keyId;
    private $teamId;
    private $privateKey;
    private $tokenCache = [];
    private $tokenExpiryTime = 3600; // 1 hour in seconds
    
    public function __construct($keyId, $teamId, $privateKeyPath) {
        $this->keyId = $keyId;
        $this->teamId = $teamId;
        
        if (!file_exists($privateKeyPath)) {
            throw new \Exception("Auth key file not found: " . $privateKeyPath);
        }
        
        $this->privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
        
        if (!$this->privateKey) {
            throw new \Exception("Invalid auth key file: " . $privateKeyPath);
        }
    }
    
    /**
     * Generate JWT token for APN authentication
     * Tokens are cached and reused until expiry
     * 
     * @return string
     */
    public function generateToken() {
        $cacheKey = $this->keyId . '_' . $this->teamId;
        
        // Check if we have a valid cached token
        if (isset($this->tokenCache[$cacheKey])) {
            $cachedToken = $this->tokenCache[$cacheKey];
            if ($cachedToken['expires_at'] > time()) {
                return $cachedToken['token'];
            }
        }
        
        $header = [
            'alg' => 'ES256',
            'kid' => $this->keyId
        ];
        
        $payload = [
            'iss' => $this->teamId,
            'iat' => time()
        ];
        
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $signature = '';
        $success = openssl_sign(
            $headerEncoded . '.' . $payloadEncoded,
            $signature,
            $this->privateKey,
            OPENSSL_ALGO_SHA256
        );
        
        if (!$success) {
            throw new \Exception("Failed to sign JWT token");
        }
        
        $signatureEncoded = $this->base64UrlEncode($signature);
        $jwt = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
        
        // Cache the token
        $this->tokenCache[$cacheKey] = [
            'token' => $jwt,
            'expires_at' => time() + $this->tokenExpiryTime - 300 // Expire 5 minutes early for safety
        ];
        
        return $jwt;
    }
    
    /**
     * Base64 URL encode
     * 
     * @param string $data
     * @return string
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Cleanup resources
     */
    public function __destruct() {
        if ($this->privateKey) {
            openssl_free_key($this->privateKey);
        }
    }
}