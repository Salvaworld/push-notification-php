<?php

namespace SalvaWorld\PushNotification;

use Firebase\JWT\JWT;

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
        
        $payload = [
            'iss' => $this->teamId,
            'iat' => time()
        ];

        // ES256 needs the raw 64 byte R || S signature. openssl_sign returns an ASN.1 DER signature,
        // which Apple rejects with InvalidProviderToken, so let firebase/php-jwt build and convert it.
        try {
            $jwt = JWT::encode($payload, $this->privateKey, 'ES256', $this->keyId);
        } catch (\Throwable $e) {
            throw new \Exception("Failed to sign JWT token: " . $e->getMessage(), 0, $e);
        }
        
        // Cache the token
        $this->tokenCache[$cacheKey] = [
            'token' => $jwt,
            'expires_at' => time() + $this->tokenExpiryTime - 300 // Expire 5 minutes early for safety
        ];
        
        return $jwt;
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