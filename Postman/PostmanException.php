<?php

class PostmanException extends Exception {}

class PostmanCollectionException extends PostmanException {}

class PostmanFolderException extends PostmanException {}

class PostmanRequestException extends PostmanException {}

define("MISSING_REQUIRED", 1000);
define("MISSING_REQUIRED_MSG", "Required parameter \"%s\" missing.");

define("NO_ACTIVE_COLLECTION", 1001);
define("NO_ACTIVE_COLLECTION_MSG", "You must have an active collection to use this command.");

define("ACTIVE_FOLDER", 1002);
define("ACTIVE_FOLDER_MSG", "Please add your existing active folder using addFolder() before issuing this command.");

define("NO_ACTIVE_FOLDER", 1003);
define("NO_ACTIVE_FOLDER_MSG", "You must have an active folder before using this command.  Please create one using the newFolder() method.");

define("ACTIVE_REQUEST", 1004);
define("ACTIVE_REQUEST_MSG", "Please add your existing active request using addRequest() before issuing this command.");

define("NO_ACTIVE_REQUEST", 1005);
define("NO_ACTIVE_REQUEST_MSG", "You must have an active request before using this command.  Please create one using the newRequest() method.");

define("UNKNOWN_DATA_MODE", 1006);
define("UNKNOWN_DATA_MODE_MSG", "Unknown data mode (%s) encountered while processing request.");

define("JSON_ENCODE_ERROR", 1007);
define("JSON_ENCODE_ERROR_MSG", "Error encoding json payload.");
