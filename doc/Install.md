[![CC BY-NC-SA 4.0][cc-by-nc-sa-shield]][cc-by-nc-sa]

This work is licensed under a
[Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License][cc-by-nc-sa].

[![CC BY-NC-SA 4.0][cc-by-nc-sa-image]][cc-by-nc-sa]

[cc-by-nc-sa]: https://creativecommons.org/licenses/by-nc-sa/4.0/deed.de
[cc-by-nc-sa-image]: https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png
[cc-by-nc-sa-shield]: https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-lightgrey.svg

---
# Installation Instructions for AhoyDTU on Raspberry-Pi with (NGINX) WebServices, (Mosquitto) MQTT-Broker and (Volkszaehler) Smart-Meter

## Installation-Requirements
1. `/tmp` must be available for all users. AhoyDTU stores log- and other temp files in this directory.
2. AhoyDTU based on python and need some python-modules, later more ...
3. AhoyDTU with NGINX WebServices based on some specific linux packages. You have to install this packages:
   ```code
   sudo apt-get update
   sudo apt-get -y full-upgrade
   sudo apt-get install cmake git python3-dev libboost-python-dev python3-pip python3-rpi.gpio php-yaml nginx php-fpm
   ```
4. AhoyDTU must be installed in a non-user HOME directory, because the Web-Server process cannot read HTML or PHP(CGI)-scripts from a USER-HOME directory.  
   We prefere to install this project in: `/home/AhoyDTU`:
   ```code
   cd /home
   sudo mkdir AhoyDTU
   sudo chown pi:pi AhoyDTU/
   ```

## Download AhoyDTU from github
```code
git clone https://github.com/PaeserBastelstube/AhoyDTU4RPi.git AhoyDTU
```

## Create a PYTHON virtual environment
Important: Debian 12 follows the recommendation of [`PEP 668`]
(https://peps.python.org/pep-0668/)  
Now, python is configured as "externally-managed-environment" !
- You have to use a python virtual environment. See: `https://docs.python.org/3/library/venv.html`
- You can install and manage python libs via `pip` in this virtual environment!

```code
cd AhoyDTU
python3 -m venv ahoyenv       ## create python virtual environment
```
If you need to start individual python scripts, activate the virtual environment with:  
```code
source ahoyenv/bin/activate
```

## AhoyDTU requires the installation of certain python libraries:
```code
ahoyenv/bin/python3 -m pip install --upgrade paho-mqtt crcmod requests pyRF24 ruamel-yaml SunTimes datetime
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
pip                23.0.1
pyrf24             0.5.0
pytz               2025.2
requests           2.32.5
ruamel.yaml        0.18.15
ruamel.yaml.clib   0.2.12
setuptools         66.1.1
suntimes           1.1.2
typing_extensions  4.14.1
tzlocal            5.3.1
urllib3            2.5.0
zope.interface     7.2
```


# NGINX WebServer, Mosquitto-MQTT-Broker and Volkszaehler-Smart-Meter
The allready known 'Ahoy on ESP8266 or ESP32' includes its own web server for presentation hoymiles inverter data.  
In this project, we use NGINX Web-Services to control the AhoyDTU and present the data from the hoymiles inverters.  
To do this, we need additional PHP FastCGI Process Manager, too.  
For data exchange between AhoyDTU (Python) and NGINX WebService (PHP and PHP-FPM) we use a “Mosquitto MQTT broker”.

## Middelware Installation
NGINX WebServer, PHP FastCGI Process Manager and Mosquitto
mariadb-server will need for (Volkszaehler) Smart-Meter
```code
sudo apt-get install -y nginx php-fpm php-yaml php-mysql mosquitto mosquitto-clients mariadb-server
```

## configure NGINX
To configure NGINX and PHP FastCGI Process Manager, we need two sym-links from our AhoyDTU directory into NGINX and PHP configuration
```code
cd /home/AhoyDTU
sudo rm /etc/nginx/sites-enabled/default
sudo ln -fs etc/php-fpm/AhoyDTU.conf /etc/php/8.2/fpm/pool.d/AhoyDTU.conf
sudo ln -fs etc/nginx/AhoyDTU /etc/nginx/sites-enabled/AhoyDTU
sudo nginx -t
```
Please change the PHP-version-directory if necessary.
In the standard installation, Mosquitto requires no configuration.

Finally, we have to restart the system-services for nginx and php8.2-fpm.  
If you have any private 'mosquitto' configurations, restart is nessasary.
```code
sudo systemctl restart nginx php8.2-fpm
or
sudo systemctl restart nginx php8.2-fpm mosquitto
```

## Test your Web-Server
Now you can test, if your your WebServer can display your AhoyDTU startpage. Start your prefered browser and load the URL like this example:
```code
http://Raspberry-PI.fritz.box
```

If you have an trouble, have a look on NGINX log files:
```code
tail /var/log/nginx/access.log /var/log/nginx/error.log
```

If NGINX works to control AhoyDTU, now we can configure our environment and inverters:

## Konfiguration und Start der Datenbank „MariaDB“
Die Betriebsdaten der AhoyDTU sollen mit Hilfe einer Volkszähler-Instanz gespeichert und ausgewertet werden.
Zur Speicherung wird die Datenbank MariaDB verwendet, sie wurde bereits installiert.

## Installation der Volkszähler-Instanz
Die Volkszähler-Software wird von github heruntergeladen und im Verzeichnis /home installiert.
Zur Nutzung durch den Webserver wird dieses Verzeichnis nach /var/www verlinkt.
```code
cd /home
sudo mkdir volkszaehler
sudo chown pi:pi volkszaehler
git clone https://github.com/volkszaehler/volkszaehler.org.git volkszaehler
```

## PHP Composer installieren
Innerhalb der Volkszähler Instanz laufen PHP-Scripte. Diese PHP-Scripte benötigen spezifische PHP-Bibliotheken. Damit diese PHP - Bibliotheken genutzt werden können, sind sie mit Hilfe des PHP Paketma-nager Composer zu installieren. Wir Installieren zuerst den PHP Paketmanager Composer.
```code
cd /tmp
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```
Anschließend werden die PHP Pakete installiert:
```code
cd /home/volkszaehler/
composer install
```
Wir erhalten verschiedenen Fehlermeldungen, daher werden einige „ignore“ Parameter angefügt und der Installationsprozess noch einmal gestartet:
```code
composer install --ignore-platform-req=ext-dom --ignore-platform-req=ext-xml --ignore-platform-req=ext-xmlwriter
```
