#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Hoymiles output plugin library
"""

import socket
import logging
from datetime import datetime, timezone
from hoymiles.decoders import StatusResponse, Response_InverterDevInform_All
from hoymiles import HOYMILES_TRANSACTION_LOGGING, HOYMILES_VERBOSE_LOGGING

class OutputPluginFactory:
    def __init__(self, **params):
        """
        Initialize output plugin

        :param inverter_ser: The inverter serial
        :type inverter_ser: str
        :param inverter_name: The configured name for the inverter
        :type inverter_name: str
        """

        self.inverter_ser  = params.get('inverter_ser', '')
        self.inverter_name = params.get('inverter_name', None)

    def store_status(self, response, **params):
        """
        Default function

        :raises NotImplementedError: when the plugin does not implement store status data
        """
        raise NotImplementedError('The current output plugin does not implement store_status')

class InfluxOutputPlugin(OutputPluginFactory):
    """ Influx2 output plugin """
    api = None

    def __init__(self, url, token, **params):
        """
        Initialize InfluxOutputPlugin
        https://influxdb-client.readthedocs.io/en/stable/api.html#influxdbclient

        The following targets must be present in your InfluxDB. This does not
        automatically create anything for You.

        :param str url: The url to connect this client to. Like http://localhost:8086
        :param str token: Influx2 access token which is allowed to write to bucket
        :param org: Influx2 org, the token belongs to
        :type org: str
        :param bucket: Influx2 bucket to store data in (also known as retention policy)
        :type bucket: str
        :param measurement: Default measurement-prefix to use
        :type measurement: str
        """
        super().__init__(**params)

        try:
            from influxdb_client import InfluxDBClient
        except ModuleNotFoundError:
            ErrorText1 = f'Module "influxdb_client" for INFLUXDB necessary.'
            ErrorText2 = f'Install module with command: python3 -m pip install influxdb_client'
            print(ErrorText1, ErrorText2)
            logging.error(ErrorText1)
            logging.error(ErrorText2)
            exit(1)

        self._bucket = params.get('bucket', 'hoymiles/autogen')
        self._org = params.get('org', '')
        self._measurement = params.get('measurement', f'inverter,host={socket.gethostname()}')

        with InfluxDBClient(url, token, bucket=self._bucket) as self.client:
             self.api = self.client.write_api()
             if HOYMILES_VERBOSE_LOGGING:
                logging.info(f"Influx: connect to DB {url} initialized")

    def disco(self, **params):
        self.client.close()          # Shutdown the client
        return

    # def store_status(self, response, **params):
    def store_status(self, data, inv_ser, **params):
        """
        Publish StatusResponse object

        :param hoymiles.decoders.StatusResponse response: StatusResponse object
        :type response: hoymiles.decoders.StatusResponse
        :param measurement: Custom influx measurement name
        :type measurement: str or None

        :raises ValueError: when response is not instance of StatusResponse
        """

        # if not isinstance(response, StatusResponse):
        #    raise ValueError('Data needs to be instance of StatusResponse')
        if not 'phases' in data or not 'strings' in data:
           raise ValueError('DICT need key "inverter_ser" and "inverter_name"')

        # data = response.__dict__()     # convert response-parameter into python-dict

        measurement = self._measurement + f',location={data["inverter_ser"]}'

        data_stack = []

        time_rx = datetime.now()
        if 'time' in data and isinstance(data['time'], datetime):
            time_rx = data['time']

        # InfluxDB uses UTC
        utctime = datetime.fromtimestamp(time_rx.timestamp(), tz=timezone.utc)

        # InfluxDB requires nanoseconds
        ctime = int(utctime.timestamp() * 1e9)

        if HOYMILES_VERBOSE_LOGGING:
            logging.info(f'InfluxDB: utctime: {utctime}')

        # AC Data
        phase_id = 0
        for phase in data['phases']:
            data_stack.append(f'{measurement},phase={phase_id},type=voltage value={phase["voltage"]} {ctime}')
            data_stack.append(f'{measurement},phase={phase_id},type=current value={phase["current"]} {ctime}')
            data_stack.append(f'{measurement},phase={phase_id},type=power value={phase["power"]} {ctime}')
            data_stack.append(f'{measurement},phase={phase_id},type=Q_AC value={phase["reactive_power"]} {ctime}')
            data_stack.append(f'{measurement},phase={phase_id},type=frequency value={phase["frequency"]:.3f} {ctime}')
            phase_id = phase_id + 1

        # DC Data
        string_id = 0
        for string in data['strings']:
            data_stack.append(f'{measurement},string={string_id},type=voltage value={string["voltage"]:.3f} {ctime}')
            data_stack.append(f'{measurement},string={string_id},type=current value={string["current"]:3f} {ctime}')
            data_stack.append(f'{measurement},string={string_id},type=power value={string["power"]:.2f} {ctime}')
            data_stack.append(f'{measurement},string={string_id},type=YieldDay value={string["energy_daily"]:.2f} {ctime}')
            data_stack.append(f'{measurement},string={string_id},type=YieldTotal value={string["energy_total"]/1000:.4f} {ctime}')
            data_stack.append(f'{measurement},string={string_id},type=Irradiation value={string["irradiation"]:.2f} {ctime}')
            string_id = string_id + 1

        # Global
        if data['event_count'] is not None:
            data_stack.append(f'{measurement},type=total_events value={data["event_count"]} {ctime}')
        if data['powerfactor'] is not None:
            data_stack.append(f'{measurement},type=PF_AC value={data["powerfactor"]:f} {ctime}')
        data_stack.append(f'{measurement},type=Temp value={data["temperature"]:.2f} {ctime}')
        if data['yield_total'] is not None:
            data_stack.append(f'{measurement},type=YieldTotal value={data["yield_total"]/1000:.3f} {ctime}')
        if data['yield_today'] is not None:
            data_stack.append(f'{measurement},type=YieldToday value={data["yield_today"]/1000:.3f} {ctime}')
        data_stack.append(f'{measurement},type=Efficiency value={data["efficiency"]:.2f} {ctime}')

        if HOYMILES_VERBOSE_LOGGING:
            logging.debug(f'INFLUX data to DB: {data_stack}')
            pass
        self.api.write(self._bucket, self._org, data_stack)

class MqttOutputPlugin(OutputPluginFactory):
    """ Mqtt output plugin """
    client = None

    def __init__(self, config, on_message, **params):
        """
        Initialize MqttOutputPlugin

        :param host: Broker ip or hostname (defaults to: 127.0.0.1)
        :type host: str
        :param port: Broker port
        :type port: int (defaults to: 1883)
        :param user: Optional username to login to the broker
        :type user: str or None
        :param password: Optional passwort to login to the broker
        :type password: str or None
        :param topic: Topic prefix to use (defaults to: hoymiles/{inverter_ser})
        :type topic: str

        :param paho.mqtt.client.Client broker: mqtt-client instance
        :param str inverter_ser: inverter serial
        :param hoymiles.StatusResponse data: decoded inverter StatusResponse
        :param topic: custom mqtt topic prefix (default: hoymiles/{inverter_ser})
        :type topic: str
        """
        super().__init__(**params)

        try:
            import paho.mqtt.client as mqtt
        except ModuleNotFoundError:
            ErrorText1 = f'Module "paho.mqtt.client" for MQTT-output necessary.'
            ErrorText2 = f'Install module with command: python3 -m pip install paho-mqtt'
            print(ErrorText1, ErrorText2)
            logging.error(ErrorText1)
            logging.error(ErrorText2)
            exit(1)

        # For paho-mqtt 2.0.0, you need to set callback_api_version.
        # self.client = mqtt.Client(callback_api_version=mqtt.CallbackAPIVersion.VERSION1)
        self.client = mqtt.Client(mqtt.CallbackAPIVersion.VERSION2)

        if config.get('useTLS',False):
           self.client.tls_set()
           self.client.tls_insecure_set(config.get('insecureTLS',False))
        self.client.username_pw_set(config.get('user', None), config.get('password', None))

        last_will = config.get('last_will', None)
        if last_will:
            lw_topic = last_will.get('topic', 'last will hoymiles')
            lw_payload = last_will.get('payload', 'last will')
            self.client.will_set(str(lw_topic), str(lw_payload))

        self.client.connect(config.get('host', '127.0.0.1'), config.get('port', 1883))
        self.client.loop_start()

        self.topic = config.get('sub_topic', "")  # Topic for subscribe messages
        self.qos   = config.get('QoS', 0)         # Quality of Service
        self.ret   = config.get('Retain', True)   # Retain Message

        # connect own (PAHO) callback functions
        self.client.on_connect = self.mqtt_on_connect
        self.client.on_message = on_message

    def mqtt_on_connect(self, client, userdata, flags, reason_code, properties):
        """
        The callback called when the broker reponds to our connection request.
        https://eclipse.dev/paho/files/paho.mqtt.python/html/client.html#paho.mqtt.client.Client.on_connect
        Parameters:    
            client         – the client instance for this callback
            userdata       – the private user data as set in Client() or user_data_set()
            connect_flags  – the flags for this connection
            reason_code    – the connection reason code received from the broken.
            properties     – the MQTT v5.0 properties received from the broker.
        """
        if flags.session_present:
           logging.info("flags.session_present")
        if reason_code == 0:                    # success connect
           if HOYMILES_VERBOSE_LOGGING:
              logging.info(f"MQTT: connect to Broker established: {self.client.host}:{self.client.port} as user {self.client.username}")
        if reason_code > 0:                     # error processing
           logging.error(f'MQTT connect to broker failed: {reason_code}')

    def disco(self, **params):
        """
            disconnect mqtt when press CTRL-C
        """
        self.client.loop_stop()    # Stop loop 
        self.client.disconnect()   # disconnect
        return

    def info2mqtt(self, mqtt_payload):
        """
        https://eclipse.dev/paho/files/paho.mqtt.python/html/client.html#paho.mqtt.client.Client.publish
        Publish a message on a topic.
            This causes a message to be sent to the broker and subsequently from the broker to any clients subscribing to matching topics.
        Parameters:
            topic (str) – The topic that the message should be published on.
            payload     – The actual message to send. If not given, or set to None a zero length message will be used. 
                          Passing an int or float will result in the payload being converted to a string representing that number. 
            qos (int)   – The quality of service level to use.
            retain (bool) – If set to true, the message will be set as the “last known good”/retained message for the topic.
            properties  – the MQTT v5.0 properties to be included.
        """
        if HOYMILES_VERBOSE_LOGGING:
            logging.info(f"info2mqtt: {self.topic=} {mqtt_payload=}")
        for mqtt_key in mqtt_payload:
            self.client.publish(f"{self.topic}/{mqtt_key}", mqtt_payload[mqtt_key], self.qos, self.ret)
        return

    def store_status(self, InfoCommand, data, inv_ser, **params):
        """
        Publish StatusResponse object

        :param hoymiles.decoders.StatusResponse response: StatusResponse object
        :param topic: custom mqtt topic prefix (default: hoymiles/{inverter_ser})
        :type topic: str

        :raises ValueError: when response is not instance of StatusResponse
        """

        if data is None:
            logging.warn("OUTPUT-MQTT: received data object is empty")
            return

        topic = f'{data.get("inverter_ser", "no_topic")}'

        if HOYMILES_TRANSACTION_LOGGING:
           logging.info(f'MQTT: {InfoCommand=} {topic=} {data=}')

        if InfoCommand == "InverterDevInform_Simple":      # 0x00 - Hard/Software Version
            logging.debug(f"MQTT: {data['FLD_PART_NUM']=} {data['FLD_HW_VERSION']=} {data['FLD_GRID_PROFILE_CODE']=} {data['FLD_GRID_PROFILE_VERSION']=}")
            self.client.publish(f'{topic}/FLD_PART_NUM', data["FLD_PART_NUM"], self.qos, self.ret)
            self.client.publish(f'{topic}/FLD_HW_VERSION', data["FLD_HW_VERSION"], self.qos, self.ret)
            self.client.publish(f'{topic}/FLD_GRID_PROFILE_CODE', data["FLD_GRID_PROFILE_CODE"], self.qos, self.ret)
            self.client.publish(f'{topic}/FLD_GRID_PROFILE_VERSION', data["FLD_GRID_PROFILE_VERSION"], self.qos, self.ret)

        elif InfoCommand == "InverterDevInform_All":       # 0x01 - Firmware
            logging.debug(f"MQTT: Firmware version {data['FW_ver_maj']}.{data['FW_ver_min']}.{data['FW_ver_pat']}, "
                          f"build at {data['FW_build_dd']:>02}/{data['FW_build_mm']:>02}/{data['FW_build_yy']}T"
                          f"{data['FW_build_HH']:>02}:{data['FW_build_MM']:>02}, "
                          f"Bootloader version {data['BL_VER']}")

            payload = f"{data['FW_ver_maj']}.{data['FW_ver_min']}.{data['FW_ver_pat']}"
            self.client.publish(f'{topic}/FirmwareVersion', payload , self.qos, self.ret)

            payload = f"{data['FW_build_dd']:>02}/{data['FW_build_mm']:>02}/{data['FW_build_yy']}T{data['FW_build_HH']:>02}:{data['FW_build_MM']:>02}"
            self.client.publish(f'{topic}/FirmwareBuild_at', payload, self.qos, self.ret)

            payload = f"{data['BL_VER']}"
            self.client.publish(f'{topic}/bootloaderVersion', payload, self.qos, self.ret)

        elif InfoCommand == "GridOnProFilePara":           # 0x02 - GridOnProFilePara
            logging.debug(f"MQTT: {data['gridData']=}")
            self.client.publish(f'{topic}/GridOnProFilePara', data['gridData'], self.qos, self.ret)

        elif InfoCommand == "SystemConfigPara":            # 0x05 - SystemConfigPara
            logging.debug(f"MQTT: {data=}")
            #self.client.publish(f'{topic}/GridOnProFilePara', data['gridData'], self.qos, self.ret)

        elif InfoCommand == "RealTimeRunData_Debug":       # 0x0B - StatusResponse
            # Global Head
            if data['time'] is not None:
               logging.debug(f"MQTT: Time: {data['time'].strftime('%d.%m.%YT%H:%M:%S')}")
               self.client.publish(f'{topic}/time', data['time'].strftime("%d.%m.%YT%H:%M:%S"), self.qos, self.ret)

            # Global
            if data['yield_today'] is not None:
               logging.debug(f"MQTT: yield_today: {data['yield_today']}")
               self.client.publish(f'{topic}/yield_today', data['yield_today'], self.qos, self.ret)

            if data['yield_total'] is not None:
               logging.debug(f"MQTT: yield_total: {data['yield_total']}")
               self.client.publish(f'{topic}/yield_total', data['yield_total'], self.qos, self.ret)

            if data['efficiency'] is not None:
               logging.debug(f"MQTT: efficiency: {data['efficiency']}")
               self.client.publish(f'{topic}/efficiency', data['efficiency'], self.qos, self.ret)

            if data['powerfactor'] is not None:
               logging.debug(f"MQTT: powerfactor: {data['powerfactor']}")
               self.client.publish(f'{topic}/powerfactor', data['powerfactor'], self.qos, self.ret)

            if data['event_count'] is not None:
               logging.debug(f"MQTT: event_count: {data['event_count']}")
               self.client.publish(f'{topic}/total_events', data['event_count'], self.qos, self.ret)

            if data['temperature'] is not None:
               logging.debug(f"MQTT: temperature: {data['temperature']}")
               self.client.publish(f'{topic}/temperature', data['temperature'], self.qos, self.ret)

            # AC Data
            phase_id = 0
            phase_sum_power = 0
            if data['phases'] is not None:
                for phase in data['phases']:
                    self.client.publish(f'{topic}/emeter/{phase_id}/voltage', phase['voltage'], self.qos, self.ret)
                    self.client.publish(f'{topic}/emeter/{phase_id}/current', phase['current'], self.qos, self.ret)
                    self.client.publish(f'{topic}/emeter/{phase_id}/power', phase['power'], self.qos, self.ret)
                    self.client.publish(f'{topic}/emeter/{phase_id}/Q_AC', phase['reactive_power'], self.qos, self.ret)
                    self.client.publish(f'{topic}/emeter/{phase_id}/frequency', phase['frequency'], self.qos, self.ret)
                    phase_id = phase_id + 1
                    phase_sum_power += phase['power']

            # DC Data
            string_id = 0
            string_sum_power = 0
            if data['strings'] is not None:
                for string in data['strings']:
                    if 'name' in string:
                        string_name = string['name'].replace(" ","_")
                    else:
                        string_name = string_id
                    self.client.publish(f'{topic}/emeter-dc/{string_name}/voltage', string['voltage'], self.qos, self.ret)
                    self.client.publish(f'{topic}/emeter-dc/{string_name}/current', string['current'], self.qos, self.ret)
                    self.client.publish(f'{topic}/emeter-dc/{string_name}/power', string['power'], self.qos, self.ret)
                    self.client.publish(f'{topic}/emeter-dc/{string_name}/YieldDay', string['energy_daily'], self.qos, self.ret)
                    self.client.publish(f'{topic}/emeter-dc/{string_name}/YieldTotal', string['energy_total']/1000, self.qos, self.ret)
                    self.client.publish(f'{topic}/emeter-dc/{string_name}/Irradiation', string['irradiation'], self.qos, self.ret)
                    string_id = string_id + 1
                    string_sum_power += string['power']

        elif InfoCommand == "AlarmcwData":     # 0x11 - AlarmData
            logging.debug(f"MQTT: AlarmData: {data}")

        elif InfoCommand == "AlarmUpdate":     # 0x12 - AlarmUpdate
            logging.debug(f"MQTT: {data['inv_alarm_num']=} {data['inv_alarm_txt']=} {data['inv_alarm_cnt']=} {data['inv_alarm_stm']=} {data['inv_alarm_etm']=}")
            self.client.publish(f'{topic}/inv_alarm_num', data["inv_alarm_num"], self.qos, self.ret)
            self.client.publish(f'{topic}/inv_alarm_txt', data["inv_alarm_txt"], self.qos, self.ret)
            self.client.publish(f'{topic}/inv_alarm_cnt', data["inv_alarm_cnt"], self.qos, self.ret)
            self.client.publish(f'{topic}/inv_alarm_stm', data["inv_alarm_stm"], self.qos, self.ret)
            self.client.publish(f'{topic}/inv_alarm_etm', data["inv_alarm_etm"], self.qos, self.ret)

        else:
             raise ValueError('Data needs to be instance of StatusResponse or a instance of Response_InverterDevInform_All')

class VolkszaehlerOutputPlugin(OutputPluginFactory):
    def __init__(self, vz_config, **params):
        """
        Initialize VolkszaehlerOutputPlugin with VZ-config

        use Python Requests Module:
        Makes a request to a web page and print the response text
        https://requests.readthedocs.io/en/latest/user/advanced/
        """
        super().__init__(**params)

        try:
            import requests
        except ModuleNotFoundError:
            # ErrorText1 = f'Module "requests" and "time" for VolkszaehlerOutputPlugin necessary.'
            ErrorText1 = f'Module "requests" for VolkszaehlerOutputPlugin necessary.'
            ErrorText2 = f'Install module with command: python3 -m pip install requests'
            print(ErrorText1, ErrorText2)
            logging.error(ErrorText1)
            logging.error(ErrorText2)
            exit(1)

        # The Session object allows you to persist certain parameters across requests.
        self.session = requests.Session()

        self.ts = 0
        self.inv_ser = ""
        self.vz_inverters = dict()
        for inverter_in_vz_config in vz_config:
            enable   = inverter_in_vz_config.get('enable', False)
            serial   = inverter_in_vz_config.get('serial', False)
            suffix   = inverter_in_vz_config.get('suffix', '')
            url      = inverter_in_vz_config.get('url', 'http://localhost/middleware/')
            channels = inverter_in_vz_config.get('channels', [])

            if suffix != "":
                suffix = f"_{suffix}"

            chs = dict()
            for channel in channels:
                ch_type = channel.get('type', False)
                ch_uid  = channel.get('uid', False)
                if ch_type and ch_uid:
                    chs[ch_type] = ch_uid

            # create class object to send VZ data
            if enable and serial and len(chs) > 0:
                if HOYMILES_VERBOSE_LOGGING:
                   logging.info(f"Volkszaehler: init connection object for {serial} to {url}")
                self.vz_inverters[serial] = {"session" : self.session, "suffix" : suffix, "url" : url, "channel" : chs}

    def ser_exists(self, ser):
        ## if inv_ser in self.vz_inverters:      # check, if inverter-serial-number in list of vz_inverters
        if len(self.vz_inverters) > 0 and self.vz_inverters[ser]:
            return True
        else:
            return False

    def disco(self, **params):
        self.session.close()            # close all connections
        return

    def vz_publish(self, ctype, value):
        if not ctype in self.vz_inverters[self.inv_ser]["channel"]:
            logging.debug(f'ctype \"{ctype}\" not found in ahoy.yml')
            return

        ## print (self.vz_inverters[self.inv_ser]["channel"][ctype])
        uid = self.vz_inverters[self.inv_ser]["channel"][ctype]
        if not uid:
            logging.debug(f'ctype \"{ctype}\" has no configured value: uid')
            return

        url = f'{self.vz_inverters[self.inv_ser]["url"]}/data/{uid}.json?operation=add&ts={self.ts}&value={value}'
        if HOYMILES_VERBOSE_LOGGING:
            logging.info(f'VZ-url: {url}')

        try:
            r = self.session.get(url)
            if r.status_code == 404:
                logging.critical('VZ-DB not reachable, please check "middleware"')
            if r.status_code == 400:
                logging.critical('UUID not configured in VZ-DB')
            elif r.status_code != 200:
               raise ValueError(f'Transmit result {url}')
        except ConnectionError as e:
            raise ValueError(f'Could not connect VZ-DB {type(e)} {e.keys()}')
        return

    def store_status(self, infoCommand, data, inv_ser, **params):
        """
        Publish StatusResponse object

        :param hoymiles.decoders.StatusResponse response: StatusResponse object

        :raises ValueError: when response is not instance of StatusResponse
        """
  
        if   infoCommand == "InverterDevInform_Simple":   # 0x00
            return
        elif infoCommand == "InverterDevInform_All":      # 0x01
            return
        elif infoCommand == "GridOnProFilePara":          # 0x02
            return
        elif infoCommand == "HardWareConfig":             # 0x03
            return
        elif infoCommand == "SimpleCalibrationPara":      # 0x04
            return
        elif infoCommand == "SystemConfigPara":           # 0x05
            return
        elif infoCommand == "RealTimeRunData_Debug":      # 0x0b (11)
            pass
        elif infoCommand == "RealTimeRunData_Reality":    # 0x0c (12)
            return
        elif infoCommand == "RealTimeRunData_A_Phase":    # 0x0d (13)
            return
        elif infoCommand == "RealTimeRunData_B_Phase":    # 0x0e (14)
            return
        elif infoCommand == "RealTimeRunData_C_Phase":    # 0x0f (15)
            return
        elif infoCommand == "AlarmData":                  # 0x11 (17)
            return
        elif infoCommand == "AlarmUpdate":                # 0x12 (18)
            return
        elif infoCommand == "RecordData":                 # 0x13 (19)
            return
        elif infoCommand == "InternalData":               # 0x14 (20)
            return
        elif infoCommand == "GetLossRate":                # 0x15 (21)
            return
        elif infoCommand == "GetSelfCheckState":          # 0x1e (30)
            return
        else:
            raise ValueError(f"Unknown infoCommand: {infoCommand} - no output is sent")
            return

        #ts = int(round(data['time'].timestamp() * 1000))
        self.ts = int(round(data['time'] * 1000))   # VZ need to know the time of "data"
        self.inv_ser = inv_ser

        # self.vz_inverters[serial] = {"session" : self.session, "suffix" : suffix, "url" : url, "channel" : chs)
        suffix = self.vz_inverters[self.inv_ser]["suffix"]

        # AC Data
        phase_id = 0
        if 'phases' in data:
          for phase in data['phases']:
            self.vz_publish(f'AC-Voltage{suffix}',     phase['voltage'])
            self.vz_publish(f'AC-Current{suffix}',     phase['current'])
            self.vz_publish(f'AC-Power{suffix}',       phase['power'])
            self.vz_publish(f'Reactive-Power{suffix}', phase['reactive_power'])
            self.vz_publish(f'Frequency{suffix}',      phase['frequency'])
            phase_id = phase_id + 1

        # DC Data
        string_id = 0
        if 'strings' in data:
          for string in data['strings']:
            self.vz_publish(f'DC-Voltage{string_id}{suffix}',      string['voltage'])
            self.vz_publish(f'DC-Current{string_id}{suffix}',      string['current'])
            self.vz_publish(f'DC-Power{string_id}{suffix}',        string['power'])
            self.vz_publish(f'DC_Energy_Today{string_id}{suffix}', string['energy_daily'])
            self.vz_publish(f'DC_Energy_Total{string_id}{suffix}', string['energy_total'])
            self.vz_publish(f'DC_Irradiation{string_id}{suffix}',  string['irradiation'])
            string_id = string_id + 1

        # Global
        if 'event_count' in data:
            self.vz_publish(f'Event-Count{suffix}', data['event_count'])
        if 'powerfactor' in data:
            self.vz_publish(f'Power-Factor{suffix}', data['powerfactor'])
        if 'temperature' in data:
            self.vz_publish(f'Temperature{suffix}', data['temperature'])
        if 'yield_total' in data:
            self.vz_publish(f'Yield-Total{suffix}', data['yield_total'])
        if 'yield_today' in data:
            self.vz_publish(f'Yield-Today{suffix}', data['yield_today'])
        if 'efficiency' in data:
            self.vz_publish(f'Efficiency{suffix}',  data['efficiency'])

        """
        # eBZ = elektronischer Basiszähler (Stromzähler)
        if '1_8_0' in data:
            self.vz_publish(f'eBZ-import', data['1_8_0'])
        if '2_8_0' in data:
            self.vz_publish(f'eBZ-export', data['2_8_0'])
        if '16_7_0' in data:
            self.vz_publish(f'eBZ-power',  data['16_7_0'])
        """

