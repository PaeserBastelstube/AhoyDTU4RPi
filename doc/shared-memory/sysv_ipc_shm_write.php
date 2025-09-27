// sysv_ipc_shm_write.php
<?php 
################################################################################
# System-V IPC for PHP - Semaphores, Shared Memory and Message Queues
# https://www.php.net/manual/en/book.shmop.php
# https://www.php.net/manual/en/book.sem.php
################################################################################
date_default_timezone_set('Europe/Berlin'); # set the default timezone to use

# create any file to generate the specific FTOK key
$filePathName = '/tmp/example';   # def a filepath / filename
$fd = fopen($filePathName, "w");  # create file-handle for writing
fclose($fd);                      # close file-handle

# generate the specific FTOK key from file-path-name
#  ftok(string $filename, string $project_id): int
#      "id" must be as chr(string) - hex(0x30) = dec(48) = chr("0") 
$ftokKey = ftok($filePathName, "0");
print("ftokKey=" . $ftokKey . " hex=0x" . dechex($ftokKey) . PHP_EOL);

while (1) {
    $TSnow = date('d.m.YTH:i:s');        # create TimeStamp
    $sem_id = @sem_get($ftokKey);        # creating a semaphore
    if (False == $sem_id) {
        print ("can't create or open the Semaphore with KEY: " . $ftokKey . PHP_EOL);
        print ("Please check Semaphore permission!" . PHP_EOL);
        break;
    }

    if (sem_acquire($sem_id, false)) {   # acquiring the semaphore
        print ("Semaphore set - value string len=" . strlen($TSnow) . PHP_EOL);
                                         # create shared memeory object
        $shdMemObj = @shmop_open($ftokKey, "c", 0644, strlen($TSnow));
        if (!$shdMemObj) {
			echo "Couldn't create shared memory segment, mybee there is an existing one?" . PHP_EOL;
        	$shdMemObj = @shmop_open($ftokKey, "w", 0644, 0);
        	if (!$shdMemObj) {
				echo "Couldn't create shared memory segment" . PHP_EOL;
                break;
			}
        }
        print("opened Shared-Memory with size=" . shmop_size($shdMemObj));
        if (shmop_size($shdMemObj) == strlen($TSnow)) {
			$shm_bytes_written = shmop_write($shdMemObj, $TSnow, 0);
			shmop_close($shdMemObj);
			if ($shm_bytes_written == strlen($TSnow))
				print ("  len=" . $shm_bytes_written . "  TSnow=" . $TSnow);
			else print ("  Couldn't write the entire length of data");
		} else {
			print(" - ERROR: need size of: " . strlen($TSnow) . " - delete SHM");
			shmop_delete($shdMemObj); # vorhandenes SHM löschen
		}
		print PHP_EOL;
        sleep(1);
    }
    sem_release($sem_id);                # release the semaphore
    sem_remove($sem_id);                 # delete a semaphore
}


echo (PHP_EOL . "To control shared-memory and semaphore, please call 'ipcs' or remove with 'ipcrm -r ID'" . PHP_EOL);
echo ("look at: 'cat /proc/sysvipc/shm'" . PHP_EOL);
?>
