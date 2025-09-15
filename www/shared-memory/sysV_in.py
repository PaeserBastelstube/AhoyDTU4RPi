# sysV_in.py
################################################################################
# System V IPC for Python - Semaphores, Shared Memory and Message Queues
# https://semanchuk.com/philip/sysv_ipc/
################################################################################

import os
from datetime import datetime
import sysv_ipc

# create any file to generate the specific FTOK key
filePathName = '/tmp/example'                  # def a filepath / filename
fd = os.open(filePathName, flags=os.O_CREAT)   # create file-handle
os.close(fd)                                   # close file-handle

# generate the specific FTOK key from file-path-name
# ftok(path, id, [silence_warning = False])
# Note that ftok() has limitations, and this function will issue a warning 
# to that effect unless silence_warning is True. 
# "id" must be in hex - hex(0x30) = dec(48) = chr("0") 
ftokKey = sysv_ipc.ftok(filePathName, 0x30, silence_warning = True)
print(f"{ftokKey=} hex=0x{ftokKey:08x}")

# create shared memeory object
# shdMemObj = sysv_ipc.SharedMemory(ftokKey, flags=sysv_ipc.IPC_CREAT, mode=0o644)
shdMemObj = sysv_ipc.SharedMemory(ftokKey)

r_now = shdMemObj.read()
print(f" {shdMemObj.id=} len={shdMemObj.size} byte - {r_now=}")

shdMemObj.detach()
shdMemObj.remove()

print(f"check Shared Memory Segments with command 'ipcs -m'")
print(f"and look at: 'cat /proc/sysvipc/shm'")
