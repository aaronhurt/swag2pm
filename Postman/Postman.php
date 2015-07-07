<?php

require_once dirname(__FILE__) . "/PostmanCollection.php";
require_once dirname(__FILE__) . "/PostmanFolder.php";
require_once dirname(__FILE__) . "/PostmanRequest.php";
require_once dirname(__FILE__) . "/PostmanException.php";

class Postman {
    protected $_collection;
    protected $_folder;
    protected $_request;
    protected $_headers;

    public function getCollection() {
        if (!isset($this->_collection)) {
            throw new PostmanException(NO_ACTIVE_COLLECTION_MSG, NO_ACTIVE_COLLECTION);
        }
        return $this->_collection;
    }

    public function renderCollection() {
        if (($json = json_encode($this->getCollection(), JSON_PRETTY_PRINT)) == null) {
            throw new PostmanException(JSON_ENCODE_ERROR_MSG, JSON_ENCODE_ERROR);
        }
        return $json . PHP_EOL;
    }

    public function newFolder($name, $description = "") {
        if (isset($this->_folder)) {
            throw new PostmanException(ACTIVE_FOLDER_MSG, ACTIVE_FOLDER);
        }
        $this->_folder = new PostmanFolder($name, $description, $this->_collection);
    }

    public function addFolder() {
        if (!isset($this->_folder)) {
            throw new PostmanException(NO_ACTIVE_FOLDER_MSG, NO_ACTIVE_FOLDER);
        }
        $this->_collection->folders[] = $this->_folder;
        unset($this->_folder);
    }

    public function activeFolder() {
        return isset($this->_folder);
    }

    public function newRequest(array $request = array()) {
        if (isset($this->_request)) {
            throw new PostmanException(ACTIVE_REQUEST_MSG, ACTIVE_REQUEST);
        }
        $this->_request = new PostmanRequest($request, $this->_collection);
    }

    public function setRequestData(array $data = array()) {
        if (!isset($this->_request)) {
            throw new PostmanException(NO_ACTIVE_REQUEST_MSG, NO_ACTIVE_REQUEST);
        }
        $q_data = array();
        $b_data = array();
        $f_data = array();
        $h_data = array();
        foreach ($data as $d) {
            switch (strtolower($d['ptype'])) {
                case "path":
                    if (preg_match('/\{'.$d['name'].'\}/i', $this->_request->url)) {
                        $this->_request->url = str_replace('{'.$d['name'].'}', '{{'.$d['name'].'}}', $this->_request->url);
                    }
                break;
                case "query":
                    $q_data[$d['name']] = $d['value'];
                break;
                case "body":
                    $b_data[$d['name']] = $d['value'];
                break;
                case "form":
                    switch (strtolower($d['dtype'])) {
                        case "file":
                            $type = "file";
                        break;
                        default:
                            $type = "text";
                        break;
                    }
                    $f_data[] = array(
                        'name' => $d['name'],
                        'type' => $type,
                        'value' => empty($d['value']) ? '{{'.$d['name'].'}}' : $d['value']
                    );
                break;
                case "header":
                    $h_data[$d['name']] = $d['value'];
                break;
                default:
                    throw new PostmanException(sprintf(UNKNOWN_PARAM_TYPE_MSG, $d['ptype']), UNKNOWN_PARAM_TYPE);
                break;
            }
        }
        if (count($b_data)) {
            $this->_request->dataMode = "raw";
            $this->_headers['Content-Type'] = ($this->_request->_consumes != "") ? $this->_request->_consumes : 'application/json';
            switch(strtolower($this->_request->_consumes)) {
                case "application/json":
                    $this->_request->rawModeData = json_encode($b_data);
                break;
                case "application/xml":
                    $this->_request->rawModeData = xmlrpc_encode($b_data);
                break;
                case "text/plain":
                    $this->_request->rawModeData = isset($b_data['body']) ? $b_data['body'] : "";
                break;
            }
        }
        if (count($q_data)) {
            $this->_request->url = sprintf("%s?%s", $this->_request->url, http_build_query($q_data));
        }
        if (count($f_data)) {
            $this->_request->data = $f_data;
        }
        if (count($h_data)) {
            foreach ($h_data as $k => $v) {
                $this->_headers[$k] = $v;
            }
        }
        if (!isset($this->_request->dataMode)) {
            $this->_request->dataMode = "params";
        }
    }

    public function addRequest() {
        if (!isset($this->_folder)) {
            throw new PostmanException(NO_ACTIVE_FOLDER_MSG, NO_ACTIVE_FOLDER);
        }
        if (!isset($this->_request)) {
            throw new PostmanException(NO_ACTIVE_REQUEST_MSG, NO_ACTIVE_REQUEST);
        }
        $this->_folder->order[] = $this->_request->id;
        if (count($this->_headers)) {
            foreach ($this->_headers as $key => $val) {
                if (isset($this->_request->headers) && !empty($this->_request->headers)) {
                    $this->_request->headers = sprintf("%s\n%s: %s", $this->_request->headers, $key, $val);
                } else {
                    $this->_request->headers = sprintf("%s: %s", $key, $val);
                }
            }
            $this->_headers = array();
        }
        $this->_collection->requests[] = $this->_request;
        unset($this->_request);
    }

    public function activeRequest() {
        return isset($this->_request);
    }

    public function __construct($name, $description = "") {
        $this->_headers = array();
        $this->_collection = new PostmanCollection($name, $description);
    }
}
