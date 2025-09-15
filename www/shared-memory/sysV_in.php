// sysV_in.php
<?php 
################################################################################
# System-V IPC for PHP - Semaphores, Shared Memory and Message Queues
# https://www.php.net/manual/en/book.shmop.php
################################################################################
date_default_timezone_set('Europe/Berlin');

# create any file to generate the specific FTOK key
$filePathName = '/tmp/example';   # def a filepath / filename
$fd = fopen($filePathName, "w");  # create file-handle for writing
fclose($fd);                      # close file-handle

# generate the specific FTOK key from file-path-name
#  ftok(string $filename, string $project_id): int
# "id" must be in chr(string) - hex(0x30) = dec(48) = chr("0") 
$ftokKey = ftok($filePathName, "0");
print("ftokKey=" . $ftokKey . " hex=0x" . dechex($ftokKey) . PHP_EOL);

$shdMemObj = shmop_open($ftokKey, "a", 0644, 0);
if (!$shdMemObj) {
    echo "Couldn't connect to shared memory segment\n";
}
// Get shared memory block's size
$shm_size = shmop_size($shdMemObj);
echo "SHM Block Size: " . $shm_size . " byte found.\n";

// Now lets read the string back
$my_string = shmop_read($shdMemObj, 0, $shm_size);
if (!$my_string) {
    echo "Couldn't read from shared memory block\n";
}
echo "The data inside shared memory was: " . $my_string . "\n";

//Now lets delete the block and close the shared memory segment
if (!shmop_delete($shdMemObj)) {
    echo "Couldn't mark shared memory block for deletion.";
}
shmop_close($shdMemObj);

echo ("To control shared memory, please call 'ipcs -m'" . PHP_EOL);
?>
