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

$VERSION = '1.0';

/* http://cloudstack.org/forum/6-general-discussion/7102-performance-monitoring-option-for-cloudcom.html
Type 0 - Memory
Type 1 - CPU
Type 2 - Primary Storage Used
Type 3 - Primary Storage Allocated
Type 4 - Public IPs
Type 5 - Private IPs
Type 6 - Secondary Storage
*/

$stats = array(
    'memory'                    => array(
        'id'    => 0,
        'level' => 'pod',
    ),
    'cpu'                       => array(
        'id'    => 1,
        'level' => 'pod',
    ),
    'storage_primary_used'      => array(
        'id'    => 2,
        'level' => 'pod',
    ),
    'storage_primary_allocated' => array(
        'id'    => 3,
        'level' => 'pod',
    ),
    'ips_public'                => array(
        'id'    => 4,
        'level' => 'zone',
    ),
    'ips_private'               => array(
        'id'    => 5,
        'level' => 'pod',
    ),
    'storage_secondary'         => array(
        'id' => 6,
        'level' => 'zone',
    ),
);

$options = getopt('t:w:c:n:f:h', array('help'));
if(isset($options['h']) || isset($options['help']))
    usage();
$type    = !empty($options['t']) ? $options['t'] : '';
$warn    = !empty($options['w']) ? $options['w'] : '';
$crit    = !empty($options['c']) ? $options['c'] : '';
$name    = !empty($options['n']) ? $options['n'] : '';

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

try {
    if(!isset($stats[$type]))
        usage("Unknown type (-t) |$type|");
    
    if(!is_numeric($warn))
        usage("Warning (-w) is not numeric $warn");

    if(!is_numeric($crit))
        usage("Critical (-c) is not numeric $crit");

    if($warn > $crit)
        throw new Exception("Warning must be less than critical");

    if(strlen($name) == 0)
        usage("Name (-n) must be set and must either be a zone or pod name (depends on type |-t| variable)");

    $id = $stats[$type]['level'] == 'pod' ? getPodId($name) : getZoneId($name);

    $result = $cloudstack->listCapacity(array(
        'type' => $stats[$type]['id'],
        'zoneid' => $stats[$type]['level'] == 'pod' ? '' : $id,
        'podid' => $stats[$type]['level'] == 'pod' ? $id : '' 
    ));

    //print_r($result);

    $perfdata = array();

    for($i=0; $i<count($result->capacity) && empty($value); $i++) {
        $comp = $stats[$type]['level'] == 'pod' ? $result->capacity[$i]->podid : $result->capacity[$i]->zoneid;
        if($comp == $id) {
            $value = $result->capacity[$i]->percentused;
            $perfdata[] = sprintf("'%s'=%s%s;%s;%s;0;100",
                $type,
                $value,
                '%',
                $warn,
                $crit
            );
        }
    }

    if(empty($value)) {
        $msg = "UNKNOWN: unable to determine value for $type";
        $status = 3;
    } elseif($value > $crit) {
        $msg = "ERROR: $type is too high ($value% > $crit%)";
        $status = 2;
    } elseif($value > $warn) {
        $msg = "WARN: $type is too high ($value% > $warn%)";
        $status = 1;
    } else {
        $msg = "OK: $type is fine - $value%";
        $status = 0;
    }

    echo $msg . '|' . implode(' ', $perfdata);
    exit($status);
} catch (Exception $e) {
    echo "UNKNOWN: exception {$e->getMessage()}";
    exit(3); 
}

function getPodId($name) {
    global $cloudstack;

    $response = $cloudstack->listPods(array(
        'name' => $name
    ));

    if(isset($response[0]->id))
        return $response[0]->id;
    else
        throw new Exception("Unable to determine pod ID for name=$name");
}

function getZoneId($name) {
    global $cloudstack;

    $response = $cloudstack->listZones();

    for($i=0; $i<count($response); $i++) {
        if($response[$i]->name == $name)
            return $response[$i]->id;
    }

    throw new Exception("Unable to determine zone ID for name=$name");
}

function usage($msg = '') {
    global $VERSION, $stats;

    $types = implode(', ', array_keys($stats));

    if(strlen($msg) > 0)
        echo "$msg\n\n";

    $level_str = sprintf("      %-30s | %s\n", 'Value of -t', 'Zone or Pod name?');
    $level_str .= "      -------------------------------|-------------------\n";
    foreach ($stats as $key => $attribs)
        $level_str .= sprintf("      %-30s | %s\n", $key, $attribs['level']);

    echo <<<EOT
check_cloud_capacity.php v$VERSION
Copyright (c) 2011 Jason Hancock <jsnbyh@gmail.com>

This plugin reports on metrics of a CloudStack based cloud. It accesses this
data via the listCapacity api command. 

Usage:
 check_cloud_capacity.php -f <config_file> -t <type> -n <zone or pod name>
 -w <warning> -c <critical>

Options:
 -h, --help
    Print detailed help screen
 -V, --version
    Print version information
 -f
    Path to the configuration file that returns an associative array with keys
    API_ENDPOINT, API_KEY, and API_SECRET defined.
 -t type
    Which parameter to report on can be one of
    $types
 -n name
    The name of the Zone or Pod to report on. Depends on the -t parameter.

$level_str

 -w warning
    The percentage at which to warn. (ex. "80" to warn at 80% usage)
 -c critical
    The percentage at which to throw a critical error. (ex. "90" to crit at 90% usage)

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

