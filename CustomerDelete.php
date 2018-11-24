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
use Commercetools\Core\Model\Customer\Customer;
use Symfony\Component\Yaml\Yaml;

$appConfig = Yaml::parse(file_get_contents('myapp.yml'));
$context = Context::of()->setLanguages(['de'])->setGraceful(true);
// create the api client config object
$config = Config::fromArray($appConfig['parameters'])->setContext($context);
$client = Client::ofConfig($config);

/**
 * show result (would be a view layer in real world)
 */
header('Content-Type: text/html; charset=utf-8');

//JSON Builder Query
$request = RequestBuilder::of()->customers()->query();

//$customer = new CustomerCollection();

try {
    $response = $client->execute($request);
} catch (Exception $e) {
    echo 'Message: ' .$e->getMessage();
}

//Text ganz oben im Browser
echo $response->getBody();
$customers = $request->mapFromResponse($response);

//JSON Builder Delete
foreach ($customers as $customer) : ?>
    <h1><?= $customer->getId() ?></h1>
    <?php
    $customerDel = new Customer();
    $customerDel->setId($customer->getId());
    $customerDel->setVersion($customer->getVersion());
    $request = RequestBuilder::of()->customers()->delete($customerDel);
    try {
        $response = $client->execute($request);
    } catch (Exception $e) {
        echo 'Message: ' .$e->getMessage();
    }

    switch ($response->getStatusCode()) {
        case 200:
            echo 'Customer: ' . $customer->getId() . ' deleted' . '<br>';
        break;
        case 404:
            echo 'Error on Customer ' . $customer->getId() . '<br>';
        break;
        default:
            echo 'Keine Ahnung';
    }
    ?>
<?php
endforeach;