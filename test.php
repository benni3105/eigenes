<?php
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

$productArray = array(array(	'name' => "Seidentuch",'sku' => "sku_seidentuch_variant1_100011",'price' => 2200),
				array(	'name' => "Krawatte",'sku' => "sku_krawatte_variant1_100010",'price' => 1600),
				array(	'name' => "Herren-Kapuzenjacke",'sku' => "sku_herren-kapuzenjacke_variant1_100003",'price' => 2100 ));

$countCustomer = count($customerArray) - 1;
echo strval($customerArray[$countCustomer]["firstName"]);
/*
$calculateTotalPrice = 0;
$shipping = 570;

$calculateTotalPrice += $productArray[2]['price'] * 3;
echo $calculateTotalPrice . '<br>';
$calculateTotalPrice += 100 * 5;

echo 'Gesamt: ' . $calculator = ($calculateTotalPrice + $shipping) . '<br>';
echo 'totalNet:' . intval(round((($calculateTotalPrice + $shipping) / 1.19),0)). '<br>';
echo 'totalGross:' . ($calculateTotalPrice + $shipping) . '<br>';
echo $calculateTotalPrice . '   ' . (($calculateTotalPrice + $shipping) / 1.19) . '<br>';
echo 'taxPortion: ' . round(($calculateTotalPrice + $shipping) - (($calculateTotalPrice + $shipping) / 1.19),0) . '<br>';
$gesamt = $calculateTotalPrice + $shipping;
$differenz = ($calculateTotalPrice + $shipping) / 1.19;
echo 'taxPortion: ' . round($gesamt - $differenz,0) . '<br>';
echo count($customersArray);*/


