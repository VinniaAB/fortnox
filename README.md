# Fortnox API wrapper in PHP
## Installation
```
composer install
```
## Usage
Aquire access token & client secret from fortnox. See http://developer.fortnox.se/documentation/general/authentication/

```php
$accessToken = 'my-token';
$clientSecret = 'my-secret';

$guzzle = new \GuzzleHttp\Client();
$client = new \Vinnia\Fortnox\Client($guzzle, $accessToken, $clientSecret);

$customers = $client->getCustomers();
$first = $customers[0];
$first['Name'] = 'Some Other Name';
$client->updateCustomer($first['CustomerNumber'], $first);

```
