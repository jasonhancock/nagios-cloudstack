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
 * Plots number of cloudstack instances by state as reported by the
 * check_cloud_instances.php plugin.
 *
 * This file is part of the nagios-cloudstack bundle that can be found
 * at https://github.com/jasonhancock/nagios-cloudstack
 */
$alpha = '';

$colors = array(
    'Running'   => '#00FF00' . $alpha,
    'Starting'  => '#0000FF' . $alpha,
    'Stopping'  => '#FF0000' . $alpha,
    'Destroyed' => '#CCCCCC' . $alpha,
);

$vlabel = '# of VMs';
    
$opt[1] = sprintf('-T 55 -l 0 --vertical-label "%s" --title "%s / Number of VMs"', $vlabel, $hostname);
$def[1] = '';
$ds_name[1] = 'Cloud Instances';

$count = 0;
foreach ($this->DS as $i => $dso) {
    $def[1] .= rrd::def("var$i", $dso['RRDFILE'], $dso['DS'], 'AVERAGE');

    $name = rrd::cut($dso['NAME'], 9);
    $color = isset($colors[$dso['NAME']]) ? $colors[$dso['NAME']] : '#FFFF00';
    $stack = $i == 0 ? '' : 'STACK';
    
    $def[1] .= rrd::area ("var$i", $color, $name, $i == 0 ? '' : $stack);
    $def[1] .= rrd::gprint  ("var$i", array('LAST','MAX','AVERAGE'), "%3.0lf");
}

$def[1] .= 'COMMENT:"' . $TEMPLATE[$i] . '\r" ';
