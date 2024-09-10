<?php
namespace SalvaWorld\PushNotification;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use SalvaWorld\PushNotification\Contracts\PushServiceInterface;

class Fcm extends PushService implements PushServiceInterface {

    /**
     * Client to do the request
     *
     * @var \GuzzleHttp\Client $client
     */
    protected $client;

    /**
     * Gcm constructor.
     */
    public function __construct() {
        $this->config = $this->initializeConfig('fcm');
        $this->url = "https://fcm.googleapis.com/v1/projects/{$this->config['project_id']}/messages:send";
        $this->client = new Client();
    }

    /**
     * Provide the unregistered tokens of the sent notification.
     *
     * @param array $devices_token
     * @return array $tokenUnRegistered
     */
    public function getUnregisteredDeviceTokens(array $devices_token) {
        /**
         * If there is any failure sending the notification
         */
        if ($this->feedback && isset($this->feedback->failure)) {
            $unRegisteredTokens = $devices_token;

            /**
             * Walk the array looking for any error.
             * If no error, unset it from all token list which will become the unregistered tokens array.
             */
            foreach ($this->feedback->results as $key => $message) {
                if (!isset($message->error)) {
                    unset($unRegisteredTokens[$key]);
                }
            }

            return $unRegisteredTokens;
        }

        return [];
    }

    /**
     * Set the needed headers for the push notification.
     *
     * @return array
     */
    protected function addRequestHeaders() {
        return [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @return string
     */
    private function getAccessToken() {
        $serviceAccount = $this->config;
        $cacheKey = 'fcm_access_token_' . $serviceAccount['client_email'];
        return Cache::remember($cacheKey, Carbon::now()->addHour(), function () use ($serviceAccount) {
            $payload = [
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $serviceAccount['token_uri'],
                'exp' => time() + 3600,
                'iat' => time(),
            ];

            $jwtEncoded = JWT::encode($payload, $serviceAccount['private_key'], 'RS256');

            $client = new Client();
            try {
                $startTime = time();
                $tokenResponse = $client->post('https://oauth2.googleapis.com/token', [
                    RequestOptions::HEADERS => [
                        'Content-type' => 'application/x-www-form-urlencoded',
                    ],
                    RequestOptions::FORM_PARAMS => [
                        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'assertion' => $jwtEncoded,
                    ],
                ]);
                Log::debug('Get fcm access token ' . (time() - $startTime) . ' seconds');

            } catch (GuzzleException $e) {
                Log::error("Error get access token for {$jwtEncoded} takes " . (time() - $startTime) . " seconds - error message: " . $e->getMessage());
            }
            return json_decode($tokenResponse->getBody()->getContents(), true)['access_token'];
        });
    }

    /**
     * Send Push Notification
     *
     * @param  array $deviceTokens
     * @param array $message
     *
     * @return \stdClass  GCM Response
     */
    public function send(array $deviceTokens, array $message) {

        foreach ($deviceTokens as $token) {

            $message['message']['token'] = $token;
            $headers = $this->addRequestHeaders();

            try {
                $result = $this->client->post(
                    $this->url,
                    [
                        RequestOptions::HEADERS => $headers,
                        RequestOptions::JSON => $message,
                    ]
                );

                $json = $result->getBody();

                $this->setFeedback(json_decode($json, false, 512, JSON_BIGINT_AS_STRING));

                return $this->feedback;

            } catch (\Exception $e) {
                $response = ['success' => false, 'error' => $e->getMessage()];

                $this->setFeedback(json_decode(json_encode($response)));

            }
        }

        return $this->feedback;

    }
}
