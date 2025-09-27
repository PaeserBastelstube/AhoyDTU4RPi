# sysv_ipc_mq_send.py
################################################################################
# System-V IPC for Python - Semaphores, Shared Memory and Message Queues
# https://semanchuk.com/philip/sysv_ipc/
# Message-Queue - SEND
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

try:
  while True:
    TSnow = datetime.now().strftime('%d.%m.%YT%H:%M:%S')   # create TimeStamp

    try:
        ipc_mq = sysv_ipc.MessageQueue(ftokKey) # open existing message-queue
    except sysv_ipc.ExistentialError:           # message-queue does not exists
        print("ERROR: No Message-Queue found!")
        break
        # pass  # Does absolutely nothing
    else:
        print(f"  Send data to message-queue: len={len(TSnow)} bytes data={TSnow}")
        try:
            ipc_mq.send(TSnow, False)           # send message to queue
        except sysv_ipc.BusyError:
            print(f"  ERROR: can't send data to message-queue")
        print(f"    Status: {ipc_mq.current_messages=} {ipc_mq.last_send_time=} {ipc_mq.last_send_pid=}")
    time.sleep(1)
except KeyboardInterrupt:
    pass  # Does absolutely nothing

print(f"\nTo control System-V IPC objects, please call 'ipcs'\n"
      f"or remove message-queue with 'ipcrm -r ID'\n"
      f"or look at: 'cat /proc/sysvipc/msg'")

