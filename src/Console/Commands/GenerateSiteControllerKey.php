<?php

namespace Siteffects\SiteController\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use GuzzleHttp\Client as GuzzleClient;

class GenerateSiteControllerKey extends Command
{
    protected $signature = 'site-controller:generate-key {--url=}';
    protected $description = 'Generate API key, register site in admin panel, and write to .env';

    public function handle()
    {
        // 1. Get or prompt for site URL
        $siteUrl = $this->option('url')
            ?: $this->ask('Enter your site URL (e.g., https://example.com)', env('APP_URL', 'https://localhost'));

        // 2. Generate unique API key
        $apiKey = Str::random(40);

        // 3. Write to .env
        $envPath = base_path('.env');
        $content = file_exists($envPath) ? file_get_contents($envPath) : '';
        $content = preg_replace('/^SITE_CONTROLLER_API_KEY=.*/m', '', $content);
        $content = trim($content) . PHP_EOL . "SITE_CONTROLLER_API_KEY={$apiKey}" . PHP_EOL;
        file_put_contents($envPath, $content);

        $this->info('API key generated and added to .env:');
        $this->line("<fg=green>SITE_CONTROLLER_API_KEY={$apiKey}</>");

        // 4. Register site in admin panel
        $this->registerSiteInAdmin($siteUrl, $apiKey);

        $this->info('Setup complete! Add the middleware to Kernel.php and visit your site.');
    }

    protected function registerSiteInAdmin(string $siteUrl, string $apiKey): void
    {
        $config      = app('config');
        $adminApiUrl = $config->get('site-controller.registration_url');

        $this->info("Registering site '{$siteUrl}' in admin panel...");

        try {
            $laravelVersion = app()->version();

            if (version_compare($laravelVersion, '7.0', '>=')) {
                // Laravel 7+ – Http facade
                $response = \Illuminate\Support\Facades\Http::post($adminApiUrl, [
                    'url'     => $siteUrl,
                    'api_key' => $apiKey,
                ]);

                $isSuccess    = $response->successful();
                $responseData = $response->json();
            } else {
                // Laravel 5.5-6.x – Guzzle
                $client = new GuzzleClient();
                $res    = $client->post($adminApiUrl, [
                    'json' => [
                        'url'     => $siteUrl,
                        'api_key' => $apiKey,
                    ],
                ]);

                $isSuccess    = $res->getStatusCode() === 201;
                $responseData = json_decode($res->getBody()->getContents(), true);
            }

            if ($isSuccess) {
                $siteId = $responseData['site_id'] ?? 'N/A';
                $this->info("<fg=green>Site registered successfully! (ID: {$siteId})</>");
            } else {
                $msg = $responseData['message'] ?? 'Unknown error';
                $this->warn("<fg=yellow>Registration failed: {$msg}</>");
                $this->warn('The key is still generated locally. Add the site manually in the admin panel.');
            }
        } catch (\Throwable $e) {
            $this->error("<fg=red>Registration error: " . $e->getMessage() . "</>");
            $this->warn('Check SITE_CONTROLLER_REGISTRATION_URL. The key is still generated locally.');
        }
    }
}