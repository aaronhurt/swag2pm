<?php

class PostmanRequest extends Postman {
    public $name;
    public $description;
    public $id;
    public $url;
    public $method;
    public $headers;
    public $version;
    public $tests;
    public $time;
    public $collectionId;
    public $responses;
    protected $_consumes;

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
        $this->method = $request['method'];
        $this->headers = "";
        $this->version = 2;
        $this->tests = isset($request['tests']) ? $request['tests'] : "";
        $this->time = $collection->timestamp;
        $this->collectionId = $collection->id;
        $this->responses = (isset($request['responses']) && is_array($request['responses'])) ? $request['responses'] : array();
        $this->_consumes = isset($request['consumes']) ? $request['consumes'] : "";
    }
}
