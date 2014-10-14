<?php

class PostmanRequest extends Postman {
    public $name;
    public $description;
    public $id;
    public $url;
    public $pathVariables;
    public $method;
    public $dataMode;
    public $headers;
    public $version;
    public $tests;
    public $time;
    public $collectionId;
    public $responses;

    public function __construct(array $request, &$collection) {
        foreach (array('name', 'description', 'url', 'method') as $required) {
            if (!isset($request[$required])) {
                throw new PostmanRequestException(sprintf(MISSING_REQUIRED_MSG, $required), MISSING_REQUIRED);
            }
        }

        $this->name = $request['name'];
        $this->description = isset($request['description']) ? $request['description'] : "";
        $this->id = UUID::v4();
        $this->url = $request['url'];
        $this->pathVariables = new stdClass();
        $this->method = $request['method'];

        switch ($request['method']) {
            case "POST":
                $this->dataMode = "raw";
                $this->headers = "Content-Type: application/json\n";
            break;
            default:
                $this->dataMode = "params";
                $this->headers = "";
            break;
        }

        if (isset($request['data']) && is_array($request['data']) && count($request['data'])) {
            $this->data = $request['data'];
        }

        $this->version = isset($request['version']) ? $request['version'] : 2;
        $this->tests = isset($request['tests']) ? $request['tests'] : "";
        $this->time = $collection->timestamp;
        $this->collectionId = $collection->id;
        $this->responses = (isset($request['responses']) && is_array($request['responses'])) ? $request['responses'] : array();
    }
}
