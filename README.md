[![CC BY-NC-SA 4.0][cc-by-nc-sa-shield]][cc-by-nc-sa]

This work is licensed under a
[Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License][cc-by-nc-sa].

[![CC BY-NC-SA 4.0][cc-by-nc-sa-image]][cc-by-nc-sa]

[cc-by-nc-sa]: https://creativecommons.org/licenses/by-nc-sa/4.0/deed.de
[cc-by-nc-sa-image]: https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png
[cc-by-nc-sa-shield]: https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-lightgrey.svg

---
# AhoyDTU for Raspberry-Pi with NGINX WebServices

This project is a partial copy of ***ahoy (lumapu)*** (https://github.com/lumapu/ahoy/)  
***ahoy (lumapu)*** is licensed under a
[Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License][cc-by-nc-sa].

Since the beginning of 2024, the maintenance of ahoy (lumapu) has focused on programming ESP microcontrollers.
Development for Raspberry-PI controllers has been frozen. 
In this project, the development of an AhoyDTU for Raspberry PI processors is continued independently.
For this purpose, the ahoy (lumapu) version v0.8.155 was copied and adapted for use on a Linux system with NGINX as a web-server.

The goal is to collect data from a hoymiles microinverter and present the data on a web server (NGINX).  
As an additional feature, it is planed to control the hoymiles microinverter for zero export, to reduce consume any power when using a battery.

## Installation-Requirements
1. `/tmp` must be available for all users. AhoyDTU stores log- and other temp files in this directory.
2. AhoyDTU based on python and need some python-modules, later more ...
3. AhoyDTU need some specific linux packages
   ```code
   sudo apt install cmake git python3-dev libboost-python-dev python3-pip python3-rpi.gpio
   ```
4. AhoyDTU must be installed in a non-user HOME, because the Web-Server process cannot read HTML or API scripts from a USER-HOME directory.  
   We prefere `/home/AhoyDTU` to install this project:
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
- You cann't install python libs via `pip`!
- You have to use a python virtual environment `https://docs.python.org/3/library/venv.html`

Create a PYTHON virtual environment
```code
cd AhoyDTU
python3 -m venv ahoyenv       ## create python virtual environment
source ahoyenv/bin/activate   ## activate the virtual environment
```

AhoyDTU requires the installation of certain python libraries:
```code
python3 -m pip install paho-mqtt crcmod PyYAML suntimes requests pyRF24
```

If you have trouble to install `pyRF24`, please use the following workaround:
```code
git clone --recurse-submodules https://github.com/nRF24/pyRF24.git
cd pyRF24
  python3 -m pip install . -v
cd ..
```
This step takes a while!


Finally, check all installed `python modules`:
```code
python3 -m pip list         ## check: search for pyRF24
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
PyYAML             6.0.2
requests           2.32.3
setuptools         44.1.1
suntimes           1.1.2
typing-extensions  4.14.0
tzlocal            5.3.1
urllib3            2.4.0


history:
Package            Version
------------------ ---------
DateTime           5.5
ruamel.yaml        0.18.10
ruamel.yaml.clib   0.2.12
zope.interface     7.2
```

# configure AhoyDTU
To configure `Ahoy DTU`, the file `ahoy.yml` is required.  
Please create a copy of `ahoy.yml.example` and rename it as `ahoy.yml`.

## start AhoyDTU manualy
```code
source /home/AhoyDTU/ahoyenv/bin/activate
python3 -um hoymiles --log-transactions --verbose  --config ahoy.yml'
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

## Installation NGINX
```code
sudo apt-get install -y nginx
```
Finally, we need to integrate (link) our AhoyDTU service into NGINX and restart NGINX Service
```code
cd /home/AhoyDTU
sudo ln -fs $(pwd)/etc/nginx/AhoyDTU /etc/nginx/sites-enabled/AhoyDTU
sudo systemctl restart nginx
```

