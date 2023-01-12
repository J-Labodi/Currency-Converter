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


The following request provides accessability to the microservice, providing required paramaters such as conversion from & to, amount of conversion and format. 

`http://localhost/atwd1/assignment/?from=GBP&to=JPY&amnt=10.35&format=xml`

### Response of conversion in XML and JSON format

![xml_conversion](https://user-images.githubusercontent.com/79979904/212058329-a8ccfdf3-ecd2-4a63-901c-5760cfd1e66e.jpg)
![json_conversion](https://user-images.githubusercontent.com/79979904/212058428-aab801e9-01ad-4819-aabe-490b7e58c373.jpg)


## Error Handling





### CRUD Functionality






### Client Interface






## Run the application locally
