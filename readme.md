### CoPilot
Simple system intel tool for eve online.


#### Data Sources
+ [crest](https://developers.eveonline.com/)
+ [zkillboard](https://github.com/zKillboard/zKillboard/wiki)
+ [evemaps](http://evemaps.dotlan.net)
+ [fuzzworks](https://www.fuzzwork.co.uk/tools/api-map-data/)
+ [eve central](https://eve-central.com/home/develop.html)

  
#### System Requirements
+ nginx
+ php 5
+ curl
+ [composer](https://getcomposer.org/download/)

#### Installation

##### Server
Install nginx and php
```apt-get install nginx php5-fpm php5-curl```

Upload 
Copy the nginx config:
```cp 
server {
    listen       80;
    server_name  some.host.com;

    access_log  /var/log/nginx/access.log  main;
    error_log /var/log/nginx/error.log main;

    root   /path/to/CoPilot/public/;
    index  index.php;

    location / {
	try_files $uri $uri/ /index.php?url=$request_uri&$args;
    }

    error_page  404              /404.html;

    # redirect server error pages to the static page /50x.html
    #
    error_page   500 502 503 504 404  /50x.html;
    location = /50x.html {
        root   /usr/share/nginx/html;
    }

    # pass the PHP scripts to FastCGI
    #
    location ~ \.php$ {
	try_files $uri 404;
        fastcgi_pass   unix:/var/run/php5-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include        fastcgi_params;
	fastcgi_intercept_errors on;
    }
}
```



