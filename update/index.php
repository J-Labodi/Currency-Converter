<?php
session_start();
// include config data
include('../config.php'); 

function getRate($symbol){
    $curl = curl_init();
  
    // access base currency for URL parameter
    $base = constant("BASE");
  
    // details of API request
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.apilayer.com/fixer/latest?symbols={$symbol}&base={$base}",
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
  
    // retain JSON data as PHP object 
    $data = json_decode(curl_exec($curl));
    curl_close($curl);

    /* Error Handling 
    ensure rate listed for this currency - Error 2300
    convert properties of object to associative array */
    $response = get_object_vars($data);
    // generate error if API response contain error 202
    if(array_key_exists('error', $response) && $response['error']->code == '202'){
      generateErrorm('2300');;
      exit();
    }

    // access value from object and return it
    $rate = $data->rates->$symbol;
    return $rate;
}

// generateErrorm function to generate error messages
function generateErrorm($err_code){
  // generate xml error output with DOMDocument class - define XML prolog
  $dom_err = new DOMDocument();
  $dom_err->encoding = "UTF-8";
  $dom_err->xmlVersion = "1.0";
  $dom_err->formatOutput = true;

  // create root element and set its attribute 
  $err_root = $dom_err->createElement('action');
  $err_attr = new DOMAttr('type', 'tttt');
  $err_root->setAttributeNode($err_attr);

  // create child element and append it
  $err_node = $dom_err->createElement('error');  
  $err_root->appendChild($err_node);

  /* create sub elements - insert the value of $err_code 
  and the corresponding message from ERRMESSAGES constant */
  $child_node_code = $dom_err->createElement('code', $err_code);  
  $child_node_msg = $dom_err->createElement('msg', ERRMESSAGES2[$err_code]); 

  $err_node->appendChild($child_node_code);
  $err_node->appendChild($child_node_msg);

  $dom_err->appendChild($err_root);

  // display xml output
  echo '<pre>' . $dom_err->saveXML() . '</pre>';
}

// get parameters from query string 
extract($_GET);

// load rates.xml file with simpleXML
$xml = simplexml_load_file("../rates.xml") or die ("Error: Cannot load rates file");

/* Error Handling
ensure value of action is provided and valid - Error 2000 */
if (empty($action) || !in_array($action, ACTIONS)){
    generateErrorm('2000');;
    exit();
}

// ensure value of currency code is provided and in appropriate format - Error 2100
if (empty($cur) || !ctype_upper($cur) || strlen($cur) != 3){
    generateErrorm('2100');
    exit();
}

// ensure value of currency is not the base currency - Error 2400
if ($cur == BASE){
    generateErrorm('2400');
    exit();
}

/*
PUT functionality
generate a call to the external rate service and 
update the rate value for the specific currency within the rates.xml file */
if($action == 'put'){
    /* Error Handling
    ensure provided currency code is available for update - Error 2200 
    load elements of currency from rates.xml */
    $currency = $xml->xpath("./currency[code = '$cur']");
    // generate error if $currency is empty - currency not found
    if(empty($currency)){
      generateErrorm('2200');
      exit();
    } else{
      /* generate error if currency found 
      but its live attribute set to 0 - inactive currency */
      $attr = $currency[0]->attributes()->live; 
      if($attr == '0'){
        generateErrorm('2200');
        exit();
      }
    }

    // access currency from rates.xml to update
    $cur_to_update = $xml->xpath("/rates/currency[code='$cur']");

    // store old rate 
    $old_rate = (string) $cur_to_update[0]['rate'];

    // generate call to API  
    $new_rate = getRate($cur);
    
    // update rate attribute with current rate
    $cur_to_update[0]['rate'] = $new_rate;
    $xml->asXMl('../rates.xml');

    // generate response xml
    $dom = new DOMDocument();
    $dom->encoding = "UTF-8";
    $dom->xmlVersion = "1.0";
    $dom->formatOutput = true;

    /* create root element and type attribute with put as value
    set type attribute for root element */
    $root = $dom->createElement('action');
    $type_attr = new DOMAttr('type', 'put');
    $root->setAttributeNode($type_attr);

    // create xml elements with ts, rate and old rate values
    $at_node = $dom->createElement('at', date("d M Y H:i"));
    $rate_node = $dom->createElement('rate', $cur_to_update[0]['rate']);
    $old_rate_node = $dom->createElement('old_rate', $old_rate);
    $curr_node = $dom->createElement('curr');

    // append elements to root
    $root->appendChild($at_node);
    $root->appendChild($rate_node);
    $root->appendChild($old_rate_node);
    $root->appendChild($curr_node);

    // create child nodes with appropriate code, name and loc values
    $child_node_code = $dom->createElement('code', $cur_to_update[0]->code);
    $curr_node->appendChild($child_node_code);
    $child_node_name = $dom->createElement('name',$cur_to_update[0]->curr);
    $curr_node->appendChild($child_node_name);
    $child_node_loc = $dom->createElement('loc', $cur_to_update[0]->loc);
    $curr_node->appendChild($child_node_loc);

    $dom->appendChild($root);

    // display xml output
    echo '<pre>' . $dom->saveXML() . '</pre>';
}

/* 
POST functionality
get the currency rate and value for a new currency and 
insert the new record into the rates.xml file */
if($action == 'post'){
  /* Error Handling
  ensure provided currency code is available for update - Error 2200 
  load elements of currency from rates.xml */
  $currency = $xml->xpath("./currency[code = '$cur']");
  // generate error if $currency is empty - currency not found
  if(empty($currency)){
    generateErrorm('2200');
    exit();
  }

  // access currency from rates.xml to update
  $cur_to_insert = $xml->xpath("/rates/currency[code='$cur']");

  // generate call to API  
  $cur_rate = getRate($cur);

  /* update currency rate 
  set the value of live attribute to 1 - active currency */
  $cur_to_insert[0]['rate'] = $cur_rate;
  $cur_to_insert[0]['live'] = '1';
  $xml->asXMl('../rates.xml');

  // generate response xml 
  $dom = new DOMDocument();
  $dom->encoding = "UTF-8";
  $dom->xmlVersion = "1.0";
  $dom->formatOutput = true;  

  /* create root element and type attribute with post as value
  set type attribute for root element */
  $root = $dom->createElement('action');
  $type_attr = new DOMAttr('type', 'post');
  $root->setAttributeNode($type_attr);

  // create xml elements with ts and rate values
  $at_node = $dom->createElement('at', date("d M Y H:i"));
  $rate_node = $dom->createElement('rate', $cur_to_insert[0]['rate']);
  $curr_node = $dom->createElement('curr');

  // append elements to root
  $root->appendChild($at_node);
  $root->appendChild($rate_node);
  $root->appendChild($curr_node);

  // create child nodes with appropriate code, name and loc values
  $child_node_code = $dom->createElement('code', $cur_to_insert[0]->code);
  $curr_node->appendChild($child_node_code);
  $child_node_name = $dom->createElement('name',$cur_to_insert[0]->curr);
  $curr_node->appendChild($child_node_name);
  $child_node_loc = $dom->createElement('loc', $cur_to_insert[0]->loc);
  $curr_node->appendChild($child_node_loc);

  $dom->appendChild($root);

  // display xml output
  echo '<pre>' . $dom->saveXML() . '</pre>';
}

/* 
DELETE functionality
make currency unavailable to the service */
if($action == 'del'){
  // access currency from rates.xml to update
  $cur_to_delete = $xml->xpath("/rates/currency[code='$cur']");

  // update live attribute of the currency to 0 - inactive currency
  $cur_to_delete[0]['live'] = '0';
  $xml->asXMl('../rates.xml');

  // generate response xml
  $dom = new DOMDocument();
  $dom->encoding = "UTF-8";
  $dom->xmlVersion = "1.0";
  $dom->formatOutput = true;  

  /* create root element and type attribute with del as value
  set type attribute for root element */
  $root = $dom->createElement('action');
  $type_attr = new DOMAttr('type', 'del');
  $root->setAttributeNode($type_attr);

  // create xml elements with ts and code values
  $at_node = $dom->createElement('at', date("d M Y H:i"));
  $code_node = $dom->createElement('code', $cur_to_delete[0]->code);

  // append elements to root
  $root->appendChild($at_node);
  $root->appendChild($code_node);

  $dom->appendChild($root);

  // display xml output
  echo '<pre>' . $dom->saveXML() . '</pre>';
}

/*

error in service 2500

check if I can use anything from config

Check if all the appropriate error mesagges generated
Does DEL method need additional error handling? ERROR 2200? if yes, check if can be generic outside of if statmenet

delete pre tags of output, sort heading issue

reset rates.xml before submission

why xml prolog is commented out when inspect

NULL value for attribute if it is not set

*/
?>