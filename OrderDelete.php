<?php
/**
 * Created by PhpStorm.
 * User: Benjamin Dicks
 * Date: 22.11.2018
 * Time: 11:51
 */
require __DIR__ . '/../vendor/autoload.php';

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Model\Common\Context;
use Commercetools\Core\Model\Order\Order;
use Symfony\Component\Yaml\Yaml;

$appConfig = Yaml::parse(file_get_contents('../api_credentials/myapp.yml'));
$context = Context::of()->setLanguages(['de'])->setGraceful(true);
$config = Config::fromArray($appConfig['parameters'])->setContext($context);
$client = Client::ofConfig($config);

/**
 * show result (would be a view layer in real world)
 */
header('Content-Type: text/html; charset=utf-8');

//JSON Builder Query
$request = RequestBuilder::of()->orders()->query()->limit(100000000);

try {
    $response = $client->execute($request);
} catch (Exception $e) {
    echo 'Message: ' .$e->getMessage();
}

//Text ganz oben im Browser
echo $response->getBody();
$orders = $request->mapFromResponse($response);

//JSON Builder Delete
foreach ($orders as $order) : ?>
    <h1><?= $order->getId() ?></h1>
    <?php
    $orderDel = new Order();
    $orderDel->setId($order->getId());
    $orderDel->setVersion($order->getVersion());
    $request = RequestBuilder::of()->orders()->delete($orderDel);
    try {
        $response = $client->execute($request);
    } catch (Exception $e) {
        echo 'Message: ' .$e->getMessage();
    }
    echo $response->getStatusCode();
    /*
    switch ($response->getStatusCode()) {
        case 200:
            echo 'Customer: ' . $order->getId() . ' deleted' . '<br>';
            break;
        case 404:
            echo 'Error on Customer ' . $order->getId() . '<br>';
            break;
        default:
            echo 'Keine Ahnung';
    }
    */
    ?>
<?php
endforeach;