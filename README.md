# JsonEncoderBundle

A bundle that make the code of the [JsonEncoder component PR](https://github.com/symfony/symfony/pull/51718) 
available.

## Installation

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require mtarld/json-encoder-bundle
```

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require mtarld/json-encoder-bundle
```

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Mtarld\JsonEncoderBundle\JsonEncoderBundle::class => ['all' => true],
];
```
