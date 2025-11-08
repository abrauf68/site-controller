<?php

namespace Siteffects\SiteController;

use Closure;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

class SiteStatusMiddleware
{
    public function handle($request, Closure $next)
    {
        $config = app('config');
        $app = app();
        $view = app('view');
        $responseFactory = app('response');

        $apiUrl = $config->get('site-controller.api_url');
        $apiKey = $config->get('site-controller.api_key');
        $laravelVersion = $app->version();

        try {
            if (version_compare($laravelVersion, '7.0', '>=')) {
                $httpResponse = \Illuminate\Support\Facades\Http::withHeaders(['X-Api-Key' => $apiKey])->get($apiUrl);
                $data = $httpResponse->json();
                $isFailed = $httpResponse->failed();
            } else {
                $client = new GuzzleClient();
                $res = $client->request('GET', $apiUrl, ['headers' => ['X-Api-Key' => $apiKey]]);
                $data = json_decode($res->getBody()->getContents(), true);
                $isFailed = $res->getStatusCode() !== 200;
            }

            if ($isFailed || ($data['status'] ?? 'active') === 'stopped') {
                $message = $data['message'] ?? 'Site is unavailable.';
                return $responseFactory->view('site-controller::maintenance', ['message' => $message], 503);
            }
        } catch (\Throwable $e) {
            // Fail open: proceed if API fails
            // Optionally log: app('log')->warning('SiteController API error: ' . $e->getMessage());
        }

        return $next($request);
    }
}