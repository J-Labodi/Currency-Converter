<?php
session_start();
// include config data
include('config.php'); 

// callAPI function to call external exchange service - Fixer API
function callAPI(){
  $curl = curl_init();

  // access base currency and initial live currencies for the URL parameters
  $base = constant("BASE");
  $symbols = implode(",", LIVE);    

  // details of API request
  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.apilayer.com/fixer/latest?base={$base}",
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

  // retain current rates as JSON
  $rates = curl_exec($curl);
  curl_close($curl);

  // convert rates to associative array 
  $response = json_decode($rates, true);

  return $response;
}

// generateErrorm function to generate error messages
function generateErrorm($err_code){
  // generate xml error output with DOMDocument class - define XML prolog
  $dom_err = new DOMDocument();
  $dom_err->encoding = "UTF-8";
  $dom_err->xmlVersion = "1.0";
  $dom_err->formatOutput = true;

  // create root and its child element
  $err_root = $dom_err->createElement('conv');
  $err_node = $dom_err->createElement('error');  

  $err_root->appendChild($err_node);

  /* create sub elements - insert the value of $err_code 
  and the corresponding message from ERRMESSAGES constant */
  $child_node_code = $dom_err->createElement('code', $err_code);  
  $child_node_msg = $dom_err->createElement('msg', ERRMESSAGES[$err_code]); 

  $err_node->appendChild($child_node_code);
  $err_node->appendChild($child_node_msg);

  $dom_err->appendChild($err_root);

  // generate JSON output if requested
  global $format;
  if (!empty($format) && $format == 'json'){
    // retain data to generate JSON
    $data_err = $dom_err->saveXML();
    // load xml data into xml data object
    $xmldata_err = simplexml_load_string($data_err);
    // display JSON output (encoded xml data)
    echo json_encode($xmldata_err, JSON_PRETTY_PRINT);
  } else{
    // display xml output
    echo $dom_err->saveXML();
  }    
}

// generateRatesXML function to generate rates.xml file
function generateRatesXML(){
  // get the iso currencies xml file
  $iso_xml = simplexml_load_file(ISO_XML) or die("Error: Cannot load currencies file");   
  // get all the currency codes
  $iso_codes = $iso_xml->xpath("//CcyNtry/Ccy");

  /* load each currency codes to array, 
  remove duplicates and retain the unique codes */
  $codes=[];
  foreach ($iso_codes as $code){
    $codes[] = (string) $code;
  }
  $codes = array_unique($codes);

  // create an array of unique (sorted) codes
  foreach ($iso_codes as $code){
    if (!in_array($code, $codes)){
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

  foreach ($codes as $code){ 

    // pull all currencies that matches the current code
    $nodes = $iso_xml->xpath("//Ccy[.='$code']/parent::*");
    
    // get the code value from the first entry 
    $cname =  $nodes[0]->CcyNm;

    // write element and its rate attribute
    $writer->startElement('currency');
    $writer->writeAttribute('rate', '');
    
    /* write attribute value
    depending on the currency defintion as a live curency */
    if (in_array($code, LIVE)){
      $writer->writeAttribute('live', 1);
    } else{
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
    foreach ($nodes as $index=>$node){
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

// set header based on requested format 
if (!empty($format) && $format == 'json'){
  header('Content-Type: application/json; charset=utf-8');
} else{
  header("Content-type: text/xml");
}

// load rates.xml file with simpleXML
$xml = simplexml_load_file("rates.xml") or die ("Error: Cannot load rates file");

/* Error Handling
ensure parameters in query string are valid - Error 1100 */
if ($_GET){
  $queries = array();
  parse_str($_SERVER['QUERY_STRING'], $queries);
  $queries = array_keys($queries);

  /* compare parameters from query string 
  to supported parameters */
  $diff = array_diff($queries, PARAMS);

  // generate error if there is a difference
  if (count($diff) > 0){
    generateErrorm("1100");
    exit();
  }
}

// ensure required parameters are provided - Error 1000 
if (empty($from) || empty($to) || empty($amnt)){
  generateErrorm("1000");
  exit();
}

// ensure provided currencies are supported (appropriate format) - Error 1200
if (!ctype_upper($from) || strlen($from) != 3){
  generateErrorm('1200');
  exit();
}

if (!ctype_upper($to) || strlen($to) != 3){
  generateErrorm('1200');
  exit();
}

/* ensure provided currencies are supported 
(whether currencies exist in rates.xml) - Error 1200 */

// access from and to currencies from rates.xml file
$curr_from = $xml->xpath("./currency[code = '$from']");
$curr_to = $xml->xpath("./currency[code = '$to']");

// generate error message if variables are empty
if (empty($curr_from) || empty($curr_to)){
  generateErrorm("1200");
  exit();
}

// ensure provided currencies are active (live == '1') - Error 1200
$from_live = $curr_from[0]->attributes()->live;
$to_live = $curr_to[0]->attributes()->live;

// generate error message if live attributes equal to 0 - inactive currencies
if ($from_live == '0'|| $to_live == '0'){
  generateErrorm("1200");
  exit();
}

// ensure provided amount is decimal number - Error 1300
if (!is_numeric($amnt) || strpos($amnt, '.') === false){
  generateErrorm("1300");
  exit();
}

// ensure provided format is supported - Error 1400
if (!empty($format) && !in_array($format, FORMATS)){
  generateErrorm("1400");
  exit();
}

// compare timestamp from rates.xml with current time  
$t = time();
$rates_ts = (int) $xml['ts'];
$time_diff = $t - $rates_ts;

// update rate in rates.xml if data is older than 12 hours (Unix 43200)
if ($time_diff > 43200){  
  // utilise callAPI function to get current rates
  $curr_rates = callAPI();

  // update currency rates in rates.xml
  $rate_att = 'rate';
  foreach ($curr_rates['rates'] as $k => $v){
    // access node from rates.xml file
    $node = $xml->xpath("./currency[code = '$k']");

    // ensure pulled currency is in rates.xml (API provides crypto rates as well)
    if (!empty($node)){
      // update rate if valid currency
      $node[0]->attributes()->rate = $v;
    } else{
      continue;
    }
    
    // save well-formed xml string 
    $xml->asXMl('rates.xml');
  }

  // update timestamp in rates.xml and retain it in $rates_ts
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

// generate xml output
$dom = new DOMDocument();
$dom->encoding = "UTF-8";
$dom->xmlVersion = "1.0";
$dom->formatOutput = true;

// create xml elements with ts and rate values
$root = $dom->createElement('conv');
$at_node = $dom->createElement('at', gmdate("d M Y H:i", $rates_ts));
$rate_node = $dom->createElement('rate', $conv_to[0]['rate']);
$from_node = $dom->createElement('from');
$to_node = $dom->createElement('to');

// append elements to root
$root->appendChild($at_node);
$root->appendChild($rate_node);
$root->appendChild($from_node);
$root->appendChild($to_node);

// create child nodes with appropriate code, curr, loc, amnt, result values
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

// generate JSON output if requested
if (!empty($format) && $format == 'json'){
  // retain data to generate JSON 
  $data = $dom->saveXML();
  // load xml data into xml data object
  $xmldata = simplexml_load_string($data);
  // display JSON output (encoded xml data)
  echo json_encode($xmldata, JSON_PRETTY_PRINT);
} else{
  // display xml output
  echo $dom->saveXML();
}
/*
TODO

echo '1500: Error in service';
open xml error would be a system error?

json error doesn't show root

reset rates.xml before submission

TEST

*/
?>