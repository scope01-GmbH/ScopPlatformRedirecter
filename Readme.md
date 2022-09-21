# Shopware 6 redirecter plugin

| Version    | Changes                                                                             | Availability   |
|---------	|------------------------------------------------------------------------------------------- |----------------|
| 1.0.0     | Initial release                                                                          | Github         |
| 1.0.1     | Fixed redirect resolving within multiple seo urls                                                                       | Github         |
| 1.0.2     | Fixed redirect resolving within not existing source urls                                                                     | Github         |
| 1.0.3     | Allow redirect deleted urls                                                                   | Github         |
| 1.0.4     | Exclude store api from redirects                                                                   | Github         |
| 1.0.5     | Fix redirects with query params                                                                   | Github         |
| 1.1.0     | Import, export and disabling of redirects                                                           | Github         |
| 1.1.1     | Fixed csv file identification when importing<br>Fixed endless redirecting within multiple seo urls to the same product    | Github         |
| 1.1.2     | Fixed redirecting from old seo url                                              | Github         |
| 1.1.3     | Fixed redirecting in shopware versions below 6.4.0.0                                              | Github         |
| 1.1.4     | Fixed endless redirecting when source- and target-url have only different capitalisation             | Github         |
| 1.1.5     | Fixed redirecting from an absolute URL with virtual path             | Github         |
| 1.2.0     | Added option "Ignore Query Parameters"                               | Github         |

# Installation

## Zip Installation package for the Shopware Plugin Manager

* Download the [latest plugin version](https://github.com/scope01-GmbH/ScopPlatformRedirecter/releases/latest/) (
  e.g. `ScopPlatformRedirecter-1.0.4.zip`)
* Upload and install plugin using Plugin Manager

## Git Version

* Checkout Plugin in `/custom/plugins/ScopPlatformRedirecter`
* Install the Plugin with the Plugin Manager

## Install with composer

* Change to your root Installation of shopware
* Run command `composer require scop/scopplatformredirecter` and install and active plugin with Plugin Manager

## Plugin Features:

* manage redirects in the administration
* redirect to the target in frontend
