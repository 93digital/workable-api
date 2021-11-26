# Nine3 Workable API Class

**_v1.0.1_**

**_Authors:_** _Ahmad Al Asadi & Matt Knight_

An API class for WordPress to fetch vacancies from an account on the Workable recruitment platform. The fetched data is stored as a WordPress transient, allowing quick and easy access to the data.

## Installation

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

## Methods

The class will set up an hourly cron job that makes the API request and stores the response as a transient automatically upon instantiation (assuming WP-Cron has not been disabled!).

However there are a handful of publicly available methods.

### Get vacancies

Returns a full list of the published vacancies on the Workable account. This will return the data saved in the transient. If the transient is empty or not found a new API request will be made.

**Usage:**

```php
/**
 * @return array A multi-dimensional array containing all vacancy data.
 *
 * @see https://workable.readme.io/docs/jobs For available keys in each vacancy. An extra `description` key will also have been added by the class.
 */
$workable_api->get_vacancies();
```

### Fetch vacancies

Forces a new API request to the Workable platform for the latest vacancies data. The transient will be updated with the returned response.

```php
/**
 * @param bool $return [optional] Whether to return the fetched vacancies data.
 *
 * @return array A multi-dimensional array containing all vacancy data.
 *
 * @see https://workable.readme.io/docs/jobs For available keys in each vacancy. An extra `description` key will also have been added by the class.
 */
$workable_api->fetch_vacancies( $return = false );
```

## More Info

Workable API reference: <a href="https://workable.readme.io/" target="_blank">https://workable.readme.io/</a>
