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
use Commercetools\Core\Model\Order\ImportOrder;
use Commercetools\Core\Model\Order\LineItemImportDraftCollection;
use Commercetools\Core\Model\Order\LineItemImportDraft;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Common\Price;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Order\ProductVariantImportDraft;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Cart\ShippingInfo;
use Commercetools\Core\Model\ShippingMethod\ShippingRate;
use Symfony\Component\Yaml\Yaml;

$appConfig = Yaml::parse(file_get_contents('../api_credentials/myapp.yml'));

$context = Context::of()->setLanguages(['de'])->setGraceful(true);
$config = Config::fromArray($appConfig['parameters'])->setContext($context);

$client = Client::ofConfig($config);

/**
 * show result (would be a view layer in real world)
 */
header('Content-Type: text/html; charset=utf-8');

//Produkte
$customersArray = array("firstName" => "Jochen",
                                    "LastName" => "Wolf",
                                    "StreetName" => "Weit Weg",
                                    "streetNumber" => "5",
                                    "postalCode" => "43499",
                                    "city" => "Bocholt",
                                    "state" => "NRW",
                                    "Country" => "DE",
                                    "CustomerId" => "45ea0aeb-7130-4913-b974-103bbe3e58eb");

$productArray = array("Seidentuch" => array("sku" => "sku_seidentuch_variant1_100011",
                                    "price" => 2200),
                array("Krawatte" => array("sku" => "sku_krawatte_variant1_100010",
                                    "price" => 1600),
                array("Herren-Kapuzenjacke" => array("sku" => "sku_herren-kapuzenjacke_variant1_100003",
                                    "price" => 2100 ))));

$calculateTotalPrice = 0;

//Required TotalPrice
$money = new Money();
$totalPrice = $money::ofCurrencyAndAmount("EUR", 2500, $context);

/////////////LineItem
$lineItem = new LineItemImportDraft();

//Required Product
$productVariant = new ProductVariantImportDraft();
$productVariant->setSku('sku_seidentuch_variant1_100011');
$lineItem->setVariant($productVariant);

//Required Product Name
$local = ['de' => 'Seidentuch'];
$localizedString = new LocalizedString($local);
$lineItem->setName($localizedString);

//Price
$price = new Price();
$price->setValue($money::ofCurrencyAndAmount('EUR', 2500));
$lineItem->setPrice($price);

//Quantity
$lineItem->setQuantity(1);
//////////////End LineItem

//Für mehrere
$lineItemCollection = new LineItemImportDraftCollection();
$lineItemCollection->add($lineItem);

$importOrder = new ImportOrder();
$importOrder->setOrderNumber('100000000000');
$importOrder->setCustomerId('45ea0aeb-7130-4913-b974-103bbe3e58eb');
$importOrder->setTotalPrice($totalPrice);
$importOrder->setLineItems($lineItemCollection);

//Shipping-/Billing-Address
$address = new Address();
$address->setFirstName('Jochen');
$address->setLastName('Wolf');
$address->setStreetName('Weit Weg');
$address->setStreetNumber('5');
$address->setPostalCode('43499');
$address->setCity('Bocholt');
$address->setState('NRW');
$address->setCountry('DE');

$importOrder->setShippingAddress($address);
$importOrder->setBillingAddress($address);

//ShippingInfo
$shippingInfo = new ShippingInfo();
$shippingInfo->setShippingMethodName('DHL');
$shippingInfo->setPrice($money::ofCurrencyAndAmount('EUR', 570));

//ShippingRate
$shippingRate = new ShippingRate();
$shippingRate->setPrice($money::ofCurrencyAndAmount('EUR', 570));
$shippingInfo->setShippingRate($shippingRate);

$importOrder->setShippingInfo($shippingInfo);

$create = RequestBuilder::of()->orders()->import($importOrder);

$client = Client::ofConfig($config);
try {
    $response = $client->execute($create)->getBody();
} catch (Exception $e) {
    echo 'Message: ' .$e->getMessage();
}

echo $response;

