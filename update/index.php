<?php
session_start();
date_default_timezone_set('Europe/London');

// define constants - supported actions
define('ACTIONS', array("put", "post", "del"));

// define constants - error messages
define('ERRMESSAGES', array(
"2000" => "Action not recognized or is missing",
"2100" => "Currency code in wrong format or is missing",
"2200" => "Currency code not found for update",
"2300" => "No rate listed for this currency",
"2400" => "Cannot update base currency",
"2500" => "Error in service"
));

function getRate($symbol){

    $curl = curl_init();
  
    $base = "GBP";
  
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


// ERROR HANDLING

if (empty($action) || !in_array($action, ACTIONS)){
    echo 'Error 2000 - Action not recognized or is missing';
    exit();
}

if (empty($cur) || !ctype_upper($cur) || strlen($cur) != 3){
    echo 'ERROR 2100 - Currency code in wrong format or is missing';
    exit();
}


// load rates.xml with simpleXML
$xml = simplexml_load_file("../rates.xml") or die ("Error: Cannot create object");

// PUT
/*generate a call to the external rate sservice and 
update the rate value for the specific currency in the xml file
*/
if($action == 'put'){
    // generate call to API - update rate and live attr in rates.xml
    $cur_to_update = $xml->xpath("/rates/currency[code='$cur']");
    $cur_to_update[0]['rate'] = getRate($cur);
    $cur_to_update[0]['live'] = '1';
    $xml->asXMl('../rates.xml');
    
    // generate response xml

}

// POST 
/* get the currency rate and value for a new currency an insert the
new record in the xml
*/






// DELETE
/* Make the currency unavailable to the service - 1200*/
// add currency to blacklist 


?>