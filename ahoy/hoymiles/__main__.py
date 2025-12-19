#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Hoymiles micro-inverters main application
"""

from os import environ, path
from sys import exit

import logging
from logging.handlers import RotatingFileHandler

import argparse
import struct
import re
import json
## import traceback

from ruamel.yaml import YAML
yaml = YAML(typ='rt')

import time          # for "time.sleep"
from datetime import datetime, timedelta, timezone
from suntimes import SunTimes

import hoymiles  # import paket on this place, call once: "hoymiles/__init__.py"

################################################################################
# constant values
TX_REQ_INFO =       b'\x15'
TX_REQ_DEVCONTROL = b'\x51'
ALL_FRAMES =        b'\x80'
SINGLE_FRAME =      b'\x81'
#
################################################################################
# SIGINT  = Interrupt from keyboard (CTRL + C)
# SIGTERM = Signal Handler from terminating processes
# SIGHUP  = Hangup detected on controlling terminal or death of controlling process
# SIGKILL = Signal Handler SIGKILL and SIGSTOP cannot be caught, blocked, or ignored!!
################################################################################
from signal import signal, Signals, SIGINT, SIGTERM, SIGHUP
def signal_handler(sig_num, frame):
    """ Signal Handler 
    param: signal number [signal-name]
    param: frame
    """
    signame = Signals(sig_num).name
    toPrint = f"Stop by Signal <{signame}> ({sig_num}) at: {sunset.getTimeAsString()}"
    logging.info(toPrint)
    if environ.get('TERM') is not None:
       print (f"\n{toPrint}")

    if mqtt_client:
       mqtt_client.disco()

    if influx_client:
       influx_client.disco()

    if volkszaehler_client:
       volkszaehler_client.disco()

    if web_server:
       web_server.disco()

    exit(0)

""" activate signal handler """
signal(SIGINT,  signal_handler)
signal(SIGTERM, signal_handler)
signal(SIGHUP,  signal_handler)
# signal(SIGKILL, signal_handler) # not used
################################################################################
################################################################################

class SunsetHandler:
    """ Sunset class
    to recognize the times of sunrise, sunset and to sleep at night time

    see: https://www.centron.de/tutorial/python-aktuelles-datum-und-uhrzeit-anleitung/
    """
    sunTimes      = None
    _localTz      = None
    _now          = None
    _todaySunset  = None
    _todaySunRise = None
    _loop_start   = None
    _acfn         = None
    _acfn_st      = int(datetime.now().timestamp())

    def __init__(self, acfn, sunset_config):
        if sunset_config and sunset_config.get('enabled', False):
            # calc suntimes values ## https://pypi.org/project/suntimes/
            self.sunTimes = SunTimes(longitude = float(sunset_config.get('longitude',0)),
                                      latitude = float(sunset_config.get('latitude',0)),
                                      altitude = float(sunset_config.get('altitude',0))
            )
            self._todaySunRise = self.sunTimes.riselocal(datetime.now())
            self._todaySunset  = self.sunTimes.setlocal(datetime.now())
            self._localTz      = self._todaySunRise.tzinfo
            self._acfn         = acfn  # ahoy config file name
            logging.info (f'Today Sunrise: {self._todaySunRise.strftime("%d.%m.%Y %H:%M:%S")} - '
                          f'Sunset: {self._todaySunset.strftime("%d.%m.%Y %H:%M:%S (%Z)")}')
        else:
            logging.info('Sunset disabled!')

    def getTimeAsString(self):                  # get Time as String
        return datetime.now(self._localTz).strftime("%d.%m.%Y %H:%M:%S (%Z)")

    def loop_start(self):                       # save start of main loop
        self._loop_start = datetime.now(self._localTz)
        # check for changed ahoy config file
        ##logging.info (f'###loop_start### {self._acfn_st=} - {path.getmtime(self._acfn)=}')
        if self._acfn_st < path.getmtime(self._acfn):
            logging.error (f'ERROR - ahoy config changed, while running ahoy - EXIT(15)')
            exit (15)

    def pauseMainLoop(self, loop_interval):     # pause main-loop
        _time_to_pause = loop_interval - (datetime.now(self._localTz) - self._loop_start).total_seconds()
        if _time_to_pause > 0:
           logging.info(f'MAIN-LOOP: sleep for {_time_to_pause:.2f} sec.')
           time.sleep(_time_to_pause)

    def updateTimeValues(self):
        self._now          = datetime.now(self._localTz)
        self._todaySunset  = self.sunTimes.setlocal(self._now)
        self._todaySunRise = self.sunTimes.riselocal(self._now)

    def ifSunTime(self):
        if self.sunTimes:
            self.updateTimeValues()
            if self._now > self._todaySunRise and self._now < self._todaySunset:
                return True
        return False

    def waitForSunrise(self):
        if not self.sunTimes:
            return
        self.updateTimeValues()
        _time_to_sleep = 0
        if self._now < self._todaySunRise:      # this check starts today after midnight and bevor sunrise
            _time_to_sleep = int((self._todaySunRise - self._now).total_seconds())
            _nextSunrise = self._todaySunRise
        elif self._now > self._todaySunset:     # this check starts after sunset (and bevor midnight)
            _tomorrow = self._now + timedelta(days=1)
            _nextSunrise = self.sunTimes.riselocal(_tomorrow)
            _time_to_sleep = int((_nextSunrise - self._now).total_seconds())

        if _time_to_sleep > 0:
            logging.info (f'Wait for next sunrise at {_nextSunrise.strftime("%d.%m.%Y %H:%M:%S (%Z)")}, '
                          f'sleeping for {_time_to_sleep} seconds')
            time.sleep(_time_to_sleep)
            logging.info (f'Woke up...')

    def sun_status2mqtt(self):
        """ send sunset information every day to MQTT broker """
        if not mqtt_client or not self.suntimes:
            return

        if self.suntimes:
            local_sunrise = self.suntimes.riselocal(datetime.now()).strftime("%d.%m.%YT%H:%M")
            local_sunset  = self.suntimes.setlocal(datetime.now()).strftime("%d.%m.%YT%H:%M")
            local_zone    = self.suntimes.setlocal(datetime.now()).tzinfo.key

            # TEMPLATE: info2mqtt(data2publish)
            mqtt_client.info2mqtt(
                {'dis_night_comm' : 'True', 
                  'local_sunrise' : local_sunrise, 
                   'local_sunset' : local_sunset,
                     'local_zone' : local_zone
                })
        else:
            # TEMPLATE: info2mqtt(data2publish)
            mqtt_client.info2mqtt({'dis_night_comm': 'False'})

################################################################################
################################################################################

def main_loop():
    """ Main loop """
    logging.info(f"MAIN-LOOP starts now with interval {loop_interval} sec. "
                 f"and up to {transmit_retries} retries")

    try:
        do_init = True
        while True:   # MAIN endless LOOP
            sunset.loop_start()          # save start time

            for inverter in inverters:   # querey each inverter
                if hoymiles.HOYMILES_VERBOSE_LOGGING:
                    logging.info(f"Main loop for Inverter: {inverter['name']} ({inverter['serial']})")
                # no key 'disnightcom' in dict
                if (not inverter.get('disnightcom', False)) or \
                   (inverter.get('disnightcom', False) and sunset.ifSunTime()):
                      poll_inverter(inverter, do_init, transmit_retries)
                      do_init = False
                #else:
                #    sunset.waitForSunrise() # stop work at night time
                #    continue

            if web_server:     # interact with user  frontend
              # check reset max values
              #if time.time() - t_loop_start > 6 * 60 * 60:
              #   web_server.reset_max_value()

              # check if all infos available for WebServer
              # logging.info (f"check time: {time.strftime('%M')} - {int(time.strftime('%M'),10) % 5}")
              # if (int(time.strftime("%M"),10) % 5 == 0):
              #   do_init = web_server.checkOutput()

              # read command from WebServer, if available
              inv_cmd = web_server.receiveInverterCommand()
              logging.debug (f"set Inverter command {inv_cmd=}")
              if inv_cmd:
                  inv_ser = inverters[inv_cmd['id']]['serial']
                  payload = inv_cmd['cmd']
                  logging.info(f"set Inverter: {dtu_serial=} {inv_ser=} {payload=}")

                  # build inverter request
                  request=next(
                      hoymiles.compose_esb_packet(
                          payload,                # first element from command queue - see: InfoCommands
                          mid=TX_REQ_DEVCONTROL,  # main command id - b'\x15' or b'\x51'
                          seq=SINGLE_FRAME,       # single frame if - b'\x80'
                          src=dtu_serial,
                          dst=inv_ser
                      )
                  )
                  logging.info(f"set Inverter: {request=}")
                  # send request into command queue

                  response = sendRequest(transmit_retries, request)    # send inverter request
                  if response:                    # Handle response data, if any
                      logging.info(f'set Inverter(response): {len(response)} bytes: {hoymiles.hexify_payload(response)}')
                      command_queue[inv_ser].append(
                          hoymiles.compose_send_time_payload(
                              hoymiles.InfoCommands.SystemConfigPara
                          )
                      )

            # time to pause main-loop
            sunset.pauseMainLoop(loop_interval)

    except Exception as e:
        logging.fatal('Exception catched: %s' % e)
        ## logging.fatal(traceback.print_exc())
        raise

def poll_inverter(inverter, do_init, retries):
    """
    Send/Receive command_queue, initiate status poll on inverter

    :param str inverter: inverter serial
    :param retries: tx retry count if no inverter contact
    :type retries: int
    """
    inv_ser     = inverter.get('serial')
    inv_name    = inverter.get('name')
    inv_strings = inverter.get('strings')

    if hoymiles.HOYMILES_VERBOSE_LOGGING:
        logging.info(f"poll_inverter for: {inv_name=} ({inv_ser})")
    # Command queue 
    if do_init: # this command is executed at start in the morning
      command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.InverterDevInform_Simple))   # 0x00
      command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.InverterDevInform_All))      # 0x01
      if webserver_config.get('rdGrid', None):
        command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.GridOnProFilePara))        # 0x02

      # command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.HardWareConfig))             # 0x03
      # command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.SimpleCalibrationPara))      # 0x04

      command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.SystemConfigPara))           # 0x05
      # command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.RealTimeRunData_Debug))      # 0x0b
      # command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.RealTimeRunData_Reality))    # 0x0c
      # command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.RealTimeRunData_A_Phase))    # 0x0d
      # command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.RealTimeRunData_B_Phase))    # 0x0e
      # command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.RealTimeRunData_C_Phase))    # 0x0f

      command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.AlarmData))                  # 0x11
      # command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.AlarmUpdate))                # 0x12

      # command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.RecordData))                 # 0x13
      # command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.InternalData))               # 0x14
      # command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.GetLossRate))                # 0x15
      # command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.GetSelfCheckState))          # 0x1E
      # command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.InitDataState))              # 0xFF

    # this command is executed on each run
    command_queue[inv_ser].append(hoymiles.compose_send_time_payload(hoymiles.InfoCommands.RealTimeRunData_Debug))        # 0x0b

    # Put all queued commands for current inverter on air
    while len(command_queue[inv_ser]) > 0:
        payload = command_queue[inv_ser].pop(0)    ## get first element from command queue
        logging.info(f"Poll inverter: {inv_name=} {inv_ser=} "
                     f"command={hoymiles.InfoCommands(payload[0]).name}")
        logging.debug(f"Poll inverter: {payload=}")

        # build next inverter request
        request = next(
            hoymiles.compose_esb_packet(
                payload,           # first element from command queue - see: InfoCommands
                mid=TX_REQ_INFO,   # main command id - b'\x15' or b'\x51'
                seq=ALL_FRAMES,    # single frame if - b'\x80'
                src=dtu_serial,
                dst=inv_ser
            )
        )
        logging.debug(f"Poll inverter: {request=}")
        response = sendRequest(retries, request)    # send next inverter request
        if response:                                # Handle response data, if any
            logging.debug(f'Payload: {len(response)} bytes: {hoymiles.hexify_payload(response)}')

            # get a ResponseDecoder object to decode response-payload
            decoder = hoymiles.ResponseDecoder(
                response,  
                request      = request,
                inverter_ser = inv_ser,
                strings      = inv_strings
            )

            result = decoder.decode()                          # decode response from inverter
            resultTypeName = type(result).__name__             # get class (type) name
            data = result.__dict__()                           # convert result into python-dict

            infoCommand = "DebugDecodeAny"
            if resultTypeName != infoCommand:
               infoCommand_num  = int("0x" + resultTypeName[-2:], 16)
               infoCommand = hoymiles.InfoCommands(infoCommand_num).name

            logging.debug(f"{resultTypeName=} {infoCommand=}")
            logging.info(f'Decoded: {data=}')

            # check result object for output
            if infoCommand == 'RealTimeRunData_Debug':
                logging.info(f"StatusResponse: payload contains {len(data)} elements "
                             f"(power={data['phases'][0]['power']} W - event_count={data['event_count']})")

                # when 'event_count' is changed, add AlarmUpdate-command to queue
                if data is not None and 'event_count' in data:
                    if event_message_index[inv_ser] != data['event_count']:
                       event_message_index[inv_ser]  = data['event_count']
                       logging.info(f"Alarm requested: event count changed to {data['event_count']}")

                       # add AlarmUpdate-command to queue 
                       command_queue[inv_ser].append(
                         hoymiles.compose_send_time_payload(
                           hoymiles.InfoCommands.AlarmUpdate, alarm_id=event_message_index[inv_ser]
                         )
                       )

            # sent outputs
            if web_server:
               web_server.SaveData4PHP(infoCommand, data, inv_ser)          # save data for using in NGINX

            if mqtt_client:
               mqtt_client.store_status(infoCommand, data, inv_ser)         # output to MQTT-Broker

            if influx_client:
               influx_client.store_status(infoCommand, data, inv_ser)       # output to influxDB

            if volkszaehler_client.ser_exists(inv_ser):
               volkszaehler_client.store_status(infoCommand, data, inv_ser) # output to volkszaehler

################################################################################
def sendRequest(payload_ttl, request):
    response = None
    while payload_ttl > 0: ## Send payload {ttl}-times until we get at least one reponse
        payload_ttl -= 1
        com = hoymiles.InverterTransaction(    ## create query inverter (TX) object
                radio=hmradio,
                txpower=inverter.get('txpower', None),
                dtu_ser=dtu_serial,
                inverter_ser=inv_ser,
                request=request
              )

        # Transmit next packet from tx_queue if available and wait for responses
        while com.rxtx():
            try:
                response = com.get_payload()
                payload_ttl = 0
            except Exception as e_all:
                logging.debug(f'Error while retrieving data: {e_all}')
                pass
    return response

################################################################################
################################################################################
def mqtt_on_message(mqtt_client, userdata, message):
    ''' 
    https://eclipse.dev/paho/files/paho.mqtt.python/html/client.html#paho.mqtt.client.Client.on_message
    The callback called when a message has been received on a topic that the client subscribes to.
    Parameters:
        client   – the client instance for this callback
        userdata – the private user data as set in Client() or user_data_set()
        message  – the received message. This is a class with members topic, payload, qos, retain.
    running in a thread: "paho-mqtt-client-" - important for signals and Exceptions!
    a)  when receiving topic ends with "SENSOR" for privat electricity meter
    b)  when receiving topic ends with "command" for runtime faster debugging

    Handle commands to sub_topic
        hoymiles/{inverter_ser}/command
    frame a payload and put onto command_queue
    The message must be in hexlified format
    Inverters must have mqtt.send_raw_enabled: true configured
    Use of variables:
    tttttttt gets expanded to a current int(time)

    Example injects exactly the same as we normally use to poll data:
      mosquitto -h broker -t inverter_topic/command -m 800b00tttttttt0000000500000000

    This allows for even faster hacking during runtime
    '''
    # print(f"msg-topic: {message.topic} - QoS: {message.qos}")
    # print(f"payload:  ",str(message.payload.decode("utf-8")), "\n")

##    # handle specific payload topic
##    if message.topic.endswith("SENSOR"):
##       data = yaml.safe_load(str(message.payload.decode("utf-8")))    # string to dict
##       if volkszaehler_client:
##          volkszaehler_client.store_status(data)
##       else:
##          # eBZ = elektronischer Basiszähler (Stromzähler)
##          for key in data.keys():
##              if "1_8_0" in data[key] and "2_8_0" in data[key] and "16_7_0" in data[key]:
##                 logging.info  (f"{key}- {data[key]['96_1_0']} - "
##                                f"eBZ-import: {data[key]['1_8_0']:.02f} - "
##                                f"eBZ-export: {data[key]['2_8_0']} - "
##                                f"eBZ-power: {data[key]['16_7_0']:.02f}")

    # message is a class with members topic, payload, qos, retain
    if message.topic.endswith("command"):
        p_message = message.payload.decode('utf-8').lower()

        # Expand tttttttt to current time for use in hexlified payload
        expand_time = ''.join(f'{b:02x}' for b in struct.pack('>L', int(time.time())))

        p_message = p_message.replace('tttttttt', expand_time)
        logging.info (f"MQTT-command: {message.topic=} {p_message=}")

        if (len(p_message) < 2048 and len(p_message) % 2 == 0 and re.match(r'^[a-f0-9]+$', p_message)):
            payload = bytes.fromhex(p_message)
            logging.info (f"MQTT-command for {inv_ser=}: {payload=}")

            # add command (payload) to inverter-command-queue
            if payload[0] == 0x80:                      # commands must start with \x80
                # array "command_queue[inv_ser]" will be shared to an other thread --> critical section
                command_queue[inv_ser].append(hoymiles.frame_payload(payload[1:]))
            else:
                logging.info (f"MQTT-command: must start with \x80: {payload=}")
        else:
            logging.info (f"MQTT-command to long (max length: 2048 bytes) - or contains non hex char")

def init_logging(log_config):
    """ init and prepare logging 
        Über die Angabe des Log-Levels wird festgelegt, wie dringlich eine Nachricht ist. 
        Im logging-Modul sind dabei folgende Werte festgelegt:
           CRITICAL 50
           ERROR    40
           WARNING  30
           INFO     20
           DEBUG    10
           NOTSET    0
    """
    # default values
    fn = 'hoymiles.log'
    max_log_filesize = 1000000
    max_log_files = 1
    lvl = logging.ERROR

    if log_config:     # check, if logging configured in ahoy.yml
        fn               = log_config.get('filename',         fn)
        max_log_filesize = log_config.get('max_log_filesize', max_log_filesize)
        max_log_files    = log_config.get('max_log_files',    max_log_files)
        level            = log_config.get('level',            'ERROR')

        if level == 'DEBUG':
           lvl = logging.DEBUG
        elif level == 'INFO':
           lvl = logging.INFO
        elif level == 'WARNING':
           lvl = logging.WARNING
        elif level == 'ERROR':
           lvl = logging.ERROR
        elif level == 'FATAL':
           lvl = logging.FATAL
        else:
           lvl = logging.INFO

    # define log switches
    if global_config.verbose:
       hoymiles.HOYMILES_VERBOSE_LOGGING     = True
       lvl = logging.INFO
    if global_config.log_transactions:
       hoymiles.HOYMILES_TRANSACTION_LOGGING = True
       lvl = logging.DEBUG

    # start configured logging
    logging.basicConfig(handlers=[RotatingFileHandler(fn, maxBytes=max_log_filesize, backupCount=max_log_files)], 
        format='%(asctime)s %(levelname)s: %(message)s', 
        datefmt='%Y-%m-%d %H:%M:%S.%s', level=lvl)

    if environ.get('TERM') is not None:
       print(f"==>run before starting AhoyDTU: tail -f {fn} &")

    if lvl >= 40:
        logging.critical(f'AhoyDTU-logging started for "{dtu_name}" with level: {logging.getLevelName(logging.root.level)}')
    else:
        logging.info    (f'AhoyDTU-logging started for "{dtu_name}" with level: {logging.getLevelName(logging.root.level)}')

if __name__ == '__main__':
    # read commandline parameter
    parser = argparse.ArgumentParser(description='Ahoy - Hoymiles solar inverter gateway', prog="hoymiles")
    parser.add_argument("-c", "--config-file", nargs="?", required=True,
        help="configuration file")
    parser.add_argument("--log-transactions", action="store_true", default=False,
        help="Enable transaction logging output (loglevel must be DEBUG)")
    parser.add_argument("--verbose", action="store_true", default=False,
        help="Enable detailed debug output (loglevel must be DEBUG)")
    global_config = parser.parse_args()

    # Load config file given in commandline parameter
    try:
        with open(global_config.config_file, 'r') as fh_yaml:
            cfg = yaml.load(fh_yaml)
    except FileNotFoundError:
        logging.error(f"Could not load config file: '{global_config.config_file}' - Try --help")
        exit(2)
    except yaml.YAMLError as e_yaml:
        logging.error(f"Failed to load config file: '{global_config.config_file}' - {e_yaml}")
        exit(1)

    # read all parameter from configuration-file as 'ahoy_config'
    ahoy_config = dict(cfg.get('ahoy', {}))

    # extract 'DTU' parameter
    dtu_serial  = ahoy_config.get('dtu', {}).get('serial', None)           # String
    dtu_name    = ahoy_config.get('dtu', {}).get('name', 'hoymiles-dtu')   # String

    # init and prepare logging
    init_logging(ahoy_config.get('logging', None))

    # define radio object # only one NRF24L01+ radio transceivers allowed
    radio_config = ahoy_config.get('nrf', [{}])                            # dict
    hmradio = hoymiles.HoymilesNRF(**radio_config)                         # obj

    # init WebServer client object
    web_server = None
    webserver_config = ahoy_config.get('WebServer', None)                  # dict
    if webserver_config:
        from .ioWebServer import WebServer
        web_server = WebServer(webserver_config, logging, global_config.config_file)
    #if (None != web_server):
    #      logging.info(f"WebServer init successfull!")

    # init INFLUX client object
    influx_client = None
    influx_config = ahoy_config.get('influxdb', None)
    if influx_config and influx_config.get('enabled', False):
        from .outputs import InfluxOutputPlugin
        influx_client = InfluxOutputPlugin(
                influx_config.get('url'),
                influx_config.get('token'),
                org=influx_config.get('org', ''),
                bucket=influx_config.get('bucket', None),
                measurement=influx_config.get('measurement', 'hoymiles'))

    # init VOLKSZAEHLER client object
    volkszaehler_client = None
    volkszaehler_config = ahoy_config.get('volkszaehler', {})
    # if volkszaehler_config and volkszaehler_config.get('enabled', False):
    if volkszaehler_config and len(volkszaehler_config) > 0:
        from .outputs import VolkszaehlerOutputPlugin # import VZ-class from external "outputs" file
        volkszaehler_client = VolkszaehlerOutputPlugin(volkszaehler_config)

    # init MQTT client object
    mqtt_client = None                               # create client-obj-placeholder
    mqtt_config = ahoy_config.get('mqtt', None)      # get mqtt-config, if available
    sub_topic_array = []                             # create array for subscribe-topic's
    if mqtt_config and (mqtt_config.get('enabled', False)):
       from .outputs import MqttOutputPlugin         # import MQTT-class from external "outputs" file

       # create MQTT client object with own mqtt_on_message callback funtion
       try:
           mqtt_client = MqttOutputPlugin(mqtt_config, mqtt_on_message)
       except:
           print("MQTT is requested or listening to topic is configured in ahoy-config,")
           print("but broker is not available--> exit(31)")
           exit(31)

       sub_topic = mqtt_config.get('sub_topic', None)   # get topic, if available
       if sub_topic:                                    # add subscribe-topic to array
           # sub_topic_array should contain QOS levels as well as topic names
           # sub_topic_array=[("Server1/kpi1",0),("Server2/kpi2",0),("Server3/kpi3",0)]
           sub_topic_array.append((sub_topic, mqtt_config.get('QoS',0)))

    # subscribe mqtt topic, if requested
    if mqtt_client and len(sub_topic_array) > 0:
       logging.info(f'MQTT: subscribe for topic/QoS: {sub_topic_array}')
       mqtt_client.client.subscribe(sub_topic_array)

    # init Sunset-Handler object # need MQTT object, if available
    sunset = SunsetHandler(global_config.config_file, ahoy_config.get('sunset'))   # obj

    # check 'interval' parameter in config-file
    loop_interval = int(ahoy_config.get('interval', 15))           # int
    if (loop_interval <= 0):
        logging.critical("Parameter 'loop_interval' must >= 0 - STOP(999)")
        exit(999)

    # check 'transmit_retries' parameter in config-file
    transmit_retries = ahoy_config.get('transmit_retries', 5)      # int
    if (transmit_retries <= 0):
        logging.critical("Parameter 'transmit_retries' must >= 0 - STOP(998)")
        exit(998)
    logging.debug(f"AhoyDTU: {loop_interval=} sec. - {transmit_retries=}")

    # init important runtime variables
    event_message_index = {}                            # dict
    command_queue = {}                                  # dict
    mqtt_command_topic_subs = []                        # dict

    # check inverters in config-file and init important queues
    inverters = [inverter for inverter in ahoy_config.get('inverters', [])
                 if inverter.get('enabled', True)]
    # check all inverter "name" and "serial-number" in config-file
    for inverter in inverters:
        if not 'name' in inverter:                      # check inverter "name"
           inverter['name'] = 'hoymiles'
        if not 'serial' in inverter:                    # check inverter "serial"
           logging.error("inverter without serial-number not accepted - STOP(996)")
           exit(996)

        inv_ser = inverter.get('serial', 0)             # get inverter serial number as index
        command_queue[inv_ser] = []                     # init empty inverter-command-queue
        event_message_index[inv_ser] = 0                # init event-queue with value=0

        # if send_raw_enabled, add topic to subscribe mqtt-command-queue
        if mqtt_client and inverter.get('mqtt', {}).get('send_raw_enabled', False):
           sub_topic_array.append(
             inverter.get('mqtt', {}).get('topic',
             f'hoymiles/{inv_ser}') + '/command',
             mqtt_config.get('QoS',0)
           )

    if len(inverters) == 0:
        logging.critical("no inverters configured - STOP(997)")
        exit(997)

    # start main-loop
    main_loop()

