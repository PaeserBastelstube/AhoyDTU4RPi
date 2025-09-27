// sysv_ipc_mq_send.php
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

# start loop
#   check, if message-queue (ipc_mq) exists - will be init by receiver
#   if ipc_mq exists,
#     open ipc_mq
#     send TimeStamp
#     close ipc_mq
#   wait 1 sec

while (1) {
    $TSnow = date('d.m.YTH:i:s');		# create TimeStamp

	if (! msg_queue_exists($ftokKey)) {
		print "No Message-Queue found - EXIT" . PHP_EOL;
		break;
	}

	$ipc_mq = msg_get_queue($ftokKey);	# open existing message-queue
	if (false == $ipc_mq) {				# check if mq exists
		print "  ERROR: can't create message-queue" . PHP_EOL;
		break;
	}

	print ("  Send data to message-queue: len=" . strlen($TSnow) . " bytes data=" . $TSnow . PHP_EOL);
	if (msg_queue_exists($ftokKey)) msg_send($ipc_mq, 1, $TSnow);
	sleep(1);
}

echo (PHP_EOL . "To control System-V IPC objects, please call 'ipcs'" . PHP_EOL .
                "or remove message-queue with 'ipcrm -r ID'" . PHP_EOL . 
				"or look at: 'cat /proc/sysvipc/msg'" . PHP_EOL);
?>
