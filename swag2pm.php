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
if (($swagger = json_decode($fetch)) === null) {
    die(sprintf("Error: failed to decode json payload: %s", $fetch) . PHP_EOL);
}

// check version
if (!isset($swagger->swaggerVersion) || $swagger->swaggerVersion != "1.2") {
    die("Error: Only Swagger version 1.2 currently supported." . PHP_EOL);
}

// parse passed url
$my_parts = parse_url($argv[1]);

// join url paths
function join_paths(array $paths = array()) {
    return preg_replace('#/+#', '/', join('/', $paths));
}

// reconstruct url pieces
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
foreach ($swagger->apis as $api) {
    // get path
    $path = join_paths(array($my_parts['path'], basename($api->path)));

    // build url
    $url = simple_build_url(array_merge($my_parts, array('path' => $path)));

    // fetch path
    $fetch = file_get_contents($url);

    // decode and check return
    if (($collection = json_decode($fetch)) === null) {
        die(sprintf("Error: failed to decode json payload: %s", $fetch) . PHP_EOL);
    }

    // loop through apis in path
    foreach ($collection->apis as $capi) {
        // build description
        if (isset($capi->description)) {
            $description = $capi->description;
        } else if (isset($capi->summary)) {
            $description = $capi->summary;
        } else {
            $description = "";
        }
        // append notes if found
        if (isset($capi->notes)) {
            $description = sprintf("%s\n---\n%s", $description, $capi->notes);
        }
        // create folder if needed
        if (!$postman->activeFolder()) {
            $postman->newFolder(basename($api->path), $description);
        }
        // create request if needed
        if (!$postman->activeRequest()) {
            // build description
            $description = $capi->operations[0]->summary;
            // append notes if present
            if (isset($capi->operations[0]->notes)) {
                 $description = sprintf("%s\n---\n%s", $description, $capi->operations[0]->notes);
            }
            // get method
            if (isset($capi->operations[0]->httpMethod)) {
                $method = $capi->operations[0]->httpMethod;
            } else if (isset($capi->operations[0]->method)) {
                $method = $capi->operations[0]->method;
            } else {
                $method = "";
            }
            if (isset($collection->basePath) && $collection->basePath != "") {
                $url = simple_build_url(array_merge(parse_url($collection->basePath),
                    array('path' => str_replace('{format}', 'json', $capi->path))));
            } else {
                $url = simple_build_url(array_merge($my_parts,
                    array('path' => str_replace('{format}', 'json', $capi->path))));
            }
            $postman->newRequest(
                array(
                    'name' => $capi->operations[0]->nickname,
                    'url' => $url,
                    'description' => $description,
                    'method' => $method,
                    'consumes' => $capi->operations[0]->consumes[0],
                )
            );
        }

        // init data array
        $data = array();

        // build data if needed
        if (isset($capi->operations[0]->parameters) && is_array($capi->operations[0]->parameters) && count($capi->operations[0]->parameters)) {
            for ($x = 0; $x < count($capi->operations[0]->parameters); $x++) {
                // get this param
                $param = $capi->operations[0]->parameters[$x];
                // build data
                $data[$x]['name'] = $param->name;
                // get type
                if (isset($param->dataType)) {
                    $type = $param->dataType;
                } else if (isset($param->type)) {
                    $type = $param->type;
                } else {
                    $type = 'unknown';
                }
                // set default
                if (strcasecmp($type, 'int') == 0) {
                    $default = 0;
                } else {
                    $default = "";
                }
                // finish data
                $data[$x]['value'] = (!isset($param->defaultValue) || ($param->defaultValue == 'null')) ? $default : $param->defaultValue;
                $data[$x]['dtype'] = $type;
                $data[$x]['ptype'] = (isset($param->paramType)) ? $param->paramType : 'unknown';
            }
        }

        // add request data
        $postman->setRequestData($data);

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
