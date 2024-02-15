# Shopware 6 redirecter plugin

| Version   | Changes                                                                                | Shopware Version   |
|---------	|----------------------------------------------------------------------------------------|--------------------|
| 1.0.0     | Initial release                                                                        | <= 6.4             |
| 1.0.1     | Fixed redirect resolving within multiple seo urls                                      | <= 6.4             |
| 1.0.2     | Fixed redirect resolving within not existing source urls                               | <= 6.4             |
| 1.0.3     | Allow redirect deleted urls                                                            | <= 6.4             |
| 1.0.4     | Exclude store api from redirects                                                       | <= 6.4             |
| 1.0.5     | Fix redirects with query params                                                        | <= 6.4             |
| 1.1.0     | Import, export and disabling of redirects                                              | <= 6.4             |
| 1.1.1     | Fixed csv file identification when importing<br>Fixed endless redirecting within multiple seo urls to the same product    | <= 6.4             |
| 1.1.2     | Fixed redirecting from old seo url                                                     | <= 6.4             |
| 1.1.3     | Fixed redirecting in shopware versions below 6.4.0.0                                   | <= 6.4             |
| 1.1.4     | Fixed endless redirecting when source- and target-url have only different capitalisation   | <= 6.4             |
| 1.1.5     | Fixed redirecting from an absolute URL with virtual path                               | <= 6.4             |
| 1.2.0     | Added option "Ignore Query Parameters"                                                 | <= 6.4             |
| 2.0.0     | Changed to Shopware v6.5<br>Fixed error in the log on successful redirect              | **6.5**            |
| 2.1.0     | Added possibility to transfer query parameters to the target url<br>Fixed logical mistake in the import file validation | 6.5                |
| 2.2.0 | Added plugin configuration for support of special chars (like umlauts) in the Source URL<br>Fixed error on creating/editing a redirect with an empty Source-/Target-URL | 6.5                |
| 2.3.0 | Added sales channel selection for each redirect                                            | 6.5                |
| 3.0.0 | Changed to Shopware 6.6                                                                    | **6.6**            |

> [!Important]\
> Version 2.0.0 is no longer compatible with Shopware 6.4 or below!\
> Version 3.0.0 is no longer compatible with Shopware 6.5 or below!

# Installation

## Zip Installation package for the Shopware Plugin Manager

* Download the [latest plugin version](https://github.com/scope01-GmbH/ScopPlatformRedirecter/releases/latest/) (
  e.g. `ScopPlatformRedirecter-1.0.4.zip`)
* Upload and install plugin using Plugin Manager

## Git Version

* Checkout Plugin in `/custom/plugins/ScopPlatformRedirecter`
* Install the Plugin with the Plugin Manager

## Plugin Features:

* manage redirects in the administration
* redirect to the target in frontend
