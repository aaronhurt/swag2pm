swag2pm
=======

This little script and class collection *should* generate [Postman](https://github.com/a85/POSTMan-Chrome-Extension/)
collections from [Swagger](https://github.com/wordnik/swagger-spec) api documentation feeds.

Example:

```
$ ./swag2pm.php
Usage: ./swag2pm.php <swagger source> <collection name>

$ ./swag2pm.php https://172.27.3.47:9443/api/v1/api-docs.json/ xms
Finished - wrote output to: ./xms-postman.json
```

Sample file generated using this tool against the Xirrus Management System is included.
You should be able to import these files direclty into Postman as new collections.
