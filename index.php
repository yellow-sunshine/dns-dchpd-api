<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Controllers\FlushBindDnsController;
use App\Controllers\ZoneDetailsController;
use App\Controllers\CloudflareDdnsController;
use App\Controllers\DhcpdController;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

# Default route, nothing really to show here excpet a nice message
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("<h1>ns1.daha.us api</h1> For more information see <a href='//ncc.daha.us'>Network Control Console</a>");
    return $response;
});

# Flush Bind DNS
$flushBindDnsController = new FlushBindDnsController();
$app->get('/flush-bind-dns[/]', [$flushBindDnsController, 'flushDns']);

# Zone Details
$zoneDetailsController = new ZoneDetailsController();
$app->get('/zone-details/{domain}', function ($request, $response, $args) use ($zoneDetailsController) {
    return $zoneDetailsController->getZoneDetailsJson($request, $response, $args['domain']);
});

# Cloudflare DDNS
$cloudflareDdnsController = new CloudflareDdnsController();
$app->get('/cloudflare-ddns[/]', [$cloudflareDdnsController, 'show']);
$app->get('/cloudflare-ddns/run', [$cloudflareDdnsController, 'runCloudflareDdns']);
$app->get('/cloudflare-ddns/run/force', [$cloudflareDdnsController, 'runCloudflareDdns']);

# Dhcpd 
$dhcpdController = new DhcpdController();
$app->get('/dhcpd[/]', [$dhcpdController, 'index']);


$app->run();