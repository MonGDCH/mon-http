<?php

declare(strict_types=1);

set_error_handler(function ($level, $message, $file = '', $line = 0, $context = []) {
    if (error_reporting() & $level) {
        throw new ErrorException($message, 0, $level, $file, $line);
    }
});
register_shutdown_function(function ($start_time) {
    // if (time() - $start_time <= 1) {
    //     sleep(1);
    // }
}, time());


$a= 'c';
$b= 'b';
$c= 'q';
$abc = <<<eee
asdfp{$a}]asdaf{$b}{$c}aaa
eee;

var_dump($abc);