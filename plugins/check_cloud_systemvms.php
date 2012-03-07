<?php
/*
 * Copyright (C) 2012 Jason Hancock http://geek.jasonhancock.com
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

$options  = getopt('f:t:hV', array('help', 'version'));
if(isset($options['h']) || isset($options['help']))
    usage();

if(isset($options['V']) || isset($options['version']))
    version();

if(isset($options['f']) && is_file($options['f']))
    $config = require_once($options['f']);
else {
    echo 'UNKNOWN - No configuration file (-f) found.';
    exit(3);
}

if(isset($options['t']))
    $type = $options['t'];
else {
    echo 'UNKNOWN - System VM type (-t) not specified.';
    exit(3);
}

$cloudstack = new CloudStackClient(
    $config['API_ENDPOINT'],
    $config['API_KEY'],
    $config['API_SECRET']
);

if($type == 'router')
    $vms = $cloudstack->listRouters();
else
    $vms = $cloudstack->listSystemVms();

$unreachable = array();

foreach($vms as $vm) {
    if($type != 'router' && $vm->systemvmtype != $type)
        continue;

    // Different attributes for different types of VM
    $ip = $type == 'router' ? $vm->guestipaddress : $vm->publicip;
    $cmd = sprintf('ping -c 1 -W 1 %s > /dev/null 2>&1', $ip);
    exec($cmd, $output, $status);

    if($status != 0)
        $unreachable[] = $vm->name;
}

if(count($unreachable) > 0) {
    printf('CRITICAL: Unreachable %s VM(s): %s', $type, implode(', ', $unreachable));
    exit(2);
} else {
    echo "$type - OK";
    exit(0);
}

function version() {
    global $VERSION;
    echo "check_cloud_systemvms.php v$VERSION\n";
    exit(0);
}

function usage() {
    global $VERSION;

    echo <<<EOT
check_cloud_systemvms.php v$VERSION
Copyright (c) 2012 Jason Hancock <jsnbyh@gmail.com>

This plugin queries the CloudStack API to determine all of the secondary storage,
console proxy, or router VMs then attempts to ping the public IP of each VM.

Usage:
 check_cloud_systemvms.php -f <config_file> -t <system_vm_type>

Options:
 -h, --help
    Print detailed help screen
 -V, --version
    Print version information
 -f
    Path to the configuration file that returns an associative array with keys
    API_ENDPOINT, API_KEY, and API_SECRET defined.
 -t
    System VM type. Can be 'consoleproxy', 'secondarystoragevm', or 'router'

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
