## Lumen integration
Laravel-apie works for Lumen, but because of issues with testing laravel and lumen at the same time, there are not automatic tests,
so please be careful with upgrading laravel-apie.

In your Laravel package you should do the usual steps to install a Laravel package.
```bash
composer require w2w/laravel-apie

```

Copy vendor/w2w/laravel-apie/config/apie.php to config/apie.php

In bootstrap/app.php add these lines:
```php
<?php
// bootstrap/app.php
// if you use eloquent with Apie make sure these lines are uncommented:
$app->withFacades();
$app->withEloquent();

$app->configure('apie');

$app->register(\W2w\Laravel\Apie\Providers\ApiResourceServiceProvider::class);
```

Now if you go to /swagger-ui you get to see the swagger ui page!




