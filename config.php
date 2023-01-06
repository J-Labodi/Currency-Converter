<?php
// set timezone
date_default_timezone_set('Europe/London');

// define constants - supported parameters, formats, initial live currencies
define('PARAMS', array("from", "to", "amnt", "format"));

define('FORMATS', array("xml", "json"));

define('LIVE', array(
       "AUD", "BRL", "CAD", "CHF", "CNY",
       "DKK", "EUR", "GBP", "HKD", "HUF",
       "INR", "JPY", "MXN", "MYR", "NOK", 
       "NZD", "PHP", "RUB", "SEK", "SGD", 
       "THB", "TRY", "USD", "ZAR"
));

// define constant - error messages
define('ERRMESSAGES', array(
       "1000" => "Required parameter is missing",
       "1100" => "Parameter not recognized",
       "1200" => "Currency type not recognized",
       "1300" => "Currency amount must be a decimal number",
       "1400" => "Format must be xml or json",
       "1500" => "Error in service"
));

// define constants - rates output file, ISO XMl, base rate
define('RATES', 'rates.xml');

define ('ISO_XML', 'https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml');

define('BASE', 'GBP');

// define constant - supported actions of update functionality
define('ACTIONS', array("put", "post", "del"));

// define constant - error messages of update functionality
define('ERRMESSAGES2', array(
       "2000" => "Action not recognized or is missing",
       "2100" => "Currency code in wrong format or is missing",
       "2200" => "Currency code not found for update",
       "2300" => "No rate listed for this currency",
       "2400" => "Cannot update base currency",
       "2500" => "Error in service"
));
?>