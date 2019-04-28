## OAuth Negotiator.

[![Total Downloads](https://poser.pugx.org/bmatovu/oauth-negotiator/downloads)](https://packagist.org/packages/bmatovu/oauth-negotiator)
[![Latest Stable Version](https://poser.pugx.org/bmatovu/oauth-negotiator/v/stable)](https://packagist.org/packages/bmatovu/oauth-negotiator)
[![License](https://poser.pugx.org/bmatovu/oauth-negotiator/license)](https://github.com/mtvbrianking/oauth-negotiator/blob/master/license.txt)
[![Build Status](https://travis-ci.org/mtvbrianking/oauth-negotiator.svg?branch=master)](https://travis-ci.org/mtvbrianking/oauth-negotiator)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mtvbrianking/oauth-negotiator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mtvbrianking/oauth-negotiator/?branch=master)
[![StyleCI](https://github.styleci.io/repos/177835567/shield?branch=master)](https://github.styleci.io/repos/177835567)

OAuth negotiator is a PHP package that will help you acquire, and refresh your access tokens automatically per request to the OAuth server. So you don't have to worry about non-existent or expires access tokens.

This package works by tapping into each **Guzzle HTTP Client** request via [middleware](http://docs.guzzlephp.org/en/stable/handlers-and-middleware.html). Then checks if the request has an authorization header. 

- If not, then it uses the available access token in storage and add it to the request authorization header.

- If no access token is present, a new access token will be requested for from the server using the main grant type specified.

- If the existing access token is expired, it will request for a new access token using the Refresh token grant if specified.

**Grant types**:
The package supports four grant types out of box, that is; Client Credentials, Refresh Token, Password, and the Authourization Code grant type. But you can implement your own custom grant type.

**Token storage**:
The package also comes with a file base token persistent storage, but you may still implement your custom persistent storage like say a session based storage.

### [Installation](https://packagist.org/packages/bmatovu/oauth-negotiator)

The package can be installed via composer.

`composer require bmatovu/oauth-negotiator`

### Usage

TODO...

**Source code [documentation](https://mtvbrianking.github.io/oauth-negotiator/)**
