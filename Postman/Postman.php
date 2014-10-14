<?php

require_once dirname(__FILE__) . "/PostmanCollection.php";
require_once dirname(__FILE__) . "/PostmanFolder.php";
require_once dirname(__FILE__) . "/PostmanRequest.php";
require_once dirname(__FILE__) . "/PostmanException.php";

class Postman {
    protected $_collection;
    protected $_folder;
    protected $_request;

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
        $temps = array();
        switch ($this->_request->dataMode) {
            case "raw":
                foreach ($data as $d) {
                    $temps[$d['name']] = $d['value'];
                }
                $this->_request->rawModeData = json_encode($temps);
            break;
            case "params":
                foreach ($data as $k => $d) {
                    if (preg_match('/\{'.$d['name'].'\}/i', $this->_request->url)) {
                        $this->_request->url = str_replace('{'.$d['name'].'}', '{{'.$d['name'].'}}', $this->_request->url);
                        unset($data[$k]);
                        continue;
                    }
                    $temps[$d['name']] = $d['value'];
                }
                $this->_request->data = $data;
                $this->_request->url = sprintf("%s?%s", $this->_request->url, http_build_query($temps));
            break;
            default:
                throw new PostmanException(sprintf(UNKNOWN_DATA_MODE_MSG, $this->_request->dataMode), UNKNOWN_DATA_MODE);
            break;
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
        $this->_collection->requests[] = $this->_request;
        unset($this->_request);
    }

    public function activeRequest() {
        return isset($this->_request);
    }

    public function __construct($name, $description = "") {
        $this->_activeFolder = false;
        $this->_activeRequest = false;
        $this->_collection = new PostmanCollection($name, $description);
    }
}
