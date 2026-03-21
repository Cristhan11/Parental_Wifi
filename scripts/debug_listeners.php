<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$d = $app->make('events');
$ref = new ReflectionClass($d);
$p = $ref->getProperty('listeners');
$p->setAccessible(true);
$listeners = $p->getValue($d);

$key = \App\Events\BlockedWebsiteAccessed::class;
echo "Listeners for {$key}:\n";
print_r($listeners[$key] ?? []);

echo "\nclass_implements:\n";
print_r(class_implements(\App\Events\BlockedWebsiteAccessed::class));

$providers = $app->getLoadedProviders();
echo "\nLoaded providers containing AppServiceProvider:\n";
foreach (array_keys($providers) as $name) {
    if (str_contains($name, 'AppServiceProvider')) {
        echo $name." => ".($providers[$name] ? 'true' : 'false')."\n";
    }
}
