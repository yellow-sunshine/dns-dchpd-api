<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

use App\Controllers\FlushBindDns;
use App\Controllers\ZoneDetails;
use App\Controllers\CloudflareDdns;
use App\Controllers\DhcpdLeases;
use App\Controllers\DhcpdConfig;
use App\Classes\DhcpdLeaseFileProcessor;


require __DIR__ . '/vendor/autoload.php';

# Load the config file and define constants
$config = require __DIR__ . '/Configs/config.php';

$app = AppFactory::create();


# Default route, nothing really to show here excpet a nice message
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("<h1>dhcp dns api</h1> For more information see <a href='https://github.com/yellow-sunshine/dns-dchpd-api'>Github</a>");
    return $response;
});


# Flush Bind DNS
$flushBindDns = new FlushBindDns();
$app->get('/flush-bind-dns[/]', [$flushBindDns, 'flushDns']);


# Zone Details
$zoneDetails = new ZoneDetails();
$app->get('/zone-details/{domain}', function ($request, $response, $args) use ($zoneDetails) {
    return $zoneDetails->getZoneDetailsJson($request, $response, $args['domain']);
});


# Cloudflare DDNS
$cloudflareDdns= new CloudflareDdns();
$app->get('/cloudflare-ddns[/]', [$cloudflareDdns, 'show']);
$app->get('/cloudflare-ddns/run', [$cloudflareDdns, 'runCloudflareDdns']);
$app->get('/cloudflare-ddns/run/force', [$cloudflareDdns, 'runCloudflareDdns']);


# Dhcpd leases
# Create a new instance of the DhcpdLeaseFileProcessor and inject it into the DhcpdLeases class
$dhcpdLeaseFileProcessor = new DhcpdLeaseFileProcessor();
$dhcpdLeases = new DhcpdLeases($dhcpdLeaseFileProcessor, $config);
$app->get('/dhcpd/leases[/]', [$dhcpdLeases, 'index']);


$app->run();