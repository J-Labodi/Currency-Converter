<?php
session_start();
extract($_GET);

/*
Get data from query string 
$from = $_GET['from'];
$to = $_GET['to'];
$amnt = $_GET['amnt'];
$format = $_GET['format'];
*/


/* TODO if format is missing, system should use xml as default 
TODO if there is more than one error in request - parameter is missing and 
parameter not recognized - error message should report on the first error 
*/


// Fixer API
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.apilayer.com/fixer/convert?to=" . $to . "&from=" . $from . "&amount=" . $amnt,
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
echo $response;

$array = json_decode($response, true);

foreach($array as $key => $value) {
  echo $key . " => " . $value . "<br>";

}