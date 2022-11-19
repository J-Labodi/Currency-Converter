<?php
session_start();
date_default_timezone_set('Europe/London');
extract($_GET);

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
/*
  curl_close($curl);
  echo '<pre>';
  echo $response;
  echo '</pre>';
*/

  // decode json response 
  $array = json_decode($response, true);
  echo '<pre>';
  global $rates_array;
  $rates_array = array_slice($array["rates"], 0); 
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
  callAPI();
  echo "It is time to call the API";
}
/*
echo "GL: <br>"; 
print_r($rates_array);
echo "<br>/GL";
*/
/*
echo '<br>';
echo 'loop through:<br>';
foreach($rates_array as $key => $value) { 
  echo $key . ": " . $value . "<br>"; 
} 

// Getting rates from xml
foreach($xmldoc->getElementsByTagName('rate') as $child){
  echo 'attribute value is: ' . $child->getAttribute('rate') . '<br>';
}
*/

// TODO Insert rates to rates.xml


echo 'loop through:<br>';
foreach($rates_array as $key => $value) { 
  echo $key . ": " . $value . "<br>"; 
} 
echo '<br>';

foreach($xmldoc->getElementsByTagName('rate') as $child){
  echo "outer loop: <br>";
  foreach($rates_array as $key => $value){
    echo "first inner loop: <br>";
    $child->setAttribute('rate', $value);
    echo 'value to be set is : ' . $value . '<br>';
    echo "attribute is set <br>";
  }
  $xmldoc->saveXML();
  $xmldoc->save('file.xml');
}

 






// TODO Complete conversion based on query string 
/* TODO if format is missing, system should return xml as default 
TODO if there is more than one error in request - parameter is missing and 
parameter not recognised - error message should report on the first error 
// TODO create config file for API key
*/
// Rename file to index.php
//test
?>