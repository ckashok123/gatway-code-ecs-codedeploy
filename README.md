# gateway_magento_plugin
The following will describe how to setup, deploy, upgrade and troubleshoot with Magento 2

## Environment Setup

The following instructions describe how to run Magento 2 in a docker container with a theme (Bergstrome's is the example merchant for Verifone)

### Pull Docker Image

Once you've downloaded/cloned the repository, you can run the following command to pull the Magento 2 image. Here is the link to documentation on the Magento 2 container: https://hub.docker.com/r/alexcheng/magento2  
```
docker pull alexcheng/magento2
```

### Pre-Configure the Magento 2 environment:
The `env` file stores the basic config for the environment. Below are the default values of the fields:  
MYSQL_HOST=`db`  
MYSQL_ROOT_PASSWORD=`myrootpassword`  
MYSQL_USER=`magento`  
MYSQL_PASSWORD=`magento`  
MYSQL_DATABASE=`magento`  

MAGENTO_LANGUAGE=`en_GB`  
MAGENTO_TIMEZONE=`Pacific/Auckland`  
MAGENTO_DEFAULT_CURRENCY=`NZD`  
MAGENTO_URL=http://local.magento  
MAGENTO_BACKEND_FRONTNAME=`admin`  
MAGENTO_USE_SECURE=`0`  
MAGENTO_BASE_URL_SECURE=`0`  
MAGENTO_USE_SECURE_ADMIN=`0`  

MAGENTO_ADMIN_FIRSTNAME=`Admin`  
MAGENTO_ADMIN_LASTNAME=`MyStore`  
MAGENTO_ADMIN_EMAIL=`amdin@example.com`  
MAGENTO_ADMIN_USERNAME=`admin`  
MAGENTO_ADMIN_PASSWORD=`magentorocks1`

### Set desired Plugins
In the `docker-compose.yml` file, set the plugins that you want to use by adding them to the `volumes` field under `services`. The default plugins below are for the Bergstrom's merchant, but if you only want the Dimebox plugin then the Ves and berstroms folders should be removed. 
```
services:  
    volumes:  
      - ./Dimebox:/var/www/html/app/code/Dimebox  
      - ./Ves:/var/www/html/app/code/Ves  
      - ./bergstroms:/var/www/html/app/design/frontend/Dimebox/bergstroms
```

To add a new plugin, use the following format:  
`- ./<folder in server>:<folder in Magento App>`

### Create Docker Image
Run the following command to create the docker image with the desired configuration set above:  
```
docker-compose up
```

### Install Magento
Now that the image has been pulled, you can install Magento 2 (note: `magento2_web_1` below is the container name and may be different based on the folder you use)  
```
docker exec -it magento2_web_1 install-magento
```

### Install Sample Data
After installing Magento 2, you can install the sample data (same note applies to the container name):  
```
docker exec -it magento2_web_1 install-sampledata
```

## Updating Application
This section describes how to update the Magento application when you want to make a change to a plugin.
1. Push file changes to the server
2. Access the Docker Image with the command below, where `web` is the name of the Docker Image:
```
docker-compose exec web bash
```
3. Clear cache once you're inside the docker image. Note that you should be in the parent folder by default.
```
bin/magento cache:flush
```
4. Compile Magento Modules. For example, this would be required if there is a change to the Bergstrom's CSS, since the format from this file is compiled into by the Magento application into a separate static CSS file for each supported language.
```
bin/magento setup:upgrade
```
5. Deploy changes. For example, this can push any changes to the Dimebox plugin code.
```
bin/magento setup:static-content:deploy -f
```

## Troubleshooting
This section will describe some common issues and commands to overcome them.

### Developer vs Product mode
Setting the application to developer mode is key when applying major code and config changes. Below are the commands for this:
1. Enter the docker image:
```
docker-compose exec web bash
```
2. Check the current mode:
```
bin/magento deploy:mode:show
```
3. Set the mode to Developer:
```
bin/magento deploy:mode:set developer
```

For the best performance, it is recommended to set the mode to Production. Below is the specific command to change the mode to Production:
```
bin/magento deploy:mode:set production
```

### Restoring the DB
The DB can be corrupted from unstable code and testing. When this occurs the Magento UI won't be able to display the sample date. Below are the command that can be used to reset to the orginal Sample DB (ideal to demo purposes):
1. Remove the existing data
```
bin/magento sampledata:remove
```
2. Reset the sample data
```
bin/magento sampledata:reset
```

In the case where you need to backup and restore the DB, refer to the following guide: https://devdocs.magento.com/guides/v2.3/install-gde/install/cli/install-cli-backup.html

### Logs
The logs can be found in the folder `var/log` and will contain debug, instal and system log files.

### Error - Printing exemption
In the case there the following printing exemption error is thrown when you try to load the magento store. Note that you can also view the full error log by navigating to `var/report`.
```
There has been an error processing your request
Exception printing is disabled by default for security reasons.
Error log record number: 1234567890
```
Navigate to `pub/errors` and change the file name of `local.xml.sample` to `local.xml` with the following command:
```
cp local.xml.sample local.xml
rm local.xml.sample
```

### Error - getaddrinfo failed
The error below occurs when the DB is down. In order to restart the DB, you can `docker-compose stop` and then `docker-compose start` the docker image to bring the application back up.
```
SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: Name or service not known
```

### Error - Lock wait timeout
The following error appears in the exception logs when trying to load the `catalog_category_product` sample data. You will see this message when the database contents the application is trying to access has been locked by a (typically long running) previous process. MySQL will wait a certain amount of time for the lock to be removed before it gives up and throws that error.
```
SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction
```

This can be fixed by updating the timeout in the MySQL server with the following steps:
1. Login to MySQL `http://localhost:8580` with user and Password set in `env` file
2. Click the `SQL` tab and enter the following query to check the current timeout value
```
show variables like 'innodb_lock_wait_timeout';
```
3. Update the timeout value to 120s
```
SET innodb_lock_wait_timeout = 120
```
4. Check that the value was saved
```
show variables like 'innodb_lock_wait_timeout';
```

