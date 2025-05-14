<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class OpenRouterService
{
    protected $client;
    protected $baseUrl = 'https://openrouter.ai/api/v1';

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getChatCompletion(array $data)
    {
        $user = Auth::user();
        $encryptedKey = $user->api_key;
        $apiKey = Crypt::decryptString($encryptedKey);

        $response = $this->client->post("{$this->baseUrl}/chat/completions", [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
