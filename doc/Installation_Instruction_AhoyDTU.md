[![CC BY-NC-SA 4.0][cc-by-nc-sa-shield]][cc-by-nc-sa]

This work is licensed under a
[Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License][cc-by-nc-sa].

[![CC BY-NC-SA 4.0][cc-by-nc-sa-image]][cc-by-nc-sa]

[cc-by-nc-sa]: https://creativecommons.org/licenses/by-nc-sa/4.0/deed.de
[cc-by-nc-sa-image]: https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png
[cc-by-nc-sa-shield]: https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-lightgrey.svg

---
# Installation Instructions <br>for AhoyDTU on Raspberry-Pi with <br> NGINX-WebServices and Volkszaehler-Smart-Meter
The already known `Ahoy(lumapu) on ESP8266 or ESP32` includes its own Web-Server to present
the `Hoymiles inverter data` and to configure the system environment in a Web-Browser.  
In this project, we use a `NGINX Web-Server` to show the `Hoymiles inverter runtime data`,
to configure and to control the `AhoyDTU environment`, as well as 
to store and to analyse historcal data in a `Volkszaehler-Smart-Meter`
(https://github.com/volkszaehler/volkszaehler.org) environment.  

For all of this, we need some additional middleware:
* `PYTHON virtual environment` to run specific AhoyDTU scripts
* `NGINX Web-Service` to communicate with a human user and to store runtime data in Volkszaehler
* `PHP FastCGI Process Manager` to run specific AhoyDTU-PHP and Volkszaehler scripts
* `System-V IPC (Shared-Memory with Semaphore and Message-Queue)` for data exchange between PHP and AhoyDTU scripts
* `MariaDB` to store data permanent in a database

## Basics Instructions
1. `/tmp` must be available for all users, AhoyDTU stores log- and other temp files in this directory. This is standard behavior on Linux.
2. This project based on some specific linux packages, you have to install this packages first:
   ```code
   sudo apt-get update
   sudo apt-get -y full-upgrade
   sudo apt-get -y install cmake git python3-dev libboost-python-dev python3-pip python3-rpi.gpio
   ```
3. Activate SPI Interface on Raspi-HW
   ```code
   sudo raspi-config
	3 Interface Options
	I3 SPI
	Select YES (use TAB Key)
   ```   
   To check the SPI-Interface use „ls“-command:
   ```code
   $ ls -al /dev/spi*
   crw-rw---- 1 root spi 153, 0 Aug 20 14:43 /dev/spidev0.0
   crw-rw---- 1 root spi 153, 1 Aug 20 14:43 /dev/spidev0.1
   ```
4. AhoyDTU (based on PYTHON language) must be installed in a non-user HOME directory,<br> we prefere to install this project in: `/home/AhoyDTU`:
   ```code
   cd /home
   sudo mkdir AhoyDTU
   sudo chown pi:pi AhoyDTU/
   git clone https://github.com/PaeserBastelstube/AhoyDTU4RPi.git AhoyDTU
   ```
5. Install Middleware with standard install-parameter and (first) without any special security configurations
   ```code
   sudo apt-get install -y nginx php-fpm php-yaml php-mysql mariadb-server
   ```
6. Install the Smart-Meter `Volkszaehler`
   ```code
   cd /home
   sudo mkdir volkszaehler
   sudo chown pi:pi volkszaehler
   git clone https://github.com/volkszaehler/volkszaehler.org.git volkszaehler
   ```
7. AhoyDTU based on python.<br> You need a PYTHON (private) virtual environment and some specific python-modules - later more ...

---

# AhoyDTU Configuration instructions
AhoyDTU and the various middleware components require individual specific configuration.

## Create a PYTHON virtual environment
`Important:` Debian 12 follows the recommendation of [`PEP 668`]
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

## Installation of certain PYTHON libraries
AhoyDTU based on PYTHON scripts, they need a set of specific PYTHON libraries:
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


## Finally, check all installed `python modules`:
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

---
# NGINX WebServer configuration instructions
To configure `NGINX` and `PHP FastCGI Process Manager`, we need two sym-links from your 
installed AhoyDTU directory into `NGINX and PHP system environment`. 
But first, you have to remove the standard NGINX configuration!
```code
sudo rm /etc/nginx/sites-enabled/default
sudo ln -fs /home/AhoyDTU/etc/php-fpm/AhoyDTU.conf /etc/php/8.2/fpm/pool.d/AhoyDTU.conf
sudo ln -fs /home/AhoyDTU/etc/nginx/AhoyDTU /etc/nginx/sites-enabled/AhoyDTU
sudo nginx -t
```
Please change the `PHP-version-directory` if necessary.  
Finally, we have to restart the system-services for `nginx` and `php8.2-fpm`.  
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
In next step, you can install `Volkszaehler` environment to store historic data
* [ Installation Instruction Volkszaehler environment ](Installation_Instruction_Volkszaehler.md)
