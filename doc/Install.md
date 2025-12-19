[![CC BY-NC-SA 4.0][cc-by-nc-sa-shield]][cc-by-nc-sa]

This work is licensed under a
[Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License][cc-by-nc-sa].

[![CC BY-NC-SA 4.0][cc-by-nc-sa-image]][cc-by-nc-sa]

[cc-by-nc-sa]: https://creativecommons.org/licenses/by-nc-sa/4.0/deed.de
[cc-by-nc-sa-image]: https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png
[cc-by-nc-sa-shield]: https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-lightgrey.svg

---
# Installation Instructions <br>for AhoyDTU on Raspberry-Pi with <br> NGINX-WebServices and Volkszaehler-Smart-Meter
## Basics
The already known `Ahoy(lumapu) on ESP8266 or ESP32` includes its own WebServer to present
the Hoymiles inverter data and to configure the system environment.
In this project, we use an `NGINX WebServer` to configure and to control the AhoyDTU environment,
to display the Hoymiles inverter data as well as to configure and display history data in a Volkszaehler-Smart-Meter environment.  
For this, we also need a `PHP FastCGI Process Manager` and `System-V IPC (Shared-Memory with Semaphore and Message-Queue)` for data exchange.
For the permanent storage of the historical operating data of all inverters,
we'll use a `Volkszaehler-Smart-Meter instance`  
(https://github.com/volkszaehler/volkszaehler.org),  
as well as the individual evaluation of this data.

1. `/tmp` must be available for all users. AhoyDTU stores log- and other temp files in this directory.
2. This project based on some specific linux packages, you have to install this packages first:
   ```code
   sudo apt-get update
   sudo apt-get -y full-upgrade
   sudo apt-get -y install cmake git python3-dev libboost-python-dev python3-pip python3-rpi.gpio
   ```
3. AhoyDTU must be installed in a non-user HOME directory, we prefere to install this project in: `/home/AhoyDTU`:
   ```code
   cd /home
   sudo mkdir AhoyDTU
   sudo chown pi:pi AhoyDTU/
   git clone https://github.com/PaeserBastelstube/AhoyDTU4RPi.git AhoyDTU
   ```
4. Install Middleware with standard install-parameter and without any special security configurations
   ```code
   sudo apt-get install -y nginx php-fpm php-yaml php-mysql mariadb-server
   ```
5. Install the Smart-Meter `Volkszaehler`
   ```code
   cd /home
   sudo mkdir volkszaehler
   sudo chown pi:pi volkszaehler
   git clone https://github.com/volkszaehler/volkszaehler.org.git volkszaehler
   ```
6. AhoyDTU based on python and need some python-modules, later more ...

#  
# Configuration instructions
AhoyDTU and the various middleware components require individual specific configuration.


## Create a PYTHON virtual environment and install Python modules
Important: Debian 12 follows the recommendation of [`PEP 668`]
(https://peps.python.org/pep-0668/)  
Now, python is configured as "externally-managed-environment" !
- You have to use a python virtual environment. See: `https://docs.python.org/3/library/venv.html`
- You can install and manage python libs via `pip` in this virtual environment!

```code
cd /home/AhoyDTU
python3 -m venv ahoyenv       ## create python virtual environment
```
If you need to start individual python scripts, activate the virtual environment with:  
```code
source ahoyenv/bin/activate
```

### AhoyDTU requires the installation of certain python libraries:
```code
ahoyenv/bin/python3 -m pip install --upgrade crcmod datetime paho-mqtt phpserialize pyRF24 requests ruamel-yaml SunTimes sysv-ipc
```

If you have trouble to install `pyRF24`, please use the following workaround:
```code
git clone --recurse-submodules https://github.com/nRF24/pyRF24.git
cd pyRF24
  ahoyenv/bin/python3 -m pip install . -v
cd ..
```
This step takes a while!


### Finally, check all installed `python modules`:
```code
ahoyenv/bin/python3 -m pip list         ## check: search for pyRF24
Package            Version
------------------ ---------
certifi            2025.7.14
charset-normalizer 3.4.2
crcmod             1.7
DateTime           5.5
idna               3.10
jdcal              1.4.1
paho-mqtt          2.1.0
phpserialize       1.3
pip                23.0.1
pyrf24             0.5.0
pytz               2025.2
requests           2.32.5
ruamel.yaml        0.18.15
ruamel.yaml.clib   0.2.12
setuptools         66.1.1
suntimes           1.1.2
sysv-ipc           1.1.0
typing_extensions  4.14.1
tzlocal            5.3.1
urllib3            2.5.0
zope.interface     7.2
```


## configure NGINX WebServer
To configure NGINX and PHP FastCGI Process Manager, we need two sym-links from you installed AhoyDTU directory into NGINX and PHP system configuration. But first, you have to remove the NGINX standard configuration!

```code
cd /home/AhoyDTU
sudo rm /etc/nginx/sites-enabled/default
sudo ln -fs etc/php-fpm/AhoyDTU.conf /etc/php/8.2/fpm/pool.d/AhoyDTU.conf
sudo ln -fs etc/nginx/AhoyDTU /etc/nginx/sites-enabled/AhoyDTU
sudo nginx -t
```
Please change the PHP-version-directory if necessary.  
No configuration is required for the standard installation of Mosquito.

Finally, we have to restart the system-services for nginx and php8.2-fpm.  
```code
sudo systemctl restart nginx php8.2-fpm
```

## Test your Web-Server
Now you can test your NGINX WebServer. Start your prefered browser on your PC, Tablet or mobile-phone and call your Raspberry-pi, like in this example:
```code
http://Raspberry-PI.fritz.box
```

If you have an trouble, have a look on NGINX log files:
```code
tail /var/log/nginx/access.log /var/log/nginx/error.log
```

## Install PHP Composer
Our `Smart-Meter Volkszaehler` calls some PHP-scripts and these PHP-scripts require specific PHP-libraries. In order to use these PHP-libraries, they must be installed using the PHP-package-manager `Composer`. We first install the PHP-package-manager.
```code
cd /tmp
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### install with composer for https://www.php.net/manual/en/book.shmop.php


# Additional installation on Volkszaehler Smart Meter

## Installation of certain PHP package libraries 
```code
cd /home/volkszaehler/
composer install
# If we get various error messages, so we add some “ignore” parameters and start the installation process again:
composer install --ignore-platform-req=ext-dom --ignore-platform-req=ext-xml --ignore-platform-req=ext-xmlwriter
composer require php-mqtt/client --ignore-platform-req=ext-dom --ignore-platform-req=ext-xml --ignore-platform-req=ext-xmlwriter
```


