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

WHY PHP?
--------
The reason that these plugins are written in php is because I'm lazy. I already
had a php library for the CloudStack API and php is already installed on my
Nagios server because I use pnp4nagios.

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
about this location via the -f parameter. Note that the account you use needs to
be an admin level account.

PNP4NAGIOS
----------
I built and tested these templates on a CentOS 6.0 box running Nagios 3.2.3 and
pnp4nagios 0.6.16 (at the time of this writing, it was in the EPEL testing repo
for EL6).


INSTALLATION:
-------------
Put the php files in the [plugins](https://github.com/jasonhancock/nagios-cloudstack/tree/master/plugins) directory into your nagios plugin directory
(likely either /usr/lib/nagios/plugins or /usr/lib64/nagios/plugins). 

The pnp4nagios templates located in the [pnp4nagios/templates](https://github.com/jasonhancock/nagios-cloudstack/tree/master/pnp4nagios/templates) directory should be
placed into your pnp4nagios/templates directory (this directory was located at 
/usr/share/nagios/html/pnp4nagios/templates on my machine).

NAGIOS CONFIGURATION:
---------------------
The snippet of nagios configuration below assumes that you have a working Nagios
installation and that you have correctly configured pnp4nagios. I don't like to
tie services to specific hosts. Instead, I use hostgroups. The example below 
assumes that I only have a single zone("Zone Alpha") that contains a single
pod ("Pod Beta")

```
define hostgroup {
    hostgroup_name  Cloud Manager
    alias           Cloud Manager
}

define host {
    address         192.168.0.2
    host_name       cloudmgr.example.com
    use             linux-server
    hostgroups      Linux Servers, Cloud Manager
}

define command{
    command_name check_cloud_capacity
    command_line /usr/bin/php $USER1$/check_cloud_capacity.php -f /path/to/config/file.php -t $ARG1$ -w 80 -c 90 -n "$ARG2$"
}

define command{
    command_name check_cloud_instances
    command_line /usr/bin/php $USER1$/check_cloud_instances.php -f /path/to/config/file.php
}

define command{
    command_name check_cloud_systemvms
    command_line /usr/bin/php $USER1$/check_cloud_systemvms.php -f /path/to/config/file.php -t $ARG1$
}

define service {
    name        generic-service-graphed
    use         generic-service
    action_url  /pnp4nagios/graph?host=$HOSTNAME$&srv=$SERVICEDESC$
    register    0
}

define service{
    use                    generic-service-graphed
    service_description    Cloud Instance Count
    hostgroups             Cloud Manager 
    check_command          check_cloud_instances
    normal_check_interval  5
}

define service{
    use                  generic-service-graphed
    service_description  Cloud POD Beta CPU Used
    hostgroups           Cloud Manager
    check_command        check_cloud_capacity!cpu!Pod Beta
}

define service{
    use                  generic-service-graphed
    service_description  Cloud POD Beta Memory Used
    hostgroups           Cloud Manager
    check_command        check_cloud_capacity!memory!Pod Beta
}

define service{
    use                  generic-service-graphed
    service_description  Cloud POD Beta Primary Storage Used
    hostgroups           Cloud Manager
    check_command        check_cloud_capacity!storage_primary_used!Pod Beta
}

define service{
    use                  generic-service-graphed
    service_description  Cloud POD Beta Private IPs Used
    hostgroups           Cloud Manager
    check_command        check_cloud_capacity!ips_private!Pod Beta
}

define service{
    use                  generic-service-graphed
    service_description  Cloud ZONE Alpha Public IPs Used
    hostgroups           Cloud Manager
    check_command        check_cloud_capacity!ips_public!Zone Alpha
}

define service{
    use                  generic-service-graphed
    service_description  Cloud ZONE Alpha Secondary Storage
    hostgroups           Cloud Manager
    check_command        check_cloud_capacity!storage_secondary!Zone Alpha
}

define service{
    use                  generic-service
    service_description  Console Proxy VMs
    hostgroups           Cloud Manager
    check_command        check_cloud_systemvms!consoleproxy
}

define service{
    use                  generic-service
    service_description  Router VMs
    hostgroups           Cloud Manager
    check_command        check_cloud_systemvms!router
}

define service{
    use                  generic-service
    service_description  Secondary Storage VMs
    hostgroups           Cloud Manager
    check_command        check_cloud_systemvms!secondarystoragevm
}
```

EXAMPLE GRAPHS:
---------------
**check_cloud_instances:**

![check_cloud_instances](https://github.com/jasonhancock/nagios-cloudstack/raw/master/example-images/check_cloud_instances.png)
