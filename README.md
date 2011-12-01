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

DESCRIPTION:
------------

I needed a couple of additional commands added to the CloudStack API for a proof
of concept private cloud I had been working with. I needed the ability to read
the userdata associated with a virtual machine instance via the API. 
Additionally, I wanted a way to launch a VM without having a specify a zoneid,
templateid, offering id, etc., but have it bundled up and associated with a
name. For example, if I want to launch a new machine of type 'foo', I don't
to have to specify zoneid, offering id, etc., I just want to launch a
bundle named 'foo' and have it deploy a machine with whatever 'foo' maps
to.

A total of three commands have been added to the API:

 * getUserData(id) - returns the userdata associated with the given instance id
 * listBundles() - returns a list of the bundles offered
 * deployBundle(bundle) - Launches a bundle of the specified name 

It is important to note that I'm not doing any fancy account->VM permissions
checking. This means that anyone with a valid API key/secret combo can read
any userdata for a VM, even one not belonging to them, so don't store anything
sensitive in the userdata. I am checking the signature of each request, so you
must have both a valid API key and a valid API secret.

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
