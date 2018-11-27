<?php
/**
 * This is a bootstrap file for a phar distribution of the SDK in order to be able to use
 * `require 'commercetools-php-sdk.phar';`
 */
namespace Commercetools\Core;

use Commercetools\Core\Builder\Request\RequestBuilder;
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
use Commercetools\Core\Model\Product\ProductData;

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

/*
$orders = $request->mapFromResponse($response);
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
*/

//////////////////////////////////////
//JSON Builder -> Get Customer Query//
//////////////////////////////////////
$requestCustomers = RequestBuilder::of()->customers()->query();
$requestProducts = RequestBuilder::of()->products()->query()->expand('taxCategory');

try {
    $responseCustomers = $client->execute($requestCustomers);
    $responseProducts = $client->execute($requestProducts);
    //echo $responseProducts->getBody();
} catch (Exception $e) {
    echo 'Message: ' .$e->getMessage();
}

echo $responseProducts->getBody();

$customersArray = $requestCustomers->mapFromResponse($responseCustomers);
$productsArray = $requestProducts->mapFromResponse($responseProducts);

//Variablen
//$orderNumber = 100000000000;    //Start-Bestellnummer
$countOrders = 100;             //Anzahl zu generierender Bestellungen

for ($n = 0; $n < $countOrders; $n++) {
    $customerCount = $customersArray->count() - 1;
    $productCount = $productsArray->count() - 1;

    //Random Customer
    if($customerCount - 1 > 0){
        $randomCustomer = random_int(0, count($customerCount));
    }else
        $randomCustomer = 0;

    //Zufälliger Customer
    $customer = $customersArray->getAt($randomCustomer);
    ///////////////////

    $money = new Money();

    $importOrder = new ImportOrder();
    //$importOrder->setOrderNumber((string)($orderNumber));
    $importOrder->setCustomerId($customer->getId());

    //Für mehrere Items
    $lineItemCollection = new LineItemImportDraftCollection();

    $calculateTotalPrice = 0;
    $orderCount = random_int(1, 4);

    for ($c = 0; $c < $orderCount; $c++) {
        $productRandom = random_int(0, $productCount);
        $quantityRandom = random_int(1, 5);
        $productVariant = new ProductVariantImportDraft();

        //Zufälliges Produkt
        $product = new ProductData();
        $product = $productsArray->getAt($productRandom);
        $productVariant->setSku($product->getMasterData()->getCurrent()->getMasterVariant()->getSku());
        $productVariant->setId($product->getMasterData()->getCurrent()->getMasterVariant()->getId());
        $calculateTotalPrice += $product->getMasterData()->getCurrent()->getMasterVariant()->getPrices()->current()->getValue()->getCentAmount() * $quantityRandom;

        $localizedString = new LocalizedString($product->getMasterData()->getCurrent()->getName()->toArray());

        /////////////LineItem
        $lineItem = new LineItemImportDraft();

        $lineItem->setName($localizedString);
        $lineItem->setVariant($productVariant);
        $lineItem->setProductId($product->getId());

        //Price
        $price = new Price();
        $price->setValue($money::ofCurrencyAndAmount($product->getMasterData()->getCurrent()->getMasterVariant()->getPrices()->current()->getValue()->getCurrencyCode(),
            $product->getMasterData()->getCurrent()->getMasterVariant()->getPrices()->current()->getValue()->getCentAmount()));
        $lineItem->setPrice($price);

        $taxRate = new TaxRate();
        $taxRate->setName($product->getTaxCategory()->getObj()->getName());
        $taxRate->setId($product->getTaxCategory()->getId());
        $taxRate->setAmount($product->getTaxCategory()->getObj()->getRates()->current()->getAmount());
        $taxRate->setIncludedInPrice($product->getTaxCategory()->getObj()->getRates()->current()->getIncludedInPrice());
        $taxRate->setCountry($product->getTaxCategory()->getObj()->getRates()->current()->getCountry());
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
    $totalNet = 0;
    $totalGross = 0;
    $taxPortion = 0;
    $i = 0;
    foreach($lineItemCollection as $item){
        $productA = new ProductData();
        $productA = $productsArray->getById($item->getProductId());
        echo 'Testdstg' . $productA->getMasterData()->getCurrent()->getMasterVariant()->getPrices()->current()->getValue()->getCentAmount();
        $totalGross += $productA->getMasterData()->getCurrent()->getMasterVariant()->getPrices()->current()->getValue()->getCentAmount() * $item->getQuantity();
        $totalNet += $productA->getMasterData()->getCurrent()->getMasterVariant()->getPrices()->current()->getValue()->getCentAmount() / $productA->getTaxCategory()->getObj()->getRates()->current()->getAmount();
        $taxPortion += $productA->getMasterData()->getCurrent()->getMasterVariant()->getPrices()->current()->getValue()->getCentAmount() - $productA->getMasterData()->getCurrent()->getMasterVariant()->getPrices()->current()->getValue()->getCentAmount() / $productA->getTaxCategory()->getObj()->getRates()->current()->getAmount();

        if($i == 0){
            $totalNet = $shippingCosts / 1.19;
            $totalGross = $shippingCosts;
            $taxPortion = $shippingCosts - $shippingCosts / 1.19;
        }
    }
    $taxedPrice->setTotalNet($money::ofCurrencyAndAmount('EUR', intval(round($totalNet,0))));
    //TotalGross -> Gesamtpreis inklusive Steuern und Versand
    $taxedPrice->setTotalGross($money::ofCurrencyAndAmount("EUR", $totalGross));

    $taxPortionElement = new TaxPortion();
    $taxPortionElement->setName('MWS');
    $taxPortionElement->setRate(0.19);
    //Different aus Gesamtpreis - (Gesamtpreis ./. 1,19) beides inklusive Steuern
    $taxPortionElement->setAmount($money::ofCurrencyAndAmount('EUR', intval(round(($calculateTotalPrice + $shippingCosts) - (($calculateTotalPrice + $shippingCosts) / 1.19),0))));
    $taxPortionCollection = new taxPortionCollection();
    $taxPortionCollection->add($taxPortionElement);
    $taxedPrice->setTaxPortions($taxPortionCollection);

    $importOrder->setTaxedPrice($taxedPrice);
    $importOrder->setLineItems($lineItemCollection);

    //Shipping-/Billing-Address
    $address = new Address();
    $address->setFirstName($customer->getFirstName());
    $address->setLastName($customer->getFirstName());
    $address->setStreetName($customer->getDefaultBillingAddress()->getStreetName());
    $address->setStreetNumber($customer->getDefaultBillingAddress()->getStreetNumber());
    $address->setPostalCode($customer->getDefaultBillingAddress()->getPostalCode());
    $address->setCity($customer->getDefaultBillingAddress()->getCity());
    $address->setState($customer->getDefaultBillingAddress()->getState());
    $address->setCountry($customer->getDefaultBillingAddress()->getCountry());

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
