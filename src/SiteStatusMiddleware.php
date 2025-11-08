<?php

namespace Siteffects\SiteController;

use Closure;
use GuzzleHttp\Client as GuzzleClient;

class SiteStatusMiddleware
{
    public function handle($request, Closure $next)
    {
        $config          = app('config');
        $app             = app();
        $view            = app('view');
        $responseFactory = app('response');

        $apiUrl          = $config->get('site-controller.api_url');
        $apiKey          = $config->get('site-controller.api_key');
        $laravelVersion  = $app->version();

        try {
            if (version_compare($laravelVersion, '7.0', '>=')) {
                $httpResponse = \Illuminate\Support\Facades\Http::withHeaders([
                    'X-Api-Key' => $apiKey,
                ])->get($apiUrl);

                $data     = $httpResponse->json();
                $isFailed = $httpResponse->failed();
            } else {
                $client = new GuzzleClient();
                $res    = $client->request('GET', $apiUrl, [
                    'headers' => ['X-Api-Key' => $apiKey],
                ]);

                $data     = json_decode($res->getBody()->getContents(), true);
                $isFailed = $res->getStatusCode() !== 200;
            }

            $status  = isset($data['status']) ? $data['status'] : 'active';
            $message = isset($data['message']) ? $data['message'] : 'Site is unavailable.';

            if ($isFailed || $status === 'stopped') {
                return $responseFactory->view(
                    'site-controller::maintenance',
                    ['message' => $message],
                    503
                );
            }
        } catch (\Throwable $e) {
            // Fail-open – let the site continue if the API is down
        }

        return $next($request);
    }
}