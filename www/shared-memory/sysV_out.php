// sysV_out.php
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
$sem_id = sem_get($ftokKey, 1);          # creating a semaphore

while (1) {
    $TSnow = date('d.m.YTH:i:s');        # create TimeStamp
    if (sem_acquire($sem_id)) {          # acquiring the semaphore
                                         # create shared memeory object
        $shdMemObj = shmop_open($ftokKey, "c", 0644, strlen($TSnow));
        if (!$shdMemObj) {
            echo "Couldn't create shared memory segment\n";
        }

        $shm_bytes_written = shmop_write($shdMemObj, $TSnow, 0);
        shmop_close($shdMemObj);

        if ($shm_bytes_written == strlen($TSnow))
             print ("  len=" . $shm_bytes_written . "  TSnow=" . $TSnow . PHP_EOL);
        else print ("  Couldn't write the entire length of data" . PHP_EOL);

        sem_release($sem_id);            # release the semaphore
        sleep(1);
    }
}

sem_remove($sem_id);                     # delete a semaphore

echo ("please call 'ipcs -m' to control Shared Memory" . PHP_EOL);
echo ("look at: 'cat /proc/sysvipc/shm'" . PHP_EOL);
?>
