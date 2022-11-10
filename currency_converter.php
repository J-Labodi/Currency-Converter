<?php
session_start();
extract($_GET);
date_default_timezone_set('Europe/London');

function callAPI(){
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
  echo '<pre>';
  print_r($array["rates"]);
  echo '</pre>';

}

// Check latest update in XML
$xmldoc = new DOMDocument();
$xmldoc->load('rates.xml');

// get timestamp from rates.xml
$element = $xmldoc->getElementsByTagName("rates");
$ts = $element[0]->getAttribute('timestamp');

// convert timestamp to Int from rates.xml
$ts = (integer)$ts;
echo "Time from xml is: ", $ts, "<br>"; 

// get current time
$t = time();
echo "System time is: ", $t, "<br>";

// calculate time diff
$time_diff = $t - $ts;
echo "Time difference is: ", $time_diff, "<br>";

// call the API to update rates if needed
if ($time_diff > 43200){
  echo "It is time to call the API";
}

// TODO Insert rates to rates.xml


// TODO Complete conversion based on query string 


/* TODO if format is missing, system should return xml as default 
TODO if there is more than one error in request - parameter is missing and 
parameter not recognised - error message should report on the first error 
// TODO create config file for API key
*/


//test
