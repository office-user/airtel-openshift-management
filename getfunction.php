<?php

function makeGetRequest($url, $authType, $authData = '') {
    $ocpusername = 'monitor';
    $ocppassword = 'Monitor@321';
    $ch = curl_init();
    if ($authType === 'bearer') {
        $headers = [
            'Authorization: Bearer ' . $authData,
        ];
    } elseif ($authType === 'basic') {
        $headers = [
            'Authorization: Basic ' . base64_encode("$ocpusername:$ocppassword"),
        ];
    }
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
    ]);
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    preg_match('/^HTTP\/\d+\.\d+ (\d+)/', $header, $matches);
    $httpCode = isset($matches[1]) ? intval($matches[1]) : null;
    if ($httpCode >= 300 && $httpCode < 400) {
        preg_match('/access_token=\K[^&]*/', $header, $matches);
        $accessToken = isset($matches[0]) ? $matches[0] : null;
        return $accessToken;
    }
    return $body;
}

?>