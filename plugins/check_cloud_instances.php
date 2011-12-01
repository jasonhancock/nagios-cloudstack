<?php
/*
 * Copyright (C) 2011 Jason Hancock http://geek.jasonhancock.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://www.gnu.org/licenses/.
 *
 * 
 * This file is part of the nagios-cloudstack bundle that can be found
 * at https://github.com/jasonhancock/nagios-cloudstack
 *
 * CloudStackClient.php can be found as part of this project:
 * https://github.com/jasonhancock/cloudstack-php-client
 */

require_once('CloudStack/CloudStackClient.php');

$VERSION='1.0';

$data = array(
    'Running'   => 0,
    'Starting'  => 0,
    'Stopping'  => 0,
    'Destroyed' => 0,
);

$options  = getopt('f:h', array('help'));
if(isset($options['h']) || isset($options['help']))
    usage();

if(isset($options['f']) && is_file($options['f']))
    $config = require_once($options['f']);
else {
    echo "UNKNOWN - No configuration file (-f) found.";
    exit(3);
}

$cloudstack = new CloudStackClient(
    $config['API_ENDPOINT'],
    $config['API_KEY'],
    $config['API_SECRET']
);

$vms = $cloudstack->listVirtualMachines();
$count = 0;


foreach($vms as $vm) {
    if(isset($data[$vm->state]))
        $data[$vm->state]++;
}


$perfdata = array();
foreach ($data as $key => $value) {
    $perfdata[] = "$key=$value";
}

echo "OK - " . implode(' ', $perfdata) . '|' . implode(' ', $perfdata);
exit(0);

function usage() {
    global $data, $VERSION;

    $types = implode(', ', array_keys($data));

    echo <<<EOT
check_cloud_instances.php v$VERSION
Copyright (c) 2011 Jason Hancock <jsnbyh@gmail.com>

This plugin reports the number of instances running in a CloudStack
cloud based on instance state. Reports the count for the following states:
$types

Usage:
 check_cloud_instances.php -f <config_file>

Options:
 -h, --help
    Print detailed help screen
 -V, --version
    Print version information
 -f
    Path to the configuration file that returns an associative array with keys
    API_ENDPOINT, API_KEY, and API_SECRET defined.

Example Configuration File:
 # cat example_config.php
 <?php

 return array(
     'API_ENDPOINT' => 'http://cloudmgr.example.com:8080/client/api',
     'API_KEY'      => 'your_api_key',
     'API_SECRET'   => 'your_secret_key'
 );


EOT;
    exit(3);
}
