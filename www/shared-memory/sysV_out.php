// sysV_out.php
<?php 
################################################################################
# System-V IPC for PHP - Semaphores, Shared Memory and Message Queues
# https://www.php.net/manual/en/book.shmop.php
################################################################################

# create any file to generate the specific FTOK key
$filePathName = '/tmp/example';   # def a filepath / filename
$fd = fopen($filePathName, "w");  # create file-handle for writing
fclose($fd);                      # close file-handle

# generate the specific FTOK key from file-path-name
#  ftok(string $filename, string $project_id): int
# "id" must be in chr(string) - hex(0x30) = dec(48) = chr("0") 
$ftokKey = ftok($filePathName, "0");
print("ftokKey=" . $ftokKey . " hex=0x" . dechex($ftokKey) . PHP_EOL);

while (1) {
    # create TimeStamp
    # // set the default timezone to use.
    date_default_timezone_set('Europe/Berlin');
    $TSnow = date('d.m.YTH:i:s');

    # create shared memeory object
    $shdMemObj = shmop_open($ftokKey, "c", 0644, strlen($TSnow));
    if (!$shdMemObj) {
        echo "Couldn't create shared memory segment\n";
    }

    $shm_bytes_written = shmop_write($shdMemObj, $TSnow, 0);
    if ($shm_bytes_written != strlen($TSnow)) {
        echo "Couldn't write the entire length of data" . PHP_EOL;
    }
    shmop_close($shdMemObj);
    sleep(1);
    
    print ("  len=" . strlen($TSnow) . "  TSnow=" . $TSnow . PHP_EOL);
}

echo ("please call 'ipcs -m' to control Shared Memory" . PHP_EOL);
echo ("look at: 'cat /proc/sysvipc/shm'" . PHP_EOL);
?>
