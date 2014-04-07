# TicketparkExpiringUrlBundle

This Symfony2 bundles creates urls with expiration hashes. This allows for an url to become invalid after a certain time.

### Example:<br>
* Url pattern:<br>`/some/url/{expirationHash}/{id}`
* Generated url:<br>
`/some/url/2014-04-03T10:41:40+02:00.eaf378321b86d7ab2edb320be1be48672eb107562a3c8cebd3bc804620e1f4fe/123`

## Installation

Add TicketparkExcelBundle in your composer.json:

```js
{
    "require": {
        "ticketpark/expiring-url-bundle": "0.1"
    }
}
```

Now tell composer to download the bundle by running the command:

``` bash
$ php composer.phar update ticketpark/expiring-url-bundle
```

Enable the bundles in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Ticketpark\ExpiringUrlBundle\TicketparkExpiringUrlBundle()
    );
}
```

## Configuration
By default no configuration is required. You can however override some config settings:

``` yml
# app/config/config.yml

ticketpark_expiring_url:
    ttl: 10 # Default time-to-live of urls in minutes
    route_parameter: 'expirationHash' # Parameter for expiration hash to be used in routes
```

## Usage
### SImple usage
Simply add the expiration hash parameter to routes which should be expirable.
``` yml
# Acme/Bundle/Resources/config/routing.yml:

  expiring_route:
    pattern: /some/url/{expirationHash}/{id}
    defaults: { _controller: AcmeBundle:AcmeController:someAction }
```

You will not have to take care of the expiration hash parameter when creating routes. This will be done automatically.

Example:

```php
<?php

namespace Acme\Bundle\AcmeBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AcmeController extends Controller
{
    public function fooAction()
    {
        //no need to add the 'expirationHash' parameter when generating urls
        $url = $this->get('router')->generate('expiring_route',
            array('id' => 123)
        );

        // ...
    }
}
```

### Advanced usage
The routing definition can be extended with two optional options:

``` yml
# Acme/Bundle/Resources/config/routing.yml:

  expiring_route:
    pattern: /some/url/{expirationHash}/{id}
    defaults: { _controller: AcmeBundle:AcmeController:someAction }
    options:
      expiring_url_identifier: 'id'
      expiring_url_ttl: 30
```

* `expiring_url_identifier`<br>Another url parameter which will be used to create a more specific expiration hash. Imagine you create an url like `/some/url/{expirationHash}/123`. Without adding the identifier, the hash within this url would also allow to access `/some/url/{expirationHash}/456` within the permitted time frame. Adding the `expiring_url_identifier` option ensures that only `/some/url/{expirationHash}/123` will be accessible (and any other url using the same identifier) because a more specific expiration hash will be created.
* `expiring_url_ttl`<br>The time-to-live of this url in minutes. Overrides the default time-to-live.

## License

This bundle is under the MIT license. See the complete license in the bundle:

    Resources/meta/LICENSE
