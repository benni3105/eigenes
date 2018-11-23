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
use Commercetools\Core\Model\TaxCategory\TaxRate;
use Commercetools\Core\Model\Common\TaxedPrice;
use Commercetools\Core\Model\Common\TaxPortion;
use Commercetools\Core\Model\Common\TaxPortionCollection;
use Commercetools\Core\Model\Customer\Customer;

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/../vendor/autoload.php';

$appConfig = Yaml::parse(file_get_contents('../api_credentials/myapp.yml'));
$context = Context::of()->setLanguages(['en'])->setGraceful(true);

// create the api client config object
$config = Config::fromArray($appConfig['parameters'])->setContext($context);
$client = Client::ofConfig($config);

/**
 * show result (would be a view layer in real world)
 */
header('Content-Type: text/html; charset=utf-8');

$customerArray = array(
                    array(
                        'firstName' => "Jochen",
                        'lastName' => "Wolf",
                        'streetName' => "Weit Weg",
                        'streetNumber' => "5",
                        'postalCode' => "43499",
                        'city' => "Bocholt",
                        'state' => "NRW",
                        'country' => "DE",
                        'customerId' => "45ea0aeb-7130-4913-b974-103bbe3e58eb")
                    );

$productArray = array(array('name' => "Seidentuch", 'sku' => "sku_seidentuch_variant1_100011", 'price' => 2200),
    array('name' => "Krawatte", 'sku' => "sku_krawatte_variant1_100010", 'price' => 1600),
    array('name' => "Herren-Kapuzenjacke", 'sku' => "sku_herren-kapuzenjacke_variant1_100003", 'price' => 2100));

//Variablen
//$orderNumber = 100000000000;    //Start-Bestellnummer
$countOrders = 100;             //Anzahl zu generierender Bestellungen
$countCustomer = count($customerArray) - 1;
$countProducts = count($productArray) - 1;

for ($n = 0; $n < $countOrders; $n++) {
    if($countCustomer > 0)
        $customer = random_int(0, count($customerArray));
    else
        $customer = 0;

    $money = new Money();

    $importOrder = new ImportOrder();
    //$importOrder->setOrderNumber((string)($orderNumber));
    $importOrder->setCustomerId(strval($customerArray[$customer]["customerId"]));

    //Für mehrere Items
    $lineItemCollection = new LineItemImportDraftCollection();

    $calculateTotalPrice = 0;
    $orderCount = random_int(1, 4);

    for ($c = 0; $c < $orderCount; $c++) {
        $productRandom = random_int(0, $countProducts);
        $quantityRandom = random_int(1, 5);
        $local = null;

        $productVariant = new ProductVariantImportDraft();

        $productVariant->setSku($productArray[$productRandom]["sku"]);

        $calculateTotalPrice += $productArray[$productRandom]["price"] * $quantityRandom;

        $local = ['de' => $productArray[$productRandom]["name"]];

        $localizedString = new LocalizedString($local);

        /////////////LineItem
        $lineItem = new LineItemImportDraft();

        $lineItem->setName($localizedString);
        $lineItem->setVariant($productVariant);

        //Price
        $price = new Price();
        $price->setValue($money::ofCurrencyAndAmount('EUR', $productArray[$productRandom]["price"]));
        $lineItem->setPrice($price);

        $taxRate = new TaxRate();
        $taxRate->setName('MWS');
        $taxRate->setAmount(0.19);
        $taxRate->setIncludedInPrice(true);
        $taxRate->setCountry('DE');
        $lineItem->setTaxRate($taxRate);

        //Quantity
        $lineItem->setQuantity($quantityRandom);

        $lineItemCollection->add($lineItem);
    }
    //Required TotalPrice
    $shippingCosts = 570; //weiter unten KB und so
    $totalPrice = $money::ofCurrencyAndAmount("EUR", ($calculateTotalPrice + $shippingCosts), $context);
    $importOrder->setTotalPrice($totalPrice);

    //TaxedPrice
    $taxedPrice = new TaxedPrice();
    //TotalPrice = Gesamtsumme der Steuern d. h. Gesamtsumme der Bestellung + Versand ./. 1,19
    $taxedPrice->setTotalNet($money::ofCurrencyAndAmount('EUR', intval(round((($calculateTotalPrice + $shippingCosts) / 1.19),0))));
    //TotalGross -> Gesamtpreis inklusive Steuern und Versand
    $taxedPrice->setTotalGross($money::ofCurrencyAndAmount("EUR", ($calculateTotalPrice + $shippingCosts)));

    $taxPortion = new TaxPortion();
    $taxPortion->setName('MWS');
    $taxPortion->setRate(0.19);
    //Different aus Gesamtpreis - (Gesamtpreis ./. 1,19) beides inklusive Steuern
    $taxPortion->setAmount($money::ofCurrencyAndAmount('EUR', intval(round(($calculateTotalPrice + $shippingCosts) - (($calculateTotalPrice + $shippingCosts) / 1.19),0))));
    $taxPortionCollection = new taxPortionCollection();
    $taxPortionCollection->add($taxPortion);
    $taxedPrice->setTaxPortions($taxPortionCollection);

    $importOrder->setTaxedPrice($taxedPrice);
    $importOrder->setLineItems($lineItemCollection);

    //Shipping-/Billing-Address
    $address = new Address();
    $address->setFirstName($customerArray[$customer]["firstName"]);
    $address->setLastName($customerArray[$customer]["lastName"]);
    $address->setStreetName($customerArray[$customer]["streetName"]);
    $address->setStreetNumber($customerArray[$customer]["streetNumber"]);
    $address->setPostalCode($customerArray[$customer]["postalCode"]);
    $address->setCity($customerArray[$customer]["city"]);
    $address->setState($customerArray[$customer]["state"]);
    $address->setCountry($customerArray[$customer]["country"]);

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

    try {
        $response = $client->execute($create)->getBody();
    } catch (Exception $e) {
        echo 'Message: ' . $e->getMessage();
    }

    echo $response;

    //$orderNumber++;
}
