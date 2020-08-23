# Features
* Translate frontname routes handled by the standard router for each store views<br>
Examples:

```
/customer/account/create -> /client/compte/creer in french store view
/customer/account/create -> /cliente/cuenta/crear in spanish store view
/customer/account/create -> /kunde/konto/erstellen in german store view
```
* Easy translation using translation files and fallback mechanism
* Third party extensions out of the box
* Old URLs are still working (even if they are translated)
* Enable/Disable module by store view
* Use param to not translate a specific url
* Store switcher supported

# Configuration
You can enable/disable the extension in configuration:
* Stores / Configuration / General / Web / Url Options / Tranlate Urls **(default: Yes)**

# Usage
**It's strongly recommended to install and test this extension in a development environment.**

Url translations are using the same mechanisms as native Magento wording translation (except database):

* CSV files
* Fallback mechanism (see <a href="http://www.ajourquin.com/magento2/translations-loading-order/" target="_blank" rel="noopener noreferrer">Translations loading order</a>)

CSV files must be located under a '**routes**' directory and named as the associated store view locale (ex: fr_CA.csv)

You can translate urls from different sources:

* **module** - /app/code/Vendor/Module/i18n/routes/fr_CA.csv
* **theme** - /app/design/Vendor/Theme/i18n/routes/fr_CA.csv
* **dictionnary** - /i18n/vendor/dictionnary/routes/fr_CA.csv

File content corresponds to the translation of each part of module url; moduleFrontName/controllerName/actionName

Let's say you want to translate "/customer/account/create" to "/client/compte/creer" for your french store view having fr_CA locale.<br>The content of your **fr_CA.csv** file will be:

```
customer,client
account,compte
create,creer
```

If you dont want to translate an url, you can add the parameter '**_notranslate**' to true when building url. The same way as adding '<strong>_scope</strong>' or '<strong>_secure</strong>' params

```
this->getUrl('customer/account/login', ['no_translate' => true]);
```

# How it works

An UrlModifier (**Magento\Framework\Url\ModifierComposite**) is responsible to parse url and translate each parts using a translation file. Only urls matched by the standard router are translated. Typically frontend/routes.xml files

When the front controller comes to standard router to check if an url could match, the url is first translated back to its original value. So the default request flow is pursued.

soap/*, rest/* and section/load/* urls are ignored

# Requirements

* PHP >= 7.2.0
* Magento 2.3.x

# Authors

* **Aurélien Jourquin**

# Releases notes
* **1.0.0**
    * Initial release
