# SID: Console tools for Magento 2

## New v.0.0.2

- Added clean:all (bin/magento c:a)
- Added link to the PDP on the Admin's product page

## Installation

- Go to your Magento root folder and run the following command:
```
$ git clone git@github.com:spraxis/sid.git app/code/Sebas/Sid
```
- Enter your M2 info in the following file:
```
app/code/Sebas/ConsoleTools/Model/Sid.php
```
- Enable the Sid module:
```
$ bin/magento module:enable Sebas_Sid
$ bin/magento s:up
```
- You are good to go!

<sub>
Note: remember to always develop in Developer mode
</sub>


## Commands

### List all the modules of your company (with its code version)

```
$ bin/magento sid modules:company
```
Same as:
```
$ bin/magento sid m:c
```

### Remove all cache (everything inside /var and /pub/static)

```
$ bin/magento sid clean:all
```
Same as:
```
$ bin/magento sid c:a
```

### Remove the specific cache to regenerate the CSS styles

```
$ bin/magento sid clean:styles --t="ThemeName"
```
The themename is optional. If none is specified, the one set in Model.php will be used.
Same as:
```
$ bin/magento sid c:s
```

### Remove the specific cache to regenerate the layouts

```
$ bin/magento sid clean:layouts
```
Same as:
```
$ bin/magento sid c:l
```

### Remove the specific cache to regenerate the templates

```
$ bin/magento sid clean:templates
```
Same as:
```
$ bin/magento sid c:t
```

###  Get the path to our theme in order to override a vendor template

```
$ bin/magento sid override:template --t="ThemeName" --t="vendor/..."
```
Same as:
```
$ bin/magento sid o:t --t="vendor/..."
```
Example:
```
$ bin/magento sid o:t --t="vendor/magento/module-catalog/view/base/templates/product/price/amount/default.phtml"
```

### Downgrade the version of a database module to the one on our code
<sub>
Useful when we move to an older branch with out-of-date modules
</sub>

```
$ bin/magento sid module:downgrade --m="ModuleName"
```
Same as:
```
$ bin/magento sid m:d --m="ModuleName"
```
Example (for Sebas_MyLocation):
```
$ bin/magento sid m:d --m="MyLocation"
```

### Enable the Template Hints
<sub>
It saves you from going to Stores > Configuration, then change the Scope and then going to Advanced > Developer > Debug > Enabled Template Path Hints for Storefront > Yes
</sub>

```
$ bin/magento sid hints:on --t="ThemeName"
```
The themename is optional. If none is specified, the one set in Model.php will be used.
Same as:
```
$ bin/magento sid h:on
```

### Disable the Template Hints
<sub>
It saves you from going to Stores > Configuration, then change the Scope and then going to Advanced > Developer > Debug > Enabled Template Path Hints for Storefront > No
</sub>

```
$ bin/magento sid hints:off --t="ThemeName"
```
The themename is optional. If none is specified, the one set in Model.php will be used.
Same as:
```
$ bin/magento sid h:off
```

# ¯\\\_(ツ)\_/¯

## Badges

![](https://img.shields.io/badge/license-MIT-blue.svg)
![](https://img.shields.io/badge/status-stable-green.svg)

