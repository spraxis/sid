# SID: Console Tools for Magento 2
# ¯\\_(ツ)_/¯

## Installation

- Go to your Magento root folder and run the following command:
```
$ git clone git@github.com:spraxis/sid.git app/code/Sebas
```
- Enter your Company name in the following file:
```
app/code/Sebas/ConsoleTools/Model/Sid.php
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

### Removed the specific cache to regenerate the CSS styles of a particular theme

```
$ bin/magento sid clean:styles --t="ThemeName"
```
Same as:
```
$ bin/magento sid c:s --t="ThemeName"
```

### Removed the specific cache to regenerate the layouts

```
$ bin/magento sid clean:layouts
```
Same as:
```
$ bin/magento sid c:l
```

### Removed the specific cache to regenerate the templates

```
$ bin/magento sid clean:templates
```
Same as:
```
$ bin/magento sid c:t
```

###  Get the path to our theme in order to override a vendor template

```
$ bin/magento sid override:template --t="ThemeName" --f="vendor/..."
```
Same as:
```
$ bin/magento sid o:t --t="ThemeName" --f="vendor/..."
```
Example:
```
$ bin/magento sid o:t --t="MyTheme" --f="vendor/magento/module-catalog/view/base/templates/product/price/amount/default.phtml"
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

### Enable the Template Hints for a given theme
<sub>
It saves you from going to Stores > Configuration, then change the Scope and then going to Advanced > Developer > Debug > Enabled Template Path Hints for Storefront > Yes
</sub>

```
$ bin/magento sid hint:on --t="ThemeName"
```
Same as:
```
$ bin/magento sid h:on --t="ThemeName"
```

### Disable the Template Hints for a given theme
<sub>
It saves you from going to Stores > Configuration, then change the Scope and then going to Advanced > Developer > Debug > Enabled Template Path Hints for Storefront > No
</sub>

```
$ bin/magento sid hint:off --t="ThemeName"
```
Same as:
```
$ bin/magento sid h:off --t="ThemeName"
```

# ¯\\_(ツ)_/¯

## Badges

![](https://img.shields.io/badge/license-MIT-blue.svg)
![](https://img.shields.io/badge/status-stable-green.svg)

