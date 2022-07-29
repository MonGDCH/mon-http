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



$a = new AB();
$test = [[$a, 'a'], [$a, 'b'], [$a, 'c'], [$a, 'd']];
$test = array_reverse($test);



// var_dump($callback(666));


// function myfunction($v1, $v2)
// {
//     return $v1 . "-" . $v2;
// }
// print_r(array_reduce($test, function ($a, $b) {
//     var_dump($a, $b);
// }, function ($c) {
//     var_dump($c);
// }));


class AB
{
    public function a($a, $b)
    {
        var_dump($a . ' => a');
        return $b(__METHOD__);
    }

    public function b($a, $b)
    {
        var_dump($a . ' => b');
        // return 123;
        return $b(__METHOD__);
    }

    public function c($a, $b)
    {
        var_dump($a . ' => c');
        return $b(__METHOD__);
    }

    public function d($a, $b)
    {
        var_dump($a . ' => d');
        return $b(__METHOD__);
    }
}

$callback = array_reduce($test, function ($carry, $pipe) {
    return function ($request) use ($carry, $pipe) {
        return $pipe($request, $carry);
    };
}, function ($res) {
    // var_dump($res);
    return '654';
});


$res = $callback(666);
var_dump($res);


// $b = [$a, 'd'];

// var_dump($b(3));


// var_dump(is_callable([$a, 'd']));
