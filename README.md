# 1. Install package
composer require siteffects/site-controller

# 2. Publish config + auto-generate key + register site
php artisan vendor:publish --provider="Siteffects\SiteController\SiteControllerServiceProvider" --tag="site-controller-config"