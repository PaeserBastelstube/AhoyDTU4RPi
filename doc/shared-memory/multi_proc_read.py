# posix_in.py
################################################################################
# POSIX-IPC for Python - Semaphores, Shared Memory and Message Queues
# https://docs.python.org/3/library/multiprocessing.shared_memory.html
################################################################################
import pickle
import os, time
from datetime import datetime
from multiprocessing import shared_memory

# create any file to generate the specific FTOK key
filePathName = '/tmp/example'                  # def a filepath / filename
fd = os.open(filePathName, flags=os.O_CREAT)   # create file-handle
myInode = os.stat(filePathName).st_ino
os.close(fd)                                   # close file-handle

ftokKey = 0x30000000 + myInode
print(f"{ftokKey=} hex=0x{ftokKey:08x}")

# connect to shared memory
shdMemObj = shared_memory.SharedMemory(str(ftokKey), create=False)
print (f"  {shdMemObj.__dict__=}")

data = pickle.loads(shdMemObj.buf[:len(shdMemObj.buf)])
# data = shdMemObj.buf[0]
print(f"  {len(shdMemObj.buf)=} : {data=}")

# Remember to release the memory after use
# shdMemObj.close()

# Remember to release the memory after use
# shdMemObj.unlink()

print(f"check Shared Memory Segments with command 'ipcs -m'")
print(f"and look at: 'cat /proc/sysvipc/shm'")
# find /proc/sysvipc -exec cat {} \;
