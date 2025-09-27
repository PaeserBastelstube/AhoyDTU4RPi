# multi_proc_write.py
################################################################################
# System V IPC for Python - Semaphores, Shared Memory and Message Queues
# https://docs.python.org/3/library/multiprocessing.shared_memory.html
################################################################################

import os                                  # for open file
import time                                # for time.sleep
from datetime import datetime              # for datetime.now() and strftime
from multiprocessing import shared_memory  # 

# create specific file to generate a known alternative FTOK key
filePathName = '/tmp/example'                  # def a filepath / filename
fd = os.open(filePathName, flags=os.O_CREAT)   # create file-handle
myInode = os.stat(filePathName).st_ino
os.close(fd)                                   # close file-handle

# generate the known alternative FTOK key from specifiv file
ftokKey = 0x30000000 + myInode
print(f"{ftokKey=} hex=0x{ftokKey:08x}")

ii = 0
while True:
    shdMemData = datetime.now().strftime('%d.%m.%YT%H:%M:%S').encode()   # create TimeStamp
    print (f"New TimeStamp: len={len(shdMemData)} type={type(shdMemData)} {shdMemData=}")

    try: # create a new SharedMemory segment
        shdMemObj = shared_memory.SharedMemory(str(ftokKey), create=True, size=len(shdMemData))
        print (f"  NEW shared memory object with size={shdMemObj.size} bytes created")
        # print (f"NEW object: {shdMemObj.__dict__=}")
        os.system("ipcs")
    except FileExistsError:
        shdMemObj = shared_memory.SharedMemory(str(ftokKey), create=False)
        bufLen  = shdMemObj.buf.__sizeof__()
        bufCont = bytes(shdMemObj.buf[0:bufLen])
        print (f"  found shared memory object with {bufLen=} bytes (of {type(shdMemObj.buf)})"
               f"- content:{bufCont} (lenCont={len(bufCont)}) bytes - marked to delete")
        # print (f"  existing: {shdMemObj.__dict__=}")
        shdMemObj.unlink()
        ii += 1
        if (ii >= 3):
            break
    else:
        shdMemLen = len(shdMemData)
        shdMemObj.buf[0:shdMemLen] = shdMemData
        print(f"  Write TimeStamp to shared memory object with {shdMemLen} bytes : {shdMemData=}")
        shdMemObj.close()                    # Close each SharedMemory object
    time.sleep(1)
print(f"check Shared Memory Segments with command 'ipcs' "
      f"and/or look at: 'cat /proc/sysvipc/shm'")

