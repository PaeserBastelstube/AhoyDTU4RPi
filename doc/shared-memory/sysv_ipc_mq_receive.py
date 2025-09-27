# sysv_ipc_mq_receive.py
################################################################################
# System-V IPC for Python - Semaphores, Shared Memory and Message Queues
# https://semanchuk.com/philip/sysv_ipc/
# Message-Queue - RECEIVE
################################################################################
# IPC_CREX is shorthand for IPC_CREAT | IPC_EXCL, they are flags used when 
# creating IPC objects and identified by key. If the IPC object (semaphore, ...) 
# with that key already exists, the call raises an ExistentialError.
################################################################################

import os                      # for open file
import time                    # for time.sleep
from datetime import datetime  # for datetime.now() and strftime
import sysv_ipc                # for System-V FTOK, Semaphore and Shared-Memory

# create specific file to generate a known FTOK key
filePathName = '/tmp/example'                  # def a filepath / filename
fd = os.open(filePathName, flags=os.O_CREAT)   # create file-handle
os.close(fd)                                   # close file-handle

# generate the known FTOK key from specifiv file
#   ftok(path, id, [silence_warning = False])
#   Note that ftok() has limitations, and this function will issue a warning 
#   to that effect unless silence_warning is True. 
#   "id" must be in hex - hex(0x30) = dec(48) = chr("0") 
ftokKey = sysv_ipc.ftok(filePathName, 0x30, silence_warning = True)
print(f"{ftokKey=} hex=0x{ftokKey:08x}")

# start loop
#   check, if message-queue (ipc_mq) exists - will be init by receiver
#   if ipc_mq exists, 
#     open ipc_mq
#     send TimeStamp
#     close ipc_mq
#   wait 1 sec

try: # create new message-queue (ipc_mq)
    ipc_mq = sysv_ipc.MessageQueue(ftokKey, sysv_ipc.IPC_CREX)
    print(f"  New message-queue with {ipc_mq.id=} and {ipc_mq.max_size=} created")
except sysv_ipc.ExistentialError: # message-queue does exists allready
    ipc_mq = sysv_ipc.MessageQueue(ftokKey)
    print(f"  existing message-queue found with {ipc_mq.current_messages} messages in queue")
    while (ipc_mq.current_messages > 0):    # remove old messages from queue
        ipc_mq.receive(False)               # wait if there's no messages

try:
  while True:
    try:
        # Receives a message from the queue, returning a tuple of (message, type)
        dataReceived = ipc_mq.receive(False)[0].decode()
        print(f"  len={dataReceived.__sizeof__()} type={type(dataReceived)} {dataReceived=}")
    except sysv_ipc.BusyError:
        pass  # Does absolutely nothing
        # print(f"  ERROR: cannot receive data from message-queue")
    except sysv_ipc.ExistentialError:   # The queue no longer exists
        print ("The queue no longer exists")
        exit()
    time.sleep(1)
except KeyboardInterrupt:
  ipc_mq.remove()      # remove message queue

print(f"\nTo control System-V IPC objects, please call 'ipcs'\n"
      f"or remove message-queue with 'ipcrm -r ID'\n"
      f"or look at: 'cat /proc/sysvipc/msg'")

