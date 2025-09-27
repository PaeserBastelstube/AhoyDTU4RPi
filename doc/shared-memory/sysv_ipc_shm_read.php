// sysv_ipc_shm_read.php
<?php 
################################################################################
# System-V IPC for PHP - Semaphores, Shared Memory and Message Queues
# https://www.php.net/manual/en/book.shmop.php
# https://www.php.net/manual/en/book.sem.php
################################################################################
date_default_timezone_set('Europe/Berlin');    # set the default timezone to use

# create any file to generate the specific FTOK key
$filePathName = '/tmp/example';   # def a filepath / filename
$fd = fopen($filePathName, "w");  # create file-handle for writing
fclose($fd);                      # close file-handle

# generate the specific FTOK key from file-path-name
#  ftok(string $filename, string $project_id): int
# "id" must be in chr(string) - hex(0x30) = dec(48) = chr("0") 
$ftokKey = ftok($filePathName, "0");
print("ftokKey=" . $ftokKey . " hex=0x" . dechex($ftokKey) . PHP_EOL);

do {
  $sem_id = @sem_get($ftokKey, 1, 0o666, 1);       # creating a semaphore
  if (False == $sem_id) {
    print ("can't create or open the Semaphore with KEY: 0x" . dechex($ftokKey) . PHP_EOL);
    break;
  }

  if (sem_acquire($sem_id)) {           # acquiring the semaphore
    $shdMemObj = @shmop_open($ftokKey, "a", 0644, 0);
    if (!$shdMemObj) {
        echo "Couldn't connect to shared memory segment\n";
        sem_remove($sem_id);            # delete a semaphore
        break;
    }
    $shm_size = shmop_size($shdMemObj); # Get shared memory block's size
    $my_string = shmop_read($shdMemObj, 0, $shm_size);  # read data back

    if (!shmop_delete($shdMemObj)) {    # delete shared memory segment
        echo "Couldn't mark shared memory block for deletion.";
    }
    shmop_close($shdMemObj);            # close the shared memory segment
    sem_release($sem_id);               # release the semaphore

    if (!$my_string) {
        echo "Couldn't read from shared memory block\n";
    } else {
        echo "SHM Block Size: " . $shm_size . " byte found.\n";
        echo "The data inside shared memory was: " . $my_string . "\n";
    }
    sem_remove($sem_id);                # remove the semaphore
  }
} while (0);

echo ("To control shared-memory and semaphore, please call 'ipcs' or remove with 'ipcrm -a'" . PHP_EOL);
?>
