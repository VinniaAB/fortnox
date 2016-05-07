# Fortnox API wrapper in PHP

## Dependencies
- php 7.0+
- composer

## Installation
Add this git as a repository in your composer.json:
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/VinniaAB/fortnox.git"
    }
  ]
}
```

And then require the package:
```shell
composer require vinnia/fortnox --save
```

## Usage
Aquire access token & client secret from fortnox. See http://developer.fortnox.se/documentation/general/authentication/

```php
use Vinnia\Fortnox\Client;
use Vinnia\Fortnox\Util;

$accessToken = 'my-token';
$clientSecret = 'my-secret';

$client = Client::make($accessToken, $clientSecret);

$response = $client->getCustomer(1);
$customer = Util::parseResponse($response)['Customer'];
$customer['Name'] = 'Some Other Name';
$client->updateCustomer($customer['CustomerNumber'], $customer);

```
