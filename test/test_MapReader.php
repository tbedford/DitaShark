#!/usr/bin/env php

<?php

require_once '../classes/MapReader.php';

$results = array();

$reader = new MapReader();
$results = $reader->start('./Source/ditamaps/enterprise.ditamap');

foreach ($results as $f){
    echo "RESULT: $f\n";
}

?>
