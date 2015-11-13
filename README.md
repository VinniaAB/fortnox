# Fortnox API wrapper in PHP
## Usage
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
