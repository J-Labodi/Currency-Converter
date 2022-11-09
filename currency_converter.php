<?php
session_start();
extract($_GET);

/* TODO if format is missing, system should return xml as default 
TODO if there is more than one error in request - parameter is missing and 
parameter not recognised - error message should report on the first error 
*/



// TODO Check latest update in XML
// TODO call the API to update rates if needed

/* Fixer API - Exchange rate must be up to date - 
must not be more than 12 hours old */

$curl = curl_init();

$base = "GBP";
$symbols = "AUD,BRL,CAD,CHF,CNY,DKK,EUR,GBP,HKD,HUF,INR,JPY,MXN,MYR,NOK,NZD,PHP,RUB,SEK,SGD,THB,TRY,USD,ZAR";

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.apilayer.com/fixer/latest?symbols={$symbols}&base={$base}",
  CURLOPT_HTTPHEADER => array(
    "Content-Type: text/plain",
    "apikey: Dq3fsfF37Akclavu8mqrD3R723E4KOJm"
  ),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET"
));

$response = curl_exec($curl);

curl_close($curl);
echo '<pre>';
echo $response;
echo '</pre>';


// Decode json response 


$array = json_decode($response, true);
print_r($array);

/*
foreach($array as $key => $value) {
  echo $key . " => " . $value . "<br>";

}
*/

// TODO Perhaps create config file for API key
