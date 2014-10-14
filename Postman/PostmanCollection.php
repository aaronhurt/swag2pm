<?php

class PostmanCollection extends Postman {
    public $name;
    public $description;
    public $id;
    public $timestamp;
    public $order;
    public $folders;
    public $synced;
    public $requests;

    public function __construct($name, $description = "") {
        $this->name = $name;
        $this->description = $description;
        $this->id = UUID::v4();
        $this->order = array();
        $this->folders = array();
        $this->timestamp = time();
        $this->synced = false;
        $this->requests = array();
    }
}
