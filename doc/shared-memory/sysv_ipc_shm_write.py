# sysv_ipc_shm_write.py
################################################################################
# System V IPC for Python - Semaphores, Shared Memory and Message Queues
# https://semanchuk.com/philip/sysv_ipc/
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

while True:
    TSnow = datetime.now().strftime('%d.%m.%YT%H:%M:%S')   # create TimeStamp

    try: # Creates a new semaphore or opens an existing one. 
        sem_id = sysv_ipc.Semaphore(ftokKey, sysv_ipc.IPC_CREX, 0o644)
        ## print(f"try to set new Semaphore")
    except sysv_ipc.ExistentialError: # semaphore exists already
        # now, open this existing Semaphore and read ".o_time" value
        # for unused Semaphore, ".o_time" value is zero
        # for used Semaphore, ".o_time" value is a timestamp
        sem_id = sysv_ipc.Semaphore(ftokKey)
        print(f"existing Semaphore found with TimeStamp: {sem_id.o_time}")
        if sem_id.o_time == 0:         # destry forgotten Semaphore and loop
            sem_id.release()
            sem_id.remove()
    else:
        # Initializing sem.o_time to nonzero value
        sem_id.release()               # release Semaphore
        # Now the semaphore is safe to use.

        # print (f"{sem_id.key=} {sem_id.id=} mode={oct(sem_id.mode)} {sem_id.value=} "
        #        f"{sem_id.block=} {sem_id.last_pid=} {os. getpid()=}")
        print(f"Semaphore successfully init with TimeStamp: {sem_id.o_time}")

        # create shared memeory object # PC_CREAT used when creating IPC objects. 
        shdMemObj = sysv_ipc.SharedMemory(ftokKey, size=len(TSnow), flags=sysv_ipc.IPC_CREAT, mode=0o644)
        shdMemObj.write(TSnow)
        shdMemObj.detach()
        print(f" Shared-Memory created: {shdMemObj.id=} len={len(TSnow)} value={TSnow}")
        sem_id.remove()                # remove Semaphore

    time.sleep(1)

print(f"check Shared Memory Segments with command 'ipcs' "
      f"and/or look at: 'cat /proc/sysvipc/shm'")

