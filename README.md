# AhoyDTU for Raspberry-Pi with NGINX WebServices

This project is a partial copy of ***ahoy (lumapu)*** [https://github.com/lumapu/ahoy/]

Since the beginning of 2024, the maintenance of ahoy (lumapu) has focused on programming ESP microcontrollers.
Development for Raspberry-PI controllers has been frozen. 
In this project, the development of an AhoyDTU for Raspberry PI processors is continued independently.
For this purpose, the ahoy (lumapu) version v0.8.155 was copied and adapted for use on a Linux system using the NGINX web server.

The goal is to collect data from a hoymiles microinverter and present the data on a web server (NGINX).
As an additional feature, it is planed to control the hoymiles microinverter for zero export, to reduce consume any power when using a battery.

## Installation-Requirements
1. AhoyDTU must be installed in a non-user HOME, because the Web-Server process cannot read HTML or API scripts from USER-HOME directories.
   We prefere `/home/AhoyDTU` to install this project.
2. `/tmp` must be available for all users. AhoyDTU stores log- and other temp files in this directory.
3. AhoyDTU based on python and need some python-modules, later more ...
4. AhoyDTU need some specific linux packages
```code
sudo apt install cmake git python3-dev libboost-python-dev python3-pip python3-rpi.gpio
```

## Download AhoyDTU from github
```code
cd /home
git clone https://github.com/PaeserBastelstube/AhoyDTU.git
```

Important: Debian 12 follows the recommendation of [`PEP 668`]
(https://peps.python.org/pep-0668/) - now, python is configured as "externally-managed-environment" !
- You cann't install python libs via `pip`!
- You have to use a python virtual environment `https://docs.python.org/3/library/venv.html`

Create a PYTHON virtual environment
```code
cd AhoyDTU
python3 -m venv ahoyenv       ## create python virtual environment
source ahoyenv/bin/activate   ## activate the virtual environment
```

AhoyDTU requires the installation of certain python libraries:
c
python3 -m pip install paho-mqtt crcmod PyYAML suntimes requests

git clone --recurse-submodules https://github.com/nRF24/pyRF24.git
cd pyRF24
  python3 -m pip install . -v
  python3 -m pip list         ## check: search for pyRF24
cd ..
```

```code
Package            Version
------------------ ---------
DateTime           5.5
ruamel.yaml        0.18.10
ruamel.yaml.clib   0.2.12
zope.interface     7.2
```




# Web-Server (NGINX)
Ahoy on ESP8266 or ESP32 includes its own web server for presentation hoymiles inverter data.
In this project, we integrate NGINX Web-Services for present this data from hoymiles invertes.

## Installation NGINX
```code
sudo apt-get install -y nginx
```
Finally, we need to integrate (link) our AhoyDTU service into NGINX
```code
cd /home/AhoyDTU
sudo ln -fs $(pwd) /etc/nginx/sites-enabled/AhoyDTU
```

