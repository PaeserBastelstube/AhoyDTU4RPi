[![CC BY-NC-SA 4.0][cc-by-nc-sa-shield]][cc-by-nc-sa]

This work is licensed under a
[Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License][cc-by-nc-sa].

[![CC BY-NC-SA 4.0][cc-by-nc-sa-image]][cc-by-nc-sa]

[cc-by-nc-sa]: https://creativecommons.org/licenses/by-nc-sa/4.0/deed.de
[cc-by-nc-sa-image]: https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png
[cc-by-nc-sa-shield]: https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-lightgrey.svg

---
# AhoyDTU for Raspberry-Pi with WebServices

This project is partial copied from ***ahoy (lumapu)*** (https://github.com/lumapu/ahoy/)  
***ahoy (lumapu)*** is licensed under
[Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License][cc-by-nc-sa].

Since the beginning of 2024, the development of ahoy (lumapu) has focused on programming ESP microcontrollers. Unfortunately, development for Raspberry PI controllers has been frozen.  
This project continues the development of the AhoyDTU for Raspberry PI controllers independently. For this purpose, ahoy (lumapu) version v0.8.155 was copied and adapted for use on a Linux system with NGINX web services.  
An MQTT broker is used for interprocess communication between the AhoyDTU (based on Python) and the web services (based on PHP).

The project pursues the following goals:
* Collect data from one or more hoymiles microinverters and display it on an NGINX web service
* Permanently store data in a Volkszaehler instance (https://github.com/volkszaehler/volkszaehler.org) and make it available for individual analysis
* As an additional feature, it is planned to reduce zero feed-in during battery operation. This requires a corresponding sensor on the electricity meter.

---

Seit Anfang 2024 konzentriert sich die Wartung von ahoy (lumapu) auf die Programmierung von ESP-Mikrocontrollern. Die Entwicklung für Raspberry-PI-Controller wurde leider eingestellt.  
In diesem Projekt wird die Entwicklung der AhoyDTU für Raspberry-PI-Controller unabhängig fortgesetzt. Zu diesem Zweck wurde die ahoy (lumapu) Version v0.8.155 kopiert und für die Nutzung auf einem Linux-System mit NGINX-WebServices angepasst.  
Zur Interprozesskommunikation zwischen der AhoyDTU (basierend auf Python) und den WebServices (basierend auf PHP) dient ein MQTT-Broker.

Folgende Ziele verfolgt das Projekt:
* Daten von einem oder mehreren hoymiles-Mikrowechselrichtern zu sammeln und auf einem NGINX-WebServices darzustellen
* Daten in einer Volkszähler-Instanz (https://github.com/volkszaehler/volkszaehler.org) dauerhaft zu speichern und für individuelle Auswertungen bereitzustellen
* Als zusätzliche Funktion ist es geplant, bei Batteriebetrieb eine Nulleinspeisung zu reduzieren. Hierbei muss am Stromzähler ein entsprechender Sensor vorhanden sein.

Additional Informations:
* [ Installation Instructions ](doc/Install.md)
* [ Basics and Background (in German language only) ](wiki/Grundlagen-und-Hintergrund)

