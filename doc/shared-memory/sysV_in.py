# sysV_in.py
################################################################################
# System V IPC for Python - Semaphores, Shared Memory and Message Queues
# https://semanchuk.com/philip/sysv_ipc/
################################################################################
# Flag IPC_CREX is shorthand for IPC_CREAT | IPC_EXCL, they are used when
# creating IPC objects and identified by key. If the IPC object (semaphore, ...)
# with that key already exists, the call raises an ExistentialError.
#
# With flags set to default=0, the module attempts to open an existing IPC object, 
# identified by key and raises a ExistentialError if that IPC object doesn't exist.  
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

try: # Creates a new semaphore or opens an existing one.
    sem_id = sysv_ipc.Semaphore(ftokKey, sysv_ipc.IPC_CREX, 0o644)
    ## print(f"try to set new Semaphore")
except sysv_ipc.ExistentialError:
    # One of my peers created the semaphore already
    # reopen this already created Semaphore
    sem_id = sysv_ipc.Semaphore(ftokKey)
    if sem_id.o_time == 0: # destry forgotten Semaphore and loop
        print(f"existing Semaphore found without TimeStamp, try to remove...")
        sem_id.release()
        sem_id.remove()
    else:
        print(f"existing Semaphore found with TimeStamp: {sem_id.o_time}, remove with 'ipcrm -a'")
else:
    # Initializing sem.o_time to nonzero value
    sem_id.release()                    # release Semaphore
    # Now the semaphore is safe to use.
    print(f"Semaphore successfully set with TimeStamp: {sem_id.o_time}")

    # a = input(f"Please close Semaphore-ID {sem_id}")
    try:        # to open Shared-Memory
        shdMemObj = sysv_ipc.SharedMemory(ftokKey)
        r_now = shdMemObj.read()        # read data from shared memory
        shdMemObj.detach()              # detach shared memory object
        print(f" {shdMemObj.id=} len={shdMemObj.size} byte - {r_now=}")
        shdMemObj.remove()              # remove shared memory object
    except sysv_ipc.ExistentialError:
        # shdMemObj = sysv_ipc.SharedMemory(ftokKey, sysv_ipc.IPC_CREX, 0o0644, 1) # create shared memeory object
        print("No Shared-Memory Segment found")

    sem_id.remove()                     # remove Semaphore

print(f"==> Check System-V IPC Objects with command: 'ipcs' "
      f"and/or  look at: 'cat /proc/sysvipc/shm'")

