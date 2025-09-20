// AhoyDTU_read_data.php
<?php 
################################################################################
# System-V IPC for PHP - Semaphores, Shared Memory and Message Queues
# https://www.php.net/manual/en/book.shmop.php
# https://www.php.net/manual/en/book.sem.php
################################################################################
date_default_timezone_set('Europe/Berlin');    # set the default timezone to use

# def a file to generate the specific FTOK key
$filePathName = '/home/AhoyDTU/ahoy/AhoyDTU.yml';

# generate the specific FTOK key from file-path-name
#  ftok(string $filename, string $project_id): int
# "id" must be in chr(string) - hex(0x30) = dec(48) = chr("0") 
$ftokKey = ftok($filePathName, "0");
print("ftokKey=" . $ftokKey . " hex=0x" . dechex($ftokKey) . PHP_EOL);

$max_acquire = 1;     # number of processes that can acquire the semaphore simultaneously
$auto_release = true; # Specifies if the semaphore should be automatically released on request shutdown
do {
  $sem_id = @sem_get($ftokKey, $max_acquire, 0o666, $auto_release);       # creating a semaphore
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
    $offsetFromStart = 0;
    $shm_string = shmop_read($shdMemObj, $offsetFromStart, $shm_size);
    shmop_close($shdMemObj);            # close the shared memory segment
    sem_release($sem_id);               # release the semaphore

    if (!$shm_string) {
        echo "Couldn't read from shared memory block\n";
    } else {
        echo "SHM Block Size: " . $shm_size . " byte found" . PHP_EOL;
        echo "The data inside shared memory was an ";
		# echo $shm_string . PHP_EOL;
		print_r (json_decode($shm_string, true));
    }
    sem_remove($sem_id);                # remove the semaphore
  }
} while (0);

echo PHP_EOL;
echo ("To control shared-memory and semaphore, please call 'ipcs' or remove with 'ipcrm -a'");
echo PHP_EOL;
?>
