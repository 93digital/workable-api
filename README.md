# Nine3 Workable API Class

**_v1.0_**

**_Authors:_** _Ahmad Al Asadi & Matt Knight_

An API class for WordPress to fetch vacancies from an account on the Workable recruitment platform. The fetched data is stored as a WordPress transient, allowing quick and easy access to the data.

## Installation

Install via composer:

```
$ composer require 93devs/nine3-workable-api:dev-master
```

Then within a theme configuration file (such as `functions.php`) instantiate the class:

```php
/**
 * @param string $subdomain The subdomain part of the URL of a Workable account.
 * @param string $access_token An access token generated within the Workable account.
 */
$workable_api = new Nine3_Workable_Api( $subdomain, $access_token )
```
