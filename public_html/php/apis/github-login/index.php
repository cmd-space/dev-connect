<?php
use Edu\Cnm\DevConnect;

require_once dirname(__DIR__, 2) . "/classes/autoload.php";
require_once dirname(__DIR__, 2) . "/lib/xsrf.php";
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");

/**  ___________________________________

                    Light PHP wrapper for the OAuth 2.0
___________________________________


AUTHOR & CONTACT
================

Charron Pierrick
- pierrick@webstart.fr

Berejeb Anis
- anis.berejeb@gmail.com


DOCUMENTATION & DOWNLOAD
========================

Latest version is available on github at :
    - https://github.com/adoy/PHP-OAuth2

Documentation can be found on :
    - https://github.com/adoy/PHP-OAuth2


LICENSE
=======

This Code is released under the GNU LGPL

Please do not change the header of the file(s).

This library is free software; you can redistribute it and/or modify it
under the terms of the GNU Lesser General Public License as published
by the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This library is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.

See the GNU Lesser General Public License for more details.  **/