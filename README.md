# SetaPDF-Demos

[This repository](https://github.com/Setasign/SetaPDF-Demos) hosts demos for all
[SetaPDF](https://www.setasign.com/products/) components.

During our build process they will be separated so that only the demos for a
specific component are shipped.

## How to run the demos

The easies way would be to simply start the build-in webserver of your local
PHP setup:

```
cd public
php -S localhost:8080 index.php
```

...and open [http://localhost:8080](http://localhost:8080) in your web browser.

Otherwise, just copy the complete package onto your webserver and open the /public 
folder in your browser. The web server must be configured so all requests to unknown files/directories
are redirected to "public/index.php" (preconfigured for Apache in the .htaccess file). 
