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

$options  = getopt('f:w:c:hV', array('help', 'version', 'warning', 'critical'));
if(isset($options['h']) || isset($options['help']))
    usage();

if(isset($options['V']) || isset($options['version']))
    version();

if(isset($options['w']) || isset($options['warning']))
    if(is_numeric($options['w']))
        $warning = $options['w'];
    else
        throw new Exception("warning is not numeric - $warning");

if(isset($options['c']) || isset($options['critical']))
    if(is_numeric($options['c']))
        $critical = $options['c'];
    else
        throw new Exception("critical is not numeric - $critical");

if(isset($options['f']) && is_file($options['f']))
    $config = require_once($options['f']);
else {
    echo 'UNKNOWN - No configuration file (-f) found.';
    exit(3);
}

$cloudstack = new CloudStackClient(
    $config['API_ENDPOINT'],
    $config['API_KEY'],
    $config['API_SECRET']
);

$vms = $cloudstack->listSystemVms();

$sessions = 0;

foreach($vms as $vm) {
    if($vm->systemvmtype != 'consoleproxy')
        continue;

    $sessions += $vm->activeviewersessions;
}

$perfdata = "'sessions'=$sessions;";
if(isset($warning))
    $perfdata .= "$warning;";
if(isset($critical))
    $perfdata .= "$critical;";

$msg = "$sessions conolsole proxy sessions";

if(isset($critical) && $sessions > $critical) {
    echo "CRITICAL - $msg|$perfdata";
    exit(2);
} elseif(isset($warning) && $sessions > $warning) {
    echo "WARNING - $msg|$perfdata";
    exit(1);
} else {
    echo "OK - $msg|$perfdata";
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
check_cloud_consoleproxy_sessions.php v$VERSION
Copyright (c) 2012 Jason Hancock <jsnbyh@gmail.com>

This plugin queries the CloudStack API to determine all of the secondary storage,
console proxy, or router VMs then attempts to ping the public IP of each VM.

Usage:
 check_cloud_consoleproxy_sessions.php -f <config_file> -w <warning> -c <critical>

Options:
 -h, --help
    Print detailed help screen
 -V, --version
    Print version information
 -f
    Path to the configuration file that returns an associative array with keys
    API_ENDPOINT, API_KEY, and API_SECRET defined.
 -w, --warning
    Optional. If the number of console proxy sessions reported by the CloudStack
    API is in excess of this value, issue a warning
 -c, --critical
    Optional. If the number of console proxy sessions reported by the CloudStack
    API is in excess of this value, issue a critical 

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
