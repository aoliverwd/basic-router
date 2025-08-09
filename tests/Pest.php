<?php

function cURLCall(string $method = "get", string $endpoint = "/"): array
{
    $ch = curl_init("http://localhost:50967" . $endpoint);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HEADER => true,
        CURLOPT_HEADER => false,
    ]);

    $response = curl_exec($ch);
    $header = curl_getinfo($ch);

    curl_close($ch);

    return [
        "response" => $response,
        "headers" => $header,
    ];
}
