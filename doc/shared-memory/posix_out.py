# posix_out.py
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

# generate the specific FTOK key from file-path-name
# ftok(path, id, [silence_warning = False])
# Note that ftok() has limitations, and this function will issue a warning 
# to that effect unless "silence_warning = True". 
# "id" must be in hex - hex(0x30) = dec(48) = chr("0") 
# ftokKey = ftok(filePathName, 0x30, silence_warning = True)
# ftokKey = ftok(filePathName, 0x30)
ftokKey = 0x30000000 + myInode
print(f"{ftokKey=} hex=0x{ftokKey:08x}")

shdMemObj = shared_memory.SharedMemory(str(ftokKey), create=True, size=1024)
shdMemObj.close()

while True:
    # create TimeStamp
    TSnow = datetime.now().strftime('%d.%m.%YT%H:%M:%S')
    print (f"  len={len(TSnow)} type={type(TSnow)} {TSnow=}")
    TSpickled = pickle.dumps(TSnow, protocol=pickle.HIGHEST_PROTOCOL)
    print (f"  len={len(TSpickled)} {TSpickled=}")

    # create shared memeory object
    # shdMemObj = shared_memory.SharedMemory(str(ftokKey), create=True, size=len(TSnow))
    shdMemObj = shared_memory.SharedMemory(str(ftokKey), create=False)
    print (f"{shdMemObj.__dict__=}")

    # Write data into shared memory object
    shdMemObj.buf[:len(TSpickled)] = TSpickled
    print(f"{  len(shdMemObj.buf)=} : {shdMemObj.buf[0]=}")

    # Close each SharedMemory object
    # shdMemObj.close()
    time.sleep(1)   # sleep 1 sec.

# Remember to release the memory after use
shdMemObj.unlink()

print(f"check Shared Memory Segments with command 'ipcs -m'")
print(f"and look at: 'cat /proc/sysvipc/shm'")
