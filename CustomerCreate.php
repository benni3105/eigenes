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
use Commercetools\Core\Model\Customer\CustomerDraft;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Common\AddressCollection;
use Symfony\Component\Yaml\Yaml;

$appConfig = Yaml::parse(file_get_contents('../api_credentials/myapp.yml'));
$context = Context::of()->setLanguages(['de'])->setGraceful(true);
$config = Config::fromArray($appConfig['parameters'])->setContext($context);
$client = Client::ofConfig($config);

/**
 * show result (would be a view layer in real world)
 */
header('Content-Type: text/html; charset=utf-8');

//Variablen
$customerShippingAdresssWeichtAb = false;
//$custumerNumber = strval(1000000004);

$customerTitle = 'Herr';
$customerEmail = 'as@bdee.de';
$customerPassword = '123456';
$customerFirstName = 'Hans';
$customerLastName = 'Peter';
$customerLocale = 'DE';
$customerMiddleName = '';
//$customerDateOfBirth = (new \DateTime())->format('Y-m-d');
$customerDateOfBirth = new DateTime();
$customerStreetName = 'Sehr Weit Weg';
$customerStreetNumber = '10';
$customerPostalCode = '65462';
$customerCity = 'Entenhausen';
$customerState = 'Hessen';
$customerCountry = 'DE';

//Billing-Address
$billingAddress = new Address();
$billingAddress->setFirstName($customerFirstName);
$billingAddress->setLastName($customerLastName);
$billingAddress->setStreetName($customerStreetName);
$billingAddress->setStreetNumber($customerStreetNumber);
$billingAddress->setPostalCode($customerPostalCode);
$billingAddress->setCity($customerCity);
$billingAddress->setState($customerState);
$billingAddress->setCountry($customerCountry);

$customer = new CustomerDraft();
//$customer->setCustomerNumber($custumerNumber);
$customer->setEmail($customerEmail);
$customer->setPassword($customerPassword);
$customer->setTitle($customerTitle);
$customer->setFirstName($customerFirstName);
$customer->setLastName($customerLastName);
$customer->setMiddleName($customerMiddleName);
$customer->setDateOfBirth($customerDateOfBirth);
$customer->setLocale($customerLocale);

$addressCollection = new AddressCollection;
$addressCollection->add($billingAddress);
$customer->setAddresses($addressCollection);
$customer->setBillingAddresses(array($addressCollection->count() - 1));

if($customerShippingAdresssWeichtAb == true) {
    $shippingAddress = new Address();
    $shippingAddress->setFirstName($customerFirstName);
    $shippingAddress->setLastName($customerLastName);
    $shippingAddress->setStreetName($customerStreetName);
    $shippingAddress->setStreetNumber($customerStreetNumber);
    $shippingAddress->setPostalCode($customerPostalCode);
    $shippingAddress->setCity($customerCity);
    $shippingAddress->setState($customerState);
    $shippingAddress->setCountry($customerCountry);
    $addressCollection->add($shippingAddress);

    $customer->setShippingAddresses(array($addressCollection->count() - 1));
    $customer->setDefaultShippingAddress($addressCollection->getAt($addressCollection->count() - 1)->getId());
}else {
    $customer->setShippingAddresses(array($addressCollection->count() - 1));
    $index = $addressCollection->count() - 1;
    $customer->setDefaultShippingAddress($addressCollection->count() - 1);
}
    $customer->setDefaultBillingAddress($addressCollection->count() - 1);

//JSON Builder
$createCustomer = RequestBuilder::of()->customers()->create($customer);

try {
    $response = $client->execute($createCustomer)->getBody();
} catch (Exception $e) {
    echo 'Message: ' .$e->getMessage();
}

echo $response;