<?php
session_start();
// include config data
include('../config.php'); 

function getRate($symbol){

    $curl = curl_init();
  
    $base = constant("BASE");
  
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
  
    $data = json_decode(curl_exec($curl));
    curl_close($curl);

    // Error 2300 - No rate listed for this currency
    $response = get_object_vars($data);
    if(array_key_exists('error', $response) && $response['error']->code == '202'){
      generateErrorm('2300');;
      exit();
    }

    $rate = $data->rates->$symbol;
    return $rate;
}

function generateErrorm($err_code){

  // generate xml error output
  $dom_err = new DOMDocument();
  $dom_err->encoding = "UTF-8";
  $dom_err->xmlVersion = "1.0";
  $dom_err->formatOutput = true;

  $err_root = $dom_err->createElement('action');
  $err_attr = new DOMAttr('type', 'tttt');
  $err_root->setAttributeNode($err_attr);

  $err_node = $dom_err->createElement('error');  
  $err_root->appendChild($err_node);

  $child_node_code = $dom_err->createElement('code', $err_code);  
  $child_node_msg = $dom_err->createElement('msg', ERRMESSAGES2[$err_code]); 

  $err_node->appendChild($child_node_code);
  $err_node->appendChild($child_node_msg);

  $dom_err->appendChild($err_root);

  // display xml output
  echo '<pre>' . $dom_err->saveXML() . '</pre>';

}


// ensure base file exist - if true, get values
if (file_exists('../rates.xml')){
    extract($_GET);
}
else{
  exit('Base file not found');
}

// load rates.xml with simpleXML
$xml = simplexml_load_file("../rates.xml") or die ("Error: Cannot load rates file");

// ERROR HANDLING

if (empty($action) || !in_array($action, ACTIONS)){
    generateErrorm('2000');;
    exit();
}

if (empty($cur) || !ctype_upper($cur) || strlen($cur) != 3){
    generateErrorm('2100');
    exit();
}

if ($cur == BASE){
    generateErrorm('2400');
    exit();
}

// PUT
/*generate a call to the external rate service and 
update the rate value for the specific currency in the xml file
*/
if($action == 'put'){

    // ERROR 2200 - check if the currency in rates.xml and live 
    $element = $xml->xpath("./currency[code = '$cur']");
    if(empty($element)){
      generateErrorm('2200');
      exit();
    } else{
      $attr = $element[0]->attributes()->live; 
      if($attr == '0'){
        generateErrorm('2200');
        exit();
      }
    }

    // access currency 
    $cur_to_update = $xml->xpath("/rates/currency[code='$cur']");

    // store old rate 
    $old_rate = (string) $cur_to_update[0]['rate'];

    // generate call to API  
    $new_rate = getRate($cur);
    
    // update rate attribute
    $cur_to_update[0]['rate'] = $new_rate;
    $xml->asXMl('../rate.xml');

    // generate response xml
    $dom = new DOMDocument();
    $dom->encoding = "UTF-8";
    $dom->xmlVersion = "1.0";
    $dom->formatOutput = true;

    $root = $dom->createElement('action');
    $type_attr = new DOMAttr('type', 'put');
    $root->setAttributeNode($type_attr);

    $at_node = $dom->createElement('at', date("d M Y H:i"));
    $rate_node = $dom->createElement('rate', $cur_to_update[0]['rate']);
    $old_rate_node = $dom->createElement('old_rate', $old_rate);
    $curr_node = $dom->createElement('curr');

    $root->appendChild($at_node);
    $root->appendChild($rate_node);
    $root->appendChild($old_rate_node);
    $root->appendChild($curr_node);

    $child_node_code = $dom->createElement('code', $cur_to_update[0]->code);
    $curr_node->appendChild($child_node_code);
    $child_node_name = $dom->createElement('name',$cur_to_update[0]->curr);
    $curr_node->appendChild($child_node_name);
    $child_node_loc = $dom->createElement('loc', $cur_to_update[0]->loc);
    $curr_node->appendChild($child_node_loc);

    $dom->appendChild($root);

    echo '<pre>' . $dom->saveXML() . '</pre>';

}

// POST 
/* get the currency rate and value for a new currency and insert the
new record in the xml
*/

if($action == 'post'){

  // ERROR 2200 - check if the currency in rates.xml and live 
  $element = $xml->xpath("./currency[code = '$cur']");
  if(empty($element)){
    generateErrorm('2200');
    exit();
  }

  // get currency rate and value for the currency
  $cur_to_insert = $xml->xpath("/rates/currency[code='$cur']");

  // generate call to API  
  $cur_rate = getRate($cur);

  // update rates.xml + set attribute to live
  $cur_to_insert[0]['rate'] = $cur_rate;
  $cur_to_insert[0]['live'] = '1';
  $xml->asXMl('../rate.xml');

  // generate response xml 

  $dom = new DOMDocument();
  $dom->encoding = "UTF-8";
  $dom->xmlVersion = "1.0";
  $dom->formatOutput = true;  

  $root = $dom->createElement('action');
  $type_attr = new DOMAttr('type', 'post');
  $root->setAttributeNode($type_attr);

  $at_node = $dom->createElement('at', date("d M Y H:i"));
  $rate_node = $dom->createElement('rate', $cur_to_insert[0]['rate']);
  $curr_node = $dom->createElement('curr');

  $root->appendChild($at_node);
  $root->appendChild($rate_node);
  $root->appendChild($curr_node);

  $child_node_code = $dom->createElement('code', $cur_to_insert[0]->code);
  $curr_node->appendChild($child_node_code);
  $child_node_name = $dom->createElement('name',$cur_to_insert[0]->curr);
  $curr_node->appendChild($child_node_name);
  $child_node_loc = $dom->createElement('loc', $cur_to_insert[0]->loc);
  $curr_node->appendChild($child_node_loc);

  $dom->appendChild($root);

  echo '<pre>' . $dom->saveXML() . '</pre>';

}

// DELETE
/* Make the currency unavailable to the service - change live attr*/

if($action == 'del'){

  // access currency
  $cur_to_delete = $xml->xpath("/rates/currency[code='$cur']");

  // update live attribute to 0
  $cur_to_delete[0]['live'] = '0';

  $xml->asXMl('../rate.xml');

  // generate response xml
  $dom = new DOMDocument();
  $dom->encoding = "UTF-8";
  $dom->xmlVersion = "1.0";
  $dom->formatOutput = true;  

  $root = $dom->createElement('action');
  $type_attr = new DOMAttr('type', 'del');
  $root->setAttributeNode($type_attr);

  $at_node = $dom->createElement('at', date("d M Y H:i"));
  $code_node = $dom->createElement('code', $cur_to_delete[0]->code);
  
  $root->appendChild($at_node);
  $root->appendChild($code_node);

  $dom->appendChild($root);

  echo '<pre>' . $dom->saveXML() . '</pre>';

}

/*

delete rate.xml and change back to rates.xml
error in service
set xml heading for response
add generating errors
check if I can use anything from config
tidy up code

Check if all the appropriate error mesagges generated
Does DEL method need additional error handling? ERROR 2200? if yes, check if can be generic outside of if statmenet


*/

?>