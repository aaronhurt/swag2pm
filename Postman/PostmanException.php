<?php

class PostmanException extends Exception {}

class PostmanCollectionException extends PostmanException {}

class PostmanFolderException extends PostmanException {}

class PostmanRequestException extends PostmanException {}

define("MISSING_REQUIRED", 1000);

define("MISSING_REQUIRED_MSG", "Required parameter \"%s\" missing.");
