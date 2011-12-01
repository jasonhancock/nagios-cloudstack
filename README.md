This repository contains nagios plugins for working with CloudStack.

LICENSE:
--------
Copyright (C) 2011 Jason Hancock http://geek.jasonhancock.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses/.

DEPENDENCIES:
-------------
My plugins retrieve information from CloudStack via the API, thus they require
that the CloudStack php client (found at https://github.com/jasonhancock/cloudstack-php-client)
is present.

CONFIGURATION FILE:
-------------------
A simple configuration file is necessary for the plugins to work. This config
file tells the plugins where the CloudStack API is and what credential to use.
An example config file looks like this:

```php
<?php

return array(
    'API_ENDPOINT' => 'http://cloudmgr.example.com:8080/client/api',
    'API_KEY'      => 'your_api_key',
    'API_SECRET'   => 'your_secret_key'
);
```

The configuration file can live anywhere on the filesystem. You tell the plugins
about this location via the -f parameter.
