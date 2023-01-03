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

    $rate = $data->rates->$symbol;
    return $rate;
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
    echo 'Error 2000 - Action not recognized or is missing';
    exit();
}

if (empty($cur) || !ctype_upper($cur) || strlen($cur) != 3){
    echo 'ERROR 2100 - Currency code in wrong format or is missing';
    exit();
}

// ERROR 2200 - check if the currency within live currencies of rates 
if($cur){




  
}



// PUT
/*generate a call to the external rate service and 
update the rate value for the specific currency in the xml file
*/
if($action == 'put'){
    // generate call to API - update rate and live attr in rates.xml
    $cur_to_update = $xml->xpath("/rates/currency[code='$cur']");
    // store old rate 
    $old_rate = (string) $cur_to_update[0]['rate'];
    // update rate and live attribute
    $cur_to_update[0]['rate'] = getRate($cur);
    $xml->asXMl('../rates.xml');

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
/* get the currency rate and value for a new currency an insert the
new record in the xml
*/






// DELETE
/* Make the currency unavailable to the service*/

global $blacklist;
$blacklist = array(); 

if($action == 'del'){

  $cur_to_delete = $xml->xpath("/rates/currency[code='$cur']");

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

  array_push($blacklist, $cur);

  echo '<pre>' . $dom->saveXML() . '</pre>';

}
?>