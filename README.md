Active Directory Change Poller Symfony 2+ Bundle
================================================

Integrates [gtt/ad-poller](https://github.com/GlobalTradingTechnologies/ad-poller) into Symfony2+ ecosystem

Installation
============

Bundle should be installed via composer

```
composer require gtt/ad-poller-bundle
```
After that you need to register the bundle inside your application kernel:
```php
public function registerBundles()
{
    $bundles = array(
        // ...
        new Gtt\Bundle\AdPollerBundle\AdPollerBundle(),
    );
}
```

### Database setup
Component requires database to persist poll task state. 
It is possible to generate schema using doctrine console utils:
```php
app/console doctrine:schema:create --dump-sql
```
Also execute [init_data.sql](https://github.com/GlobalTradingTechnologies/ad-poller/blob/master/res/init_data.sql) to fill database initially
