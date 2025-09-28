<?php
################################################################################
# Example: Python unserialize PHP data
# 28.09.2025
################################################################################

$userArray = ['foo' => 'bar'];
$userString_1 = serialize($userArray);
print "gettype IN: " . gettype($userArray) . " - gettype OUT: " . gettype($userString_1);
print " - " . $userString_1 . PHP_EOL;
print_r(unserialize($userString_1));

$userObj = (object) ['baz' => 'qux'];
$userString_2 = serialize($userObj);
print "gettype IN: " . gettype($userObj) . " - gettype OUT: " . gettype($userString_2);
print " - " . $userString_2 . PHP_EOL;
print_r(unserialize($userString_2));

?>
