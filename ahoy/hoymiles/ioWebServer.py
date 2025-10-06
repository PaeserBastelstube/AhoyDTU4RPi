#!/usr/bin/env python3
# -*- coding: utf-8 -*-

################################################################################
# ioWebServer.py
# class to handle communication with (PHP) WebServer
# 
# methods:
#  def __init__(self, web_config, log_obj, config_fn = "/tmp"):   # init class
#  def disco(self):                 # close shared-memory and remove Semaphore
#  def reset_max_values(self):      # reset Max Values
#  def getMaxValues (self, data):   # get Max Values
#  def checkOutput(self):           # check every 5 min, all data in output
# 
#  def eventArray (self, InfoCommand, data):
#      manage event queue with last 50 entries
#
#  def SaveData4PHP (self, inv_ser, InfoCommand, data):
#      save data to shared-memory, controled by Semaphore 
#
#  def receiveInverterCommand (self):   # receive inverter control commands
# 
################################################################################

from datetime import datetime
import json
import sysv_ipc  # for System-V FTOK, Semaphore and Shared-Memory
from enum import IntEnum
from phpserialize import unserialize
import struct

################################################################################

class PowerLimitControlType(IntEnum):
    limit_nonpersistent_absolute = 0x0000   # 0UL   - AbsolutNonPersistent
    limit_nonpersistent_relative = 0x0001   # 1UL   - RelativNonPersistent
    limit_persistent_absolute    = 0x0100   # 256UL - AbsolutPersistent
    limit_persistent_relative    = 0x0101   # 257UL - RelativPersistent

################################################################################

class WebServer():
  """ communication with WebServices over System-V IPC objects
  to handle maximum values
  to send data over Shared Memory by using a Semaphore
  to receive data from IPC Message-Queue
  """
  dataToFile   = {}
  AtMidnight   = False
  AtSunrise    = False
  AtSunset     = False
  NotAvailable = False
  MaxValues    = False

  def __init__(self, web_config, log_obj, config_fn = "/tmp"):
    self.log_obj = log_obj
    if web_config.get('InverterReset', None):
       reset_config = web_config.get('InverterReset')
       if reset_config.get('AtMidnight', False) == True:
          self.AtMidnight = True
       if reset_config.get('AtSunrise', False) == True:
          self.AtSunrise = True
       if reset_config.get('AtSunset', False) == True:
          self.AtSunset = True
       if reset_config.get('NotAvailable', False) == True:
          self.NotAvailable = True
       if reset_config.get('MaxValues', False) == True:
          self.MaxValues = True
    self.reset_max_values()

    # generate the known FTOK key from specifiv file
    #   ftok(path, id, [silence_warning = False])
    #   Note that ftok() has limitations, and this function will issue a warning
    #   to that effect unless silence_warning is True.
    #   "id" must be in hex - hex(0x30) = dec(48) = chr("0")
    self.ftokKey = sysv_ipc.ftok(config_fn, 0x30, silence_warning = True)
    self.log_obj.debug(f"System-V: create ftokKey={self.ftokKey} hex=0x{self.ftokKey:08x}")

    self.ipc_flags = sysv_ipc.IPC_CREX   # specify whether to create a new semaphore or open an existing one
    self.ipc_mode = 0o644                # 
    self.sem_initial_value = 3           # 
    try: # Creates a new semaphore or opens an existing one.
        sem_id = sysv_ipc.Semaphore(self.ftokKey, self.ipc_flags, self.ipc_mode, self.sem_initial_value)
    except sysv_ipc.ExistentialError: # semaphore exists already
        sem_id = sysv_ipc.Semaphore(self.ftokKey)
    sem_id.release()                     # close Semaphore
    sem_id.remove()                      # destry old Semaphore

    try:                                 # create new message-queue (ipc_mq)
        self.ipc_mq = sysv_ipc.MessageQueue(self.ftokKey, self.ipc_flags)
        self.log_obj.debug(f"System-V: new message-queue with {self.ipc_mq.id=} and {self.ipc_mq.max_size=} created")
    except sysv_ipc.ExistentialError:    # message-queue does exists allready
        self.ipc_mq = sysv_ipc.MessageQueue(self.ftokKey)
        self.log_obj.debug(f"System-V: existing message-queue found with {self.ipc_mq.current_messages} messages in queue")
        while (self.ipc_mq.current_messages > 0):    # remove old messages from queue
            self.ipc_mq.receive(False)               # wait if there's no messages

  def disco(self):
      if (self.ipc_mq):
        self.ipc_mq.remove()      # remove message queue

  def reset_max_values(self):
      self.max_values = {'max_temp':0,'max_temp_ts':0,'max_power':0,'max_power_ts':0, 'strings':[]}
 
  def getMaxValues (self, data):
      # calulate max values
      if (self.max_values['max_temp'] < data['temperature']):
          self.max_values['max_temp']    = data['temperature']
          self.max_values['max_temp_ts'] = data['time']

      if 'phases' in data:
          all_phase_power = 0
          for phase in data['phases']:
              all_phase_power += phase['power']            # add power of all phases
          if (self.max_values['max_power'] < all_phase_power):
              self.max_values['max_power'] = all_phase_power
              self.max_values['max_power_ts'] = data['time']

      if 'strings' in data:
          for ii, string in enumerate(data['strings']):
            # print (f"{ii=} {self.max_values['strings']=} {string['power']=}")
            if ii not in range(len(self.max_values['strings'])):
               self.max_values['strings'].append(string['power'])
            else:
              if (self.max_values['strings'][ii] < string['power']):
                self.max_values['strings'][ii] = string['power']
      self.dataToFile["MaxValues"] = self.max_values
      #self.log_obj.debug(f"SaveData4PHP(MaxValues): {self.dataToFile["MaxValues"]=}")
   
  def checkOutput(self):
    if "DebugDecodeAny" in self.dataToFile and \
       "InverterDevInform_Simple" in self.dataToFile and \
       "InverterDevInform_All" in self.dataToFile:
       return False
    else:
       self.log_obj.debug(f"SaveData4PHP(checkOutput): missing elements - restart main-init function")
       return True

  def eventArray (self, InfoCommand, data): # InfoCommand == "AlarmData" or "AlarmUpdate"
	# add data to FiFo-Array (max 50 pos.
    if InfoCommand in self.dataToFile:
        self.dataToFile[InfoCommand].update(data)
    else:
        self.dataToFile[InfoCommand] = data

    while len(self.dataToFile[InfoCommand]) > 50:
        myFirstKey = next(iter(self.dataToFile[InfoCommand].keys()))
        del(self.dataToFile[InfoCommand][myFirstKey])
    # self.log_obj.debug(f"SaveData4PHP: len={len(self.dataToFile[InfoCommand])} {self.dataToFile[InfoCommand]=}")

  def SaveData4PHP (self, InfoCommand, data, inv_ser):
    if (InfoCommand.startswith("Alarm")):           # AlarmData == 17(0x11) + AlarmUpdate == 18(0x12)
        self.eventArray(InfoCommand, data)          # FiFo - max 50 pos.
    elif (InfoCommand == "RealTimeRunData_Debug"):  # RealTimeRunData_Debug == 11 (0x0B)
        self.getMaxValues(data)                     # extract max.Values
        self.dataToFile[InfoCommand] = data
    else:
        self.dataToFile[InfoCommand] = data

    # self.log_obj.debug(f"SaveData4PHP: try to save data to System-V IPC")
    try: # Creates a new semaphore or opens an existing one.
        sem_id = sysv_ipc.Semaphore(self.ftokKey, self.ipc_flags, self.ipc_mode, self.sem_initial_value)
    except sysv_ipc.ExistentialError: # semaphore exists already
        # now, open this existing Semaphore and read ".o_time" value
        # for unused Semaphore, ".o_time" value is zero
        # for used Semaphore, ".o_time" value is a timestamp
        sem_id = sysv_ipc.Semaphore(self.ftokKey)
        self.log_obj.debug(f"SaveData4PHP: old Semaphore hex=0x{sem_id.key:08x} semid={sem_id.id} "
                      f"found with TimeStamp: {sem_id.o_time}")
        if sem_id.o_time == 0:         # destry forgotten Semaphore and loop
            sem_id.release()
            sem_id.remove()
    else:
        sem_id.release()               # Initializing sem.o_time to nonzero value
        ## self.log_obj.debug(f"SaveData4PHP: Semaphore hex=0x{sem_id.key:08x} semid={sem_id.id} "
        ##               f"successfully init with TimeStamp: {sem_id.o_time}")

        SHM_Data = json.dumps(         # Data Serialization for writing in SHM
            {"saveTS"          : datetime.now().strftime('%d.%m.%YT%H:%M:%S'),
             "ts_last_success" : datetime.now().timestamp(),
              inv_ser          : {** self.dataToFile}})

        self.log_obj.debug(f"SaveData4PHP: type={InfoCommand} SHM with: len={len(SHM_Data)}")
        ## self.log_obj.debug(f"SaveData4PHP: type={InfoCommand} SHM with: len={len(SHM_Data)} value={SHM_Data}")
        # create shared memeory object (SHM)
        # Flag IPC_CREX is shorthand for IPC_CREAT | IPC_EXCL, they are used when
        # creating IPC objects and identified by key. If the IPC object (semaphore, ...)
        # with that key already exists, the call raises an ExistentialError.
        #
        # With flags set to default=0, the module attempts to open an existing IPC object,
        # identified by key and raises a ExistentialError if that IPC object doesn't exist.

        try:
            shdMemObj = sysv_ipc.SharedMemory(self.ftokKey, self.ipc_flags, self.ipc_mode, len(SHM_Data))
        except sysv_ipc.ExistentialError:
            shdMemObj = sysv_ipc.SharedMemory(self.ftokKey)     # SHM must exist allready
        # check Size
        if len(SHM_Data) != shdMemObj.size:
            shdMemObj.detach()
            shdMemObj.remove()
            shdMemObj = sysv_ipc.SharedMemory(self.ftokKey, self.ipc_flags, self.ipc_mode, len(SHM_Data))

        shdMemObj.write(SHM_Data)
        shdMemObj.detach()
        ## self.log_obj.debug(f"SaveData4PHP:  Shared-Memory created: "
        ##                    f"{shdMemObj.id=} len={len(shdMemData)} value={shdMemData}")
        sem_id.remove()                # remove Semaphore

  def receiveInverterCommand (self):
    dataReceived = False
    try:
        # Receives a message from the queue, returning a tuple of (message, type)
        dataReceived = self.ipc_mq.receive(False)[0].decode()
        self.log_obj.debug(f"System-V(message-queue): len={dataReceived.__sizeof__()} type={type(dataReceived)} {dataReceived=}")
    except sysv_ipc.BusyError:
        # self.log_obj.debug(f"System-V: no data in message-queue")
        pass  # Does absolutely nothing
        return False
    except sysv_ipc.ExistentialError:   # The queue no longer exists
        self.log_obj.debug("System-V: The queue no longer exists")
        self.disco()
        return False

    if (dataReceived and isinstance(dataReceived, str)):     # inverter controll command available
        sysv_ipc_uns = unserialize(dataReceived.encode())    # convert all items to dict-string
        sysv_ipc_dict = {key.decode(): val.decode() if isinstance(val, bytes) else val for key, val in sysv_ipc_uns.items()}
        self.log_obj.debug(f"System-V(command): len={sysv_ipc_dict.__sizeof__()} {sysv_ipc_dict=}")

        # available inverter controll commands
        # {"id":0,"cmd":"power","val":"0"}                          # Inverter switch off
        # {"id":0,"cmd":"power","val":"1"}                          # Inverter switch on
        # {"id":0,"cmd":"restart","val":"0"}                        # Inverter restart
        # {"id":0,"cmd":"limit_nonpersistent_absolute","val":"870"} # nonpersistent --> no
        # {"id":0,"cmd":"limit_nonpersistent_relative","val":"66"}  # relative --> in %
        # {"id":0,"cmd":"limit_persistent_absolute","val":"870"}    # absolute --> in Watt
        # {"id":0,"cmd":"limit_persistent_relative","val":"66"}     # persistent --> Keep limit over inverter restart = yes

        # now prepare command and send to inverter-queue
        if   sysv_ipc_dict["cmd"] == "power" and int(sysv_ipc_dict["val"]) == 0: # Inverter switch off
            return struct.pack('>H', 0x0000)
        elif sysv_ipc_dict["cmd"] == "power" and int(sysv_ipc_dict["val"]) == 1: # Inverter switch on
            return struct.pack('>H', 0x0100)
        elif sysv_ipc_dict["cmd"] == "restart":                                  # Inverter restart
            return struct.pack('>H', 0x0200)
        elif sysv_ipc_dict["cmd"].startswith("limit_"):                          # set limit
            payload_limit = struct.pack('>H', int(sysv_ipc_dict["val"]))
            payload_type  = struct.pack('>H', PowerLimitControlType[sysv_ipc_dict["cmd"]])
            return {'id' : sysv_ipc_dict["id"], 
                    'cmd' : struct.pack('>H', 0x0b00) + payload_limit + payload_type}
        return False

################################################################################

