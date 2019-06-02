# MediaWiki-Phan-Config release history #

## 0.6.1 / 2019-06-01 ##
* Enable enable_class_alias_support (Max Semenik)

## 0.6.0 / 2019-05-13 ##
* Rename tests/phan/stubs in dir list to new location (Umherirrender)
* Upgrade phan to 1.2.7 (Kunal Mehta)
* Upgrade phan to 1.3.2 (Daimona Eaytoy)
* Upgrade phan to 1.3.4 (Umherirrender)

## 0.5.0 / 2019-03-10 ##
* Add RegexChecker, UnusedSuppression and DuplicateExpression plugins
  (Daimona Eaytoy)
* Upgrade Phan to 1.2.6 (Kunal Mehta & Daimona Eaytoy)

## 0.4.0 / 2019-02-23 ##
* Add phan version to composer.json (Kunal Mehta)
* build: Updating mediawiki/minus-x to 0.3.1 (Umherirrender)
* Don't start paths with "./" (Kunal Mehta)
* Drop PHP support pre 7.0 (Reedy)

## 0.3.0 / 2018-06-08 ##
* Include MediaWiki core's `tests/phan/stubs` by default (Kunal Mehta)
* Support MW_INSTALL_PATH (Umherirrender)
* Suppress PhanDeprecated* by default (Kunal Mehta)
* Suppress PhanUnreferencedUseNormal (Umherirrender)

## 0.2.0 / 2018-04-05 ##
* phan should also check an extension's maintenance scripts by default (Kunal Mehta)
* Suppress PhanDeprecatedFunction by default (Kunal Mehta)

## 0.1.0 / 2018-02-03 ##
* Initial release (Kunal Mehta)
