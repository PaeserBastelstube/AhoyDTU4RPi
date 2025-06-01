# AhoyDTU
AhoyDTU for Raspberry-Pi with NGINX WebServices
===============================================

This project is a partial copy of https://github.com/lumapu/ahoy/

Since the beginning of 2024, Ahoy has only been programmed for ESP processors. 
For this project, version v0.8.155 was copied and adapted to use on a Raspberry-Pi. 

The goal is to use a web-server (NGINX) and zero power consumption when using a battery.

Installation-Requirements
-------------------------
1. AhoyDTU must be installed in a non-user HOME, because the Web-Server process cannot read HTML or API scripts from this directories.
   We prefere `/home/AhoyDTU` to install this project.
2. `/tmp` is available for all users. AhoyDTU stores LOG- and other temp files in this dir.
3. AhoyDTU based on PYTHON and some PYTHON modules, later more
4. you need some specific linux applications

```code
sudo apt install cmake git python3-dev libboost-python-dev python3-pip python3-rpi.gpio
```

Installation-Instruction
------------------------
Download AhoyDTU from github into directory `/home/AhoyDTU`
```code
cd /home
git clone https://github.com/PaeserBastelstube/AhoyDTU.git
```

Important: Debian 12 follows the recommendation of [`PEP 668`]
(https://peps.python.org/pep-0668/) - now, PYTHON is configured as "externally-managed-environment" !
- You cann't install python libs via `pip`!
- You have to use a python virtual environment `https://docs.python.org/3/library/venv.html`

Create a PYTHON virtual environment
```code
cd AhoyDTU
python3 -m venv ahoyenv       ## create python virtual environment
source ahoyenv/bin/activate   ## activate the virtual environment
```

Now you have to install nessasarry python libraries:
```code
python3 -m pip install paho-mqtt crcmod PyYAML suntimes requests

git clone --recurse-submodules https://github.com/nRF24/pyRF24.git
cd pyRF24
  python3 -m pip install . -v
  python3 -m pip list         ## check: search for pyRF24
cd ..
```

Package            Version
------------------ ---------
DateTime           5.5
ruamel.yaml        0.18.10
ruamel.yaml.clib   0.2.12
zope.interface     7.2




Web-Server (NGINX)
==================
Ahoy on ESP8266 or ESP32 includes an own Web-Server to present hoymiles inverter data.
In this project we include NGINX Web-Services for present this data from hoymiles invertes.

Installation NGINX
------------------
```code
sudo apt-get install -y nginx
```
now we have to link our AhoyDTU Service into NGINX
```code
cd /home/AhoyDTU
sudo ln -fs $(pwd) /etc/nginx/sites-enabled/AhoyDTU
```

