# RESTful Currency Converter Microservice

The aim of the project was to build a Restful API based microservice for currency conversion with full CRUD functionaility as well as a client interface to demonstrate & test the application. 

The currency converter microservice has been built with HTML, CSS, PHP and JavaScript, making use of XML, XPath and Ajax. The service utilises Fixer API to ensure exchange rates are up-to-date. This microservice updates the exchange rates periodically (every 12 hours) and store the pulled data in the application's XML dataset. The currency converter microservice provides conversion through HTTP GET requests, returning conversion data in XML or JSON formats. 

The service may be acessed locally through sending request encoded as follows:

`http://localhost/atwd1/assignment/?from=GBP&to=JPY&amnt=10.35&format=xml`

## Project Description

Initially, the currency converter provided currency conversion function to & from the following (24) ISO 4217 currencies:


Code  | Currency
:----------|:-------------
AUD	| Australian Dollar
BRL	| Brazlian Real
CAD |	Canada Dollar
CHF |	Swiss Franc
CNY |	Chinese Yuan Renminbi
DKK |	Danish Krone
EUR |	Euro
GBP |	Pound Sterling (reference currency)
HKD |	Hong Kong Dollar
HUF |	Hungarian Forint
INR |	Indian Rupee
JPY |	Japanese Yen
MXN |	Mexican Peso
MYR |	Malaysian Ringgit
NOK |	Norwegian Krone
NZD |	New Zealand Dollar
PHP |	Philippine Peso
RUB |	Russian Ruble
SEK |	Swedish Krona
SGD |	Singapore Dollar
THB |	Thai Baht
TRY |	Turkish Lira
USD |	US Dollar
ZAR |	South African Rand


The following request provides accessability to the microservice, supplying required paramaters such as conversion from & to, amount of conversion and format. 

`http://localhost/atwd1/assignment/?from=GBP&to=JPY&amnt=10.35&format=xml`

#### Request response in XML and JSON format

<img src="https://user-images.githubusercontent.com/79979904/212058329-a8ccfdf3-ecd2-4a63-901c-5760cfd1e66e.jpg" width="600">
<img src="https://user-images.githubusercontent.com/79979904/212058428-aab801e9-01ad-4819-aabe-490b7e58c373.jpg" width="600">


### Error Handling

As there are required prameters to complete the conversion, simple error handling has been implemented to ensure the micorservice receives valid requests only. 
The service returns the following error codes & messages in case the received request is in invalid format:   

Code  | Message
:----------|:-------------
1000 | Required parameter is missing
1100 | Parameter not recognized
1200 | Currency type not recognized
1300 | Currency amount must be a decimal number
1400 | Format must be xml or json
1500 | Error in service

#### Error response 

<img src="https://user-images.githubusercontent.com/79979904/212061360-815be3e4-a3c7-4a24-b333-de247f7c0617.jpg" width="300">

### CRUD Functionality

As an extension of the initial currency conversion microservice, the application has been refactored by implementing full CRUD functionality.

This feature supporting the following requests:

* PUT request generates a call to the external rates service and update the rate value (for a specific currency) in the XML data store.
* POST request gets the currency rate and value (for a new currency) and insert a new record in the XML data store.
* DEL request makes a currency unavaialble to the service (Error 1200).

CRUD functionality may be accessed through the following requests: 

PUT:  `http://localhost/atwd1/assignment/update/?cur=EUR&action=put`

POST: `http://localhost/atwd1/assignment/update/?cur=PKR&action=post`

DEL:  `http://localhost/atwd1/assignment/update/?cur=NZD&action=del`


#### PUT, POST and DEL request responses 

<img src="https://user-images.githubusercontent.com/79979904/212067126-4dc207d5-deb0-49cc-a44c-cdf9cabac418.jpg" width="200">

<img src="https://user-images.githubusercontent.com/79979904/212067161-45651a85-1c32-4667-add7-8f060c7404c4.jpg" width="200">

<img src="https://user-images.githubusercontent.com/79979904/212067188-2cee2ab8-acc4-4c55-b3c1-91cfc2cadd62.jpg" width="200">

### Client Interface

As the last stage of development, the currency converter microservice has been extended with a client interface to demonstrate & test the application. 

This Form Interface allows the user to select the desired action and it also features a dynamic list that updates its content, depending on the selected action.
The logic behind of this behaviour limits the user to choose the appropriate currency for the chosen action. 

* PUT request generates a call to the external rates service and update the rate value (for a specific currency) in the XML data store. <br/> Content of the dynamic list limited to the currencies that are available for update within the XML data store and set to active <br/> (currency "live" attribute set to "1").
* POST request gets the currency rate and value (for a new currency) and insert a new record in the XML data store. <br/> Content of the dynamic list limited to the currencies that are available for update within the XML data store and marked as inactive (currency "live" attribute set to "0").
* DEL request makes a currency inactive. <br/> Content of the dynamic list limited to the currencies that are available for update within the XML data store and set to active <br/> (currency "live" attribute set to "1").

#### Dynamic dropdown list & PUT request response

<img src="https://user-images.githubusercontent.com/79979904/212067960-59e717f7-2ea9-4ef8-b299-536a47c2b7a7.jpg" width="400">             <img src="https://user-images.githubusercontent.com/79979904/212067969-549f8a39-10ab-4d0e-a6ce-8e80d621df33.jpg" width="400">


## Run the application locally
