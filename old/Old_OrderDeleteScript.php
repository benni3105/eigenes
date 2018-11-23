<?php
/**
 * This is a bootstrap file for a phar distribution of the SDK in order to be able to use
 * `require 'commercetools-php-sdk.phar';`
 */
require __DIR__ . '/../vendor/autoload.php';

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Model\Common\Context;
use Commercetools\Core\Request\Orders\OrderDeleteRequests;
use Commercetools\Core\Model\Order\Order;
use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/../vendor/autoload.php';

$appConfig = Yaml::parse(file_get_contents('../api_credentials/myapp.yml'));
$context = Context::of()->setLanguages(['de'])->setGraceful(true);

// create the api client config object
$config = Config::fromArray($appConfig['parameters'])->setContext($context);

/*
$config = [
    'client_id' => 'v0LMiuKLyGkuKB5H6KTHxTtm',
    'client_secret' => 'fP7QuNkRV3mh6V1ksdz10ruGaSSMrW_n',
    'project' => 'projekt-2018-68'
];
$context = Context::of()->setLanguages(['de'])->setGraceful(true);
$config = Config::fromArray($config)->setContext($context);

$client = Client::ofConfig($config);
*/

/**
 * show result (would be a view layer in real world)
 */
header('Content-Type: text/html; charset=utf-8');

$orderNumber = 100000000000;

for($i=0; $i<100; $i++){
	$order = new Order();
    $order->setOrderNumber(strval($orderNumber));
    $order->setVersion(1);

    $create = RequestBuilder::of()->orders()->deleteByOrderNumber($order);

    $client = Client::ofConfig($config);
    try {
        $response = $client->execute($create)->getStatusCode();
    } catch (Exception $e) {
        echo 'Message: ' . $e->getMessage();
    }
    echo $response . "<br>";
    $orderNumber++;
}