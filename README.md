[![CC BY-NC-SA 4.0][cc-by-nc-sa-shield]][cc-by-nc-sa]

This work is licensed under a
[Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License][cc-by-nc-sa].

[![CC BY-NC-SA 4.0][cc-by-nc-sa-image]][cc-by-nc-sa]

[cc-by-nc-sa]: https://creativecommons.org/licenses/by-nc-sa/4.0/deed.de
[cc-by-nc-sa-image]: https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png
[cc-by-nc-sa-shield]: https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-lightgrey.svg

---
# AhoyDTU for Raspberry-Pi with NGINX WebServices

This project is partial copied from ***ahoy (lumapu)*** (https://github.com/lumapu/ahoy/)  
***ahoy (lumapu)*** is licensed under
[Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License][cc-by-nc-sa].

Since the beginning of 2024, the maintenance of ahoy (lumapu) has focused on programming ESP microcontrollers.
Development for Raspberry-PI controllers has been frozen. 
In this project, the development of AhoyDTU for Raspberry PI processors is continued independently.
For this purpose, the ahoy (lumapu) version v0.8.155 was copied and adapted to use it on a Linux system with NGINX Web-Services.

The goal is to collect data from one ore more hoymiles microinverters and present the data on a web-server (NGINX).  
As an additional feature, it is planed to control the hoymiles microinverter for zero export, to reduce consume of any power when using a battery.

## Installation-Requirements
1. `/tmp` must be available for all users. AhoyDTU stores log- and other temp files in this directory.
2. AhoyDTU based on python and need some python-modules, later more ...
3. AhoyDTU based on some specific linux packages, you have to install this packages:
   ```code
   sudo apt install cmake git python3-dev libboost-python-dev python3-pip python3-rpi.gpio php-yaml
   ```
4. AhoyDTU must be installed in a non-user HOME, because the Web-Server process cannot read HTML or CGI-scripts from a USER-HOME directory.  
   We prefere to install this project in: `/home/AhoyDTU` :
   ```code
   cd /home
   sudo mkdir AhoyDTU
   sudo chown pi:pi AhoyDTU/
   ```

## Download AhoyDTU from github
```code
git clone https://github.com/PaeserBastelstube/AhoyDTU4RPi.git AhoyDTU
```

Important: Debian 12 follows the recommendation of [`PEP 668`]
(https://peps.python.org/pep-0668/)  
Now, python is configured as "externally-managed-environment" !
- You have to use a python virtual environment. See: `https://docs.python.org/3/library/venv.html`
- You can install and manage python libs via `pip` in this virtual environment!

Create a PYTHON virtual environment
```code
cd AhoyDTU
python3 -m venv ahoyenv       ## create python virtual environment
source ahoyenv/bin/activate   ## activate the virtual environment
```

AhoyDTU requires the installation of certain python libraries:
```code
ahoyenv/bin/python3 -m pip install paho-mqtt crcmod suntimes requests pyRF24 ruamel-yaml
```

If you have trouble to install `pyRF24`, please use the following workaround:
```code
git clone --recurse-submodules https://github.com/nRF24/pyRF24.git
cd pyRF24
  ahoyenv/bin/python3 -m pip install . -v
cd ..
```
This step takes a while!


Finally, check all installed `python modules`:
```code
ahoyenv/bin/python3 -m pip list         ## check: search for pyRF24
Package            Version
------------------ ---------
certifi            2025.4.26
charset-normalizer 3.4.2
crcmod             1.7
idna               3.10
jdcal              1.4.1
paho-mqtt          2.1.0
pip                20.3.4
pkg-resources      0.0.0
pyrf24             0.5.0
pytz               2025.2
requests           2.32.3
ruamel.yaml        0.18.10
ruamel.yaml.clib   0.2.12
setuptools         44.1.1
suntimes           1.1.2
typing-extensions  4.14.0
tzlocal            5.3.1
urllib3            2.4.0
```

# configure AhoyDTU
To configure "AhoyDTU" for your own purposes, you need the "ahoy.yml" file.
Please create a copy of "ahoy.yml.example", rename it to "ahoy.yml" and edit the necessary statements
```code
cp ahoy.yml.example ahoy.yml
vi ahoy.yml
```

## start AhoyDTU manualy
```code
cd ahoy
/home/AhoyDTU/ahoyenv/bin/python3 -um hoymiles --log-transactions --verbose  --config ahoy.yml
```

## start AhoyDTU as user (system) service
```code
systemctl --user enable /home/AhoyDTU/ahoy/ahoy.service  # to register AhoyDTU as (system) service
systemctl --user status ahoy.service                     # to check status of service
systemctl --user start ahoy.service                      # start AhoyDTU as (system) service

for maintenance:
systemctl --user restart ahoy.service
systemctl --user stop ahoy.service
systemctl --user disable ahoy.service
```


# Web-Server (NGINX)
Ahoy on ESP8266 or ESP32 includes its own web server for presentation hoymiles inverter data.
In this project, we integrate NGINX Web-Services to present this data from hoymiles invertes.
To do this, we need the PHP FastCGI Process Manager, too.

## Installation of NGINX Web-Server
```code
sudo apt-get install -y nginx php-fpm php-yaml
```

Than, we need to change the ownership of all files in "www" directory and have
to integrate (link) our AhoyDTU service into NGINX and check NGINX configuration
```code
cd /home/AhoyDTU
sudo chown -R www-data www
sudo rm /etc/nginx/sites-enabled/default
sudo ln -fs $(pwd)/etc/nginx/AhoyDTU /etc/nginx/sites-enabled/AhoyDTU
sudo nginx -t
```

Finally, we must restart NGINX Service
```code
sudo systemctl restart nginx
```

# Test your Web-Server
Now you can test, if your your WebServer can display your AhoyDTU startpage. Start your prefered browser and load the URL like this example:
```code
http://rpi-zero2wh.fritz.box
```

If you have an trouble, have a look on NGINX log files:
```code
tail /var/log/nginx/access.log /var/log/nginx/error.log
```
