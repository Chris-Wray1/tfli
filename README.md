<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Built Using Laravel

Laravel is one of the best known MVC PHP frameworks and lends itself to quick app production.  I have utilised this to build an API wrapper and GUI for the TFLI Tech Test.  I have utilised one of their suggested API options - namely the Open Food Facts API (V2).

Due to this being a test, I have not spent excessive amounts of time producing this - so there are parts that need improving.

## Processes Implemented
* Basic user auth included for the GUI
* Basic API calls to return a product list, with pagination
* Basic API call to pull back dtailed product details for a single produt using the barcode.
* Bootstarpped GUI for using these API calls
    * Simplified functionality for making calls
    * Split screen to show the product list and also the product details
    * Filtering options (type, group, name and barcode)

## Known issues
* Auth later for the API
* The string filtering is not finalised
* The Produt list includes the barcode and this needs a link adding (to prevent the copy & paste that is currently needed)
* Update/Edit functions needed for product details (OpenFoodFacts Auth available)

