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
These instructions assume an unconfigured debian based linux system

Install nginx, php and git

```sudo apt-get install nginx php5-fpm php5-curl git```


Clone Copilot

```cd /var/www/```
```git clone https://github.com/RZN-FFEvo/CoPilot.git```
```cd CoPilot```


Install Composer using the command line option:

<https://getcomposer.org/download/>

Set up composer

```php composer.phar -d=./core install```

Create the cache directory (because i'm bad at git)

```sudo mkdir core/cache```

Set file permissions

```sudo chmod -R 0755 core/cache```

Set file owner

```sudo chown -R www-data ./```

Create and edit the config.

Note: this version does not require app keys from cpp as a did not have time to finish the authenticated crest features.

```mv core/config.php.dist core/config.php```

I like nano, use what you like

```nano core/config.php```

Change "baseURL" to your url. "Ctrl + o" saves, "Ctrl + x" quits


Time to configure nginx.

Copy the example configuration:

```sudo cp copilot.nginx.conf /etc/nginx/sites-available/copilot.conf```

Edit it:

```sudo nano /etc/nginx/sites-available/copilot.conf```

if you are using a domain, change "server_name" to your domain name

make sure "root" is the path to CoPilot/public

save "Ctrl + o", quit "Ctrl + x"

symlink our new config so nginx will load it

 ```sudo ln -s /etc/nginx/sites-available/copilot.conf /etc/nginx/sites-enabled/copilot.conf```
 
 you can use the command "sudo nginx -t" to check your configuration
 
restart nginx

```sudo service nginx restart```

restart php

```sudo service php5-fpm restart```


You should be able to access the site now.

If you get an error message check the error log:

```tail /var/log/nginx/error.log```

