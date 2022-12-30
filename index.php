<?php
session_start();
date_default_timezone_set('Europe/London');

// define constants - supported parameters, formats, currencies
define('PARAMS', array("from", "to", "amnt", "format"));

define('FORMATS', array("xml", "json"));

define('CURRENCIES', array("AUD", "BRL", "CAD", "CHF", 
"CNY", "DKK", "EUR", "GBP", "HKD", "HUF", "INR", "JPY", 
"MXN", "MYR", "NOK", "NZD", "PHP", "RUB", "SEK", "SGD", 
"THB", "TRY", "USD", "ZAR"));

// define constants - error messages
define('ERRMESSAGES', array(
"1000" => "Required parameter is missing",
"1100" => "Parameter not recognized",
"1200" => "Currency type not recognized",
"1300" => "Currency amount must be a decimal number",
"1400" => "Format must be xml or json",
"1500" => "Error in service"
));

function callAPI(){

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

  $rates = curl_exec($curl);
  curl_close($curl);

  // convert rates to associative array 
  $assoc_array = json_decode($rates, true);

  // define rates as global
  global $response;
  $response = $assoc_array;
  
}

function generateErrorm($code){

  // generate xml error output
  $dom_err = new DOMDocument();
  $dom_err->encoding = "UTF-8";
  $dom_err->xmlVersion = "1.0";
  $dom_err->formatOutput = true;

  $err_root = $dom_err->createElement('conv');
  $err_node = $dom_err->createElement('error');  

  $err_root->appendChild($err_node);

  $child_node_code = $dom_err->createElement('code', $code);  
  $child_node_msg = $dom_err->createElement('msg', ERRMESSAGES[$code]); 

  $err_node->appendChild($child_node_code);
  $err_node->appendChild($child_node_msg);

  $dom_err->appendChild($err_root);

  // generate json output if required
  global $format;
  if (!empty($format) && ($format == 'json')){
    // access data to generate json 
    $data_err = $dom_err->saveXML();
    // load xml data into xml data object
    $xmldata_err = simplexml_load_string($data_err);
    // encode xml data into json
    $jsondata_err = json_encode($xmldata_err, JSON_PRETTY_PRINT);
    // display json output
    echo '<pre>' . $jsondata_err . '</pre>';
  }
  else{
    // display xml output
    echo '<pre>' . $dom_err->saveXML() . '</pre>';
  }    
}

// ensure base file exist - if true, get values
if (file_exists('rates.xml')){
  extract($_GET);
}
else{
  exit('Base file not found');
}

// ensure parameters in query string are valid
if ($_GET){
  $queries = array();
  parse_str($_SERVER['QUERY_STRING'], $queries);
  $queries = array_keys($queries);

  /* compare parameters from query string 
  to supported parameters */
  $diff = array_diff($queries, PARAMS);
  if (count($diff) > 0){
    generateErrorm("1100");
    exit();
  }
}

// ensure required parameters are provided 
if (empty($from) || empty($to) || empty($amnt)){
  generateErrorm("1000");
  exit();
}

// ensure provided currency type is supported
if (!in_array($from, CURRENCIES) || !in_array($to, CURRENCIES)){
  generateErrorm("1200");
  exit();
}

// ensure provided amount decimal number
if (is_numeric($amnt) && strpos($amnt, '.') === false){
  generateErrorm("1300");
  exit();
}

// ensure provided format is supported
if (!empty($format) && !in_array($format, FORMATS)){
  generateErrorm("1400");
  exit();
}

// load rates.xml with simpleXML
$xml = simplexml_load_file("rates.xml") or die ("Error: Cannot create object");

// ensure data is older than 12 hours
$t = time();
$rates_ts = (int) $xml['ts'];
$time_diff = $t - $rates_ts;

// update rates file, reinitialize rates if data older than 12 hours
if($time_diff > 43200){  
  // utilise callAPI function
  callAPI();

  // update currency rates in rates.xml
  $count = 0;
  $rate_att = 'rate';
  foreach ($response['rates'] as $k => $v){
    // access xml attribute and update it
    $xml->currency[$count]->attributes()->$rate_att = $v;
    $count++;
    $xml->asXMl('rates.xml');
  }

  // update timestamp
  $ts_att = 'ts';
  $xml->attributes()->$ts_att = time();
  $xml->asXMl('rates.xml');
  $rates_ts = (int) $xml['ts'];
}

/*
echo "current: " . $time . '<br>';
echo "rates: " . $rates_ts . '<br>';
echo "time diff: " . $time_diff;
*/

// access values to complete conversion - rate, code, curr, location
$conv_from = $xml->xpath("/rates/currency[code='$from']");

$conv_from_rate = $conv_from[0]['rate'];
$conv_from_code = $conv_from[0]->code;
$conv_from_curr = $conv_from[0]->curr;
$conv_from_loc = $conv_from[0]->loc;


$conv_to = $xml->xpath("/rates/currency[code='$to']");

$conv_to_rate = $conv_to[0]['rate'];
$conv_to_code = $conv_to[0]->code;
$conv_to_curr = $conv_to[0]->curr;
$conv_to_loc = $conv_to[0]->loc;


// complete the conversion
$result = $conv_from_rate * $conv_to_rate * $amnt;

// generate xml output
$dom = new DOMDocument();
$dom->encoding = "UTF-8";
$dom->xmlVersion = "1.0";
$dom->formatOutput = true;

$root = $dom->createElement('conv');
$at_node = $dom->createElement('at', gmdate("d M Y H:i", $rates_ts));
$rate_node = $dom->createElement('rate', $conv_to_rate);
$from_node = $dom->createElement('from');
$to_node = $dom->createElement('to');

$root->appendChild($at_node);
$root->appendChild($rate_node);
$root->appendChild($from_node);
$root->appendChild($to_node);

$child_node_code = $dom->createElement('code', $conv_from_code);
$from_node->appendChild($child_node_code);
$child_node_curr = $dom->createElement('curr', $conv_from_curr);
$from_node->appendChild($child_node_curr);
$child_node_loc = $dom->createElement('loc', $conv_from_loc);
$from_node->appendChild($child_node_loc);
$child_node_amnt = $dom->createElement('amnt', $amnt);
$from_node->appendChild($child_node_amnt);

$child_node_code_2 = $dom->createElement('code', $conv_to_code);
$to_node->appendChild($child_node_code_2);
$child_node_curr_2 = $dom->createElement('curr', $conv_to_curr);
$to_node->appendChild($child_node_curr_2);
$child_node_loc_2 = $dom->createElement('loc', $conv_to_loc);
$to_node->appendChild($child_node_loc_2);
$child_node_amnt_2 = $dom->createElement('amnt', $result);
$to_node->appendChild($child_node_amnt_2);

$dom->appendChild($root);

// generate json output if required
if (!empty($format) && ($format == 'json')){
  // access data to generate json 
  $data = $dom->saveXML();
  // load xml data into xml data object
  $xmldata = simplexml_load_string($data);
  // encode xml data into json
  $jsondata = json_encode($xmldata, JSON_PRETTY_PRINT);
  // display json output
  echo '<pre>' . $jsondata . '</pre>';
}
else{
  // display xml output
  echo '<pre>' . $dom->saveXML() . '</pre>';
}


/*

echo '1500: Error in service';

Try to improve code by adding xpath (week7)
Should I keep "live" attributes?
Are we using GBP only as from

Add constants

json error doesn't show root

compare with prakash code example

change code for localhost instead of localhost:8000

open xml system error? L146

*/
?>