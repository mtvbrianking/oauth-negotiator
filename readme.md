## OAuth Negotiator.

[![Total Downloads](https://poser.pugx.org/bmatovu/oauth-negotiator/downloads)](https://packagist.org/packages/bmatovu/oauth-negotiator)
[![Latest Stable Version](https://poser.pugx.org/bmatovu/oauth-negotiator/v/stable)](https://packagist.org/packages/bmatovu/oauth-negotiator)
[![License](https://poser.pugx.org/bmatovu/oauth-negotiator/license)](https://github.com/mtvbrianking/oauth-negotiator/blob/master/license.txt)
[![Build Status](https://travis-ci.org/mtvbrianking/oauth-negotiator.svg?branch=master)](https://travis-ci.org/mtvbrianking/oauth-negotiator)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mtvbrianking/oauth-negotiator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mtvbrianking/oauth-negotiator/?branch=master)
[![StyleCI](https://github.styleci.io/repos/177835567/shield?branch=master)](https://github.styleci.io/repos/177835567)

OAuth negotiator is a PHP Guzzle HTTP v6.X Client [Middleware](http://docs.guzzlephp.org/en/stable/handlers-and-middleware.html) that will help you acquire, and refresh your access tokens automatically per application request to the [OAuth2](https://tools.ietf.org/html/rfc6749) server so you don't have to worry about non-existent or expired access tokens.

This package works by tapping into each request. 

- First, it checks if the request has an authorization header; if present, proceed to execute the request.
- Else, if the request has no set authorization header, the available access token in storage is then added  as the request authorization header.
- If no access token is present in storage, it shall request for a new access token from the OAuth server using the main grant type specified.
- If there's an existing access token in storage, but it's expired, and a refresh token grant was specified, it shall try to refresh the expired access token using the available refresh token.

**Grant types**:
The package supports four grant types out of box, that is; Client Credentials, Refresh Token, Password, and the Authorization Code grant type. 

You can implement your own custom grant type.

**Token storage**:
The package also defaults to a file based persistent token storage, but you can still implement your custom persistent storage, say a session based token storage.

### [Installation](https://packagist.org/packages/bmatovu/oauth-negotiator)

The package can be installed via composer.

`composer require bmatovu/oauth-negotiator`

### Usage

TODO...

**Source code [documentation](https://mtvbrianking.github.io/oauth-negotiator/)**
