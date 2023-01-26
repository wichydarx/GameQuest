<?php

namespace App\Service;

use DateTimeImmutable;

class JWTService
{


    /**
     * 
     * @param array $header 
     * @param array $payload 
     * @param string $encryptkey 
     * @param int $validity 
     * @return string 
     */
    public function generate(array $header, array $payload, string $encryptkey, int $validity = 10800): string
    {
        if($validity > 0){
            $now = new DateTimeImmutable();
            $exp = $now->getTimestamp() + $validity;

            
    
            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $exp;
            
        }

        
        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload));

       
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], $base64Header);
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], $base64Payload);

        
        $encryptkey = base64_encode($encryptkey);

        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $encryptkey, true);

        $base64Signature = base64_encode($signature);

        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], $base64Signature);

        
        $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

        return $jwt;
    }

    

    public function isValid(string $token): bool
    {
        return preg_match(
            '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
            $token
        ) === 1;
    }

    
    public function getPayloadFromToken(string $token): array
    {
        
        $array = explode('.', $token);

      
        $payload = json_decode(base64_decode($array[1]), true);

        return $payload;
    }

    
    public function getHeaderFromToken(string $token): array
    {
        
        $array = explode('.', $token);

        
        $header = json_decode(base64_decode($array[0]), true);

        return $header;
    }

    
    public function isExpired(string $token): bool
    {
        $payload = $this->getPayloadFromToken($token);

        $now = new DateTimeImmutable();

        return $payload['exp'] < $now->getTimestamp();
    }

    
    public function checkSignature(string $token, string $encryptkey)
    {
        
        $header = $this->getHeaderFromToken($token);
        $payload = $this->getPayloadFromToken($token);

       
        $verifToken = $this->generate($header, $payload, $encryptkey, 0);

        return $token === $verifToken;
    }
}