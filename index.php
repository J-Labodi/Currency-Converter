<?php
session_start();

// include config data
include('config.php'); 


function callAPI(){

  $curl = curl_init();

  $base = constant("BASE");
  $symbols = implode(",", LIVE);

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

function generateErrorm($err_code){

  // generate xml error output
  $dom_err = new DOMDocument();
  $dom_err->encoding = "UTF-8";
  $dom_err->xmlVersion = "1.0";
  $dom_err->formatOutput = true;

  $err_root = $dom_err->createElement('conv');
  $err_node = $dom_err->createElement('error');  

  $err_root->appendChild($err_node);

  $child_node_code = $dom_err->createElement('code', $err_code);  
  $child_node_msg = $dom_err->createElement('msg', ERRMESSAGES[$err_code]); 

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

function generateRatesXML(){

  // get the iso currencies xml file
  $iso_xml = simplexml_load_file(ISO_XML) or die("Error: Cannot load currencies file");   
  // get all the currency codes
  $iso_codes = $iso_xml->xpath("//CcyNtry/Ccy");

  $codes=[];
  foreach ($iso_codes as $code) {
    $codes[] = (string) $code;
  }
  $codes = array_unique($codes);

  // create an array of unique (sorted) codes
  foreach ($iso_codes as $code) {
    if (!in_array($code, $codes)) {
      $codes[] = (string) $code;
    }
  }

  sort($codes);

  // build the document with XMLWriter
  $writer = new XMLWriter();
  $writer->openMemory();
  $writer->startDocument("1.0", "UTF-8");
  $writer->startElement("rates");
  $writer->writeAttribute('ts', '0');
  $writer->writeAttribute('base', BASE);

  foreach ($codes as $code) { 

    // pull all currencies that matches the current code
    $nodes = $iso_xml->xpath("//Ccy[.='$code']/parent::*");
    
    // get the code value from the first entry 
    $cname =  $nodes[0]->CcyNm;

    $writer->startElement('currency');
    
    $writer->writeAttribute('rate', '');
    
        
    if (in_array($code, LIVE)) {
      $writer->writeAttribute('live', 1);
    }
    else {
      $writer->writeAttribute('live', 0);
    }
    
    $writer->startElement('code');
    $writer->text($code);
    $writer->endElement();
    $writer->startElement("curr");
    $writer->text($cname);
    $writer->endElement();

    $writer->startElement("loc");
        
    $last = count($nodes) - 1;
        
    /* group countries together using the same code
    and lowercase first letter in name and 
    then write it out with the first letter upper-cased
    */
    foreach ($nodes as $index=>$node) {
      $writer->text(mb_convert_case($node->CtryNm, MB_CASE_TITLE, "UTF-8"));
      if ($index!=$last) {$writer->text(', ');}
    }
    
    // end the loc element
    $writer->endElement();
    
    // end the currency element
    $writer->endElement();
  }

  // end the root element and document
  $writer->endElement();
  $writer->endDocument();

  // write out and save the file
  file_put_contents(RATES, $writer->outputMemory());
}

// ensure rates file exist - if false, generate it
if (!file_exists('rates.xml')){
  generateRatesXML();
}

// get parameters from query string 
extract($_GET);

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
if (!in_array($from, LIVE) || !in_array($to, LIVE)){
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
$xml = simplexml_load_file("rates.xml") or die ("Error: Cannot load rates file");

// ensure data is older than 12 hours
$t = time();
$rates_ts = (int) $xml['ts'];
$time_diff = $t - $rates_ts;

// update rate in rates.xml
if($time_diff > 43200){  

  // utilise callAPI() function
  callAPI();

  // update currency rates in rates.xml
  $rate_att = 'rate';
  foreach ($response['rates'] as $k => $v){
    // access node from rates.xml file
    $node = $xml->xpath("./currency[code = '$k']");

    // update rate 
    $node[0]->attributes()->rate = $v;

    $xml->asXMl('rates.xml');
  }

  // update timestamp
  $ts_att = 'ts';
  $xml->attributes()->$ts_att = time();
  $xml->asXMl('rates.xml');
  $rates_ts = (int) $xml['ts'];
}

// access element to complete conversion from
$conv_from = $xml->xpath("/rates/currency[code='$from']");

// access element to complete conversion to
$conv_to = $xml->xpath("/rates/currency[code='$to']");

// complete the conversion
$result = $conv_from[0]['rate'] * $conv_to[0]['rate'] * $amnt;

// generate xml output, inserting rate, code, curr, location values
$dom = new DOMDocument();
$dom->encoding = "UTF-8";
$dom->xmlVersion = "1.0";
$dom->formatOutput = true;

$root = $dom->createElement('conv');
$at_node = $dom->createElement('at', gmdate("d M Y H:i", $rates_ts));
$rate_node = $dom->createElement('rate', $conv_to[0]['rate']);
$from_node = $dom->createElement('from');
$to_node = $dom->createElement('to');

$root->appendChild($at_node);
$root->appendChild($rate_node);
$root->appendChild($from_node);
$root->appendChild($to_node);

$child_node_code = $dom->createElement('code', $conv_from[0]->code);
$from_node->appendChild($child_node_code);
$child_node_curr = $dom->createElement('curr', $conv_from[0]->curr);
$from_node->appendChild($child_node_curr);
$child_node_loc = $dom->createElement('loc', $conv_from[0]->loc);
$from_node->appendChild($child_node_loc);
$child_node_amnt = $dom->createElement('amnt', $amnt);
$from_node->appendChild($child_node_amnt);

$child_node_code_2 = $dom->createElement('code', $conv_to[0]->code);
$to_node->appendChild($child_node_code_2);
$child_node_curr_2 = $dom->createElement('curr', $conv_to[0]->curr);
$to_node->appendChild($child_node_curr_2);
$child_node_loc_2 = $dom->createElement('loc', $conv_to[0]->loc);
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

json error doesn't show root

change code for localhost instead of localhost:8000

open xml error would be a system error?


*/
?>