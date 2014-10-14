<?php

class PostmanFolder extends Postman {
    public $name;
    public $description;
    public $id;
    public $order;
    public $collection_name;
    public $collection_id;

    public function __construct($name, $description, &$collection) {
        $this->name = $name;
        $this->description = $description;
        $this->id = UUID::v4();
        $this->order = array();
        $this->collection_name = $collection->name;
        $this->collection_id = $collection->id;
    }
}
