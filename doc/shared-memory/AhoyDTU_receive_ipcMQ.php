// AhoyDTU_receive_ipcMQ.php
<?php 
################################################################################
# System-V IPC for PHP - Semaphores, Shared Memory and Message Queues
# https://www.php.net/manual/en/book.shmop.php
# https://www.php.net/manual/en/book.sem.php
################################################################################
date_default_timezone_set('Europe/Berlin'); # set the default timezone to use

# def a file to generate the specific FTOK key
$filePathName = '/home/AhoyDTU/ahoy/AhoyDTU.yml';

# generate the specific FTOK key from file-path-name
# ftok(string $filename, string $project_id): int
#	"id" must be as chr(string) - hex(0x30) = dec(48) = chr("0") 
$ftokKey = ftok($filePathName, "0");
print("ftokKey=" . $ftokKey . " hex=0x" . dechex($ftokKey) . PHP_EOL);

if (msg_queue_exists($ftokKey)) {
	$ipc_mq = msg_get_queue($ftokKey);			# open existing message-queue
	$status_mq = msg_stat_queue($ipc_mq);		# get status of mq
	if ($status_mq['msg_qnum'] > 0)				# check for waiting messages
		print ("  " . $status_mq['msg_qnum'] . " messages waiting in Message-Queue -");
												# remove Queue
	print " Delete Message-Queue: status: " . (msg_remove_queue($ipc_mq) ? "deleted" : "false") . PHP_EOL;
	# print (system('ipcs') . PHP_EOL);
}

$ii = 10;
print ("Waiting to receive messages" . PHP_EOL);
while (1) {
	$ipc_mq = msg_get_queue($ftokKey);	# create a message-queue
	if (false == $ipc_mq) {				# check if mq exists
		print "  ERROR: can't create message-queue" . PHP_EOL;
		break;
	}

	if (msg_queue_exists($ftokKey)) {
		msg_receive($ipc_mq, 1, $messType, 16384, $msgData, true, MSG_IPC_NOWAIT, $msgError);

		if ($msgError == 0 and $messType != 0) {
			print "  messType: " . $messType . " msgError: " . $msgError . PHP_EOL;

			if (gettype($msgData) == "string" and strlen($msgData) > 0){
				print ("  Received data from message-queue: len=" . strlen($msgData) . 
					" bytes data=" . $msgData . PHP_EOL);
			} else print_r ($msgData);
		}
	}

	sleep(1);
	$ii -= 1;
	if ($ii == 0) break;
}
print "Delete Message-Queue: status: " . (msg_remove_queue($ipc_mq) ? "deleted" : "false") . PHP_EOL;

echo (PHP_EOL . "To control System-V IPC objects, please call 'ipcs'" . PHP_EOL .
                "or remove message-queue with 'ipcrm -r ID'" . PHP_EOL . 
				"or look at: 'cat /proc/sysvipc/msg'" . PHP_EOL);
?>

