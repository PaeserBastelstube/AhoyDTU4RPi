# Auswahl des Verfahrens zur Datenübertragung in einem spezifischen Projekt

## Einleitung
Das zu entwickelnde Projekt besitzt mehrere unabhängige Bestandteile (Prozesse):
* Konfiguration des Projekts in der Web-GUI (NGINX-Prozess mit Hilfe vieler sequenziellen PHP-FPM Prozesse) 
* Einlesen der Sensor-Betriebsdaten von den Sensoren (PYTHON-Prozess)
* Darstellung der Sensor-Betriebsdaten in der Web-GUI (NGINX-Prozess mit Hilfe vieler sequenziellen PHP-FPM Prozesse) 
* Senden von Konfigurations- und Steuerungsdaten an die Sensoren (PYTHON-Prozess)

## Aufgabenstellung aus dem Projekt
* Übertragung der Konfigurationsdaten aus der Web-GUI (PHP-FPM Prozesse) an die Sensor-Steuerung (Python-Prozess)
* Übertragung der Sensor-Betriebsdaten (Python-Prozess) an die Web-GUI (PHP-FPM Prozesse).
* Logging und Steuerung der Betriebsabläufe (PHP-FPM Prozesse in kooperation mit Python-Prozess)

## Randbedingungen
* Beim ersten Start der Web-GUI stehen keine Konfigurationsdaten zur Verfügung, der Prozess zum Einlesen der Betriebsdaten läuft noch nicht. Dieser Prozess wird erst durch das Web-GUI als User-Prozess gestartet.
* Der Prozess zum Einlesen der Sensor-Betriebsdaten läuft unabhängig aller anderen Prozesse als User-Prozess. Bei besonderen Konstellationen und des Nachts wird er angehalten, in diesen Fällen müssen die vorhandenen (veralteten) Betriebsdaten in der Web-GUI angezeigt werden.
* 

## Überlegung
Es stellt sich die Frage, wie die Daten von einem Prozess zum anderen Prozess übertragen werden könnte.
Prinziziell stehen folgende Mechanismen zur Interprozesskommunikation (IPC) zur Verfügung:
* Shared Memory (gemeinsamer Speicher)
* Pipes (Datenleitungen)
* Nachrichtenübermittlung (Message Queues, Sockets)
* Remote Procedure Calls (RPCs)
* Synchronisationsmechanismen wie Semaphoren und Mutexe

Weiterhin stehen folgende Arten der Kommunikation und zur Datenübertragung zur Diskussion:
* Speicherung der Daten in einer Datei und Lesen der Datei von einem zweiten Prozess.
* Speichern und Zurücklesen in einer Datenbank
* Kommunikation über MQTT, REST oder OPC-UA

## Lösungsansätze
Im ersten Lösungsansatz wurden die Betriebsdaten in einer Datei gespeichert und von den PHP-FPM Prozessen ausgelesen.  
Im zweiten Lösungsansatz wurde MQTT implementiert.  
Als dritter Lösungsansatz wurde die Datenübertragung über Shared-Memory untersucht. Hierbei wurde die Implementierung über System-V erfolgreich ungesetzt. Die Posix-Implementierung wurde nicht beendet.

## Lösung über System-V
[Python-Script zum Schreiben der Daten](./sysV_out.py)  
[Python-Script zum Lesen der Daten](./sysV_in.py)  
[PHP-Script zum Schreiben der Daten](./sysV_out.php)  
[PHP-Script zum Lesen der Daten](./sysV_in.php)  
