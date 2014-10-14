#!/usr/bin/env php
<?php

##https://172.27.3.47:9443/api/v1/api-docs.json/

/* only run from the cli */
if (php_sapi_name() !== 'cli') {
    die("This script may only be run from the command line." . PHP_EOL);
}

/* check arguments */
if ($argc < 2) {
    die(sprintf("Usage: %s <swagger source> <collection name>\n", $argv[0]));
}

// pull in required uuid class
require_once dirname(__FILE__) . "/UUID/UUID.php";

// pull in postman class
require_once dirname(__FILE__) . "/Postman/Postman.php";


// create postman object
$postman = new Postman($argv[2]);

// grab the initial payload ensuring trailing slash
$fetch = file_get_contents(trim($argv[1], '/') . '/');

// decode and check return
if (($apis = json_decode($fetch)) === null) {
    die(sprintf("Error: failed to decode json payload: %s", $fetch) . PHP_EOL);
}


// parse passed url
$my_parts = parse_url($argv[1]);

function join_paths(array $paths = array()) {
    return preg_replace('#/+#', '/', join('/', $paths));
}

function simple_build_url(array $parts = array()) {
    // set scheme
    $scheme = isset($parts['scheme']) ? $parts['scheme'] : 'http';
    // set host
    $host = isset($parts['host']) ? $parts['host'] : 'localhost';
    // append port if passed
    if (isset($parts['port'])) {
        $host = sprintf("%s:%d", $host, $parts['port']);
    }
    // set path
    $path = isset($parts['path']) ? $parts['path'] : '/';
    // return result
    return sprintf("%s://%s%s", $scheme, $host, $path);
}

// loop through apis
foreach ($apis->apis as $api) {
    // get path name
    $path = join_paths(array($my_parts['path'], basename($api->path)));

    // build url
    $url = simple_build_url(array_merge($my_parts, array('path' => $path)));

    // fetch path
    $fetch = file_get_contents($url);

    // decode and check return
    if (($path = json_decode($fetch)) === null) {
        die(sprintf("Error: failed to decode json payload: %s", $fetch) . PHP_EOL);
    }

    // loop through apis in path
    foreach ($path->apis as $papi) {
        // build description
        $description = $papi->description;
        if (isset($papi->notes)) {
            $description = sprintf("%s\n---\n%s", $description, $papi->notes);
        }
        // create folder if needed
        if (!$postman->activeFolder()) {
            $postman->newFolder(basename($api->path), $description);
        }

        // create request if needed
        if (!$postman->activeRequest()) {
            // build description
            $description = $papi->operations[0]->summary;
            if (isset($papi->operations[0]->notes)) {
                 $description = sprintf("%s\n---\n%s", $description, $papi->operations[0]->notes);
            }
            // getenerate and init request object
            $postman->newRequest(
                array(
                    'name' => $papi->operations[0]->nickname,
                    'description' => $description,
                    'url' => simple_build_url(array_merge($my_parts,
                        array('path' => join_paths(array(dirname($my_parts['path']), str_replace('{format}', 'json', $papi->path)))))),
                    'method' => $papi->operations[0]->httpMethod,
                )
            );
        }

        // init data array
        $data = array();

        // build data if needed
        if (isset($papi->operations[0]->parameters) && is_array($papi->operations[0]->parameters) && count($papi->operations[0]->parameters)) {
            for ($x = 0; $x < count($papi->operations[0]->parameters); $x++) {
                // get this param
                $param = $papi->operations[0]->parameters[$x];
                // build data
                $data[$x]['name'] = $param->name;
                if (strcasecmp($param->dataType, 'int') == 0) {
                    $default = 0;
                } else {
                    $default = "";
                }
                $data[$x]['value'] = (!isset($param->defaultValue) || ($param->defaultValue == 'null')) ? $default : $param->defaultValue;
                $data[$x]['type'] = $param->dataType;
            }
        }

        // add request data
        $postman -> setRequestData($data);

        // add request
        $postman->addRequest();
    }

    // add folder
    $postman->addFolder();
}

// build filename
$fname = sprintf("./%s-postman.json", $argv[2]);

// write collection
if (!$file = fopen($fname, 'w')) {
    die(sprintf("Error: failed to open file for writing: %s", $fname));
}

if (!fwrite($file, $postman->renderCollection())) {
    die(sprintf("Error: failed to write to file: %s", $fname));
}

// close file
fclose($file);

// finish
printf("Finished - wrote output to: %s%s", $fname, PHP_EOL);

// exit
exit(0);
