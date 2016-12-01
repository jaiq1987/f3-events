<?php
require_once 'lib/autoload.php';
$f3 = \Base::instance();
$f3->set('DEBUG', 3);
$dice = new \Dice\Dice();
$f3->set('Dice', $dice);
$rule = ['substitutions' => ['Base' => $f3]];
$dice->addRule('*', $rule);

// you can use F3_Events without dice but you need to pass $f3 in construct
$dispatcher = $dice->create('F3_Events');
$f3->set('eventD', $dispatcher);
$n = 10000;
$itTime = 0;
class M
{
    public function hi($n){
        if ($n == 1) {
            echo '1';
            echo PHP_EOL;
        }
        return ++$n;
    }
    public function hi1($n){
        if ($n == 10001) {
            echo '2';
            echo PHP_EOL;
        }
        return ++$n;
    }
    public function hi2($n){
        if ($n == 20001) {
            echo '3';
            echo PHP_EOL;
        }
        return ++$n;
    }
}
for ($i = 0; $i < $n; ++$i) {
    $middleTime = microtime(true);

    $dispatcher->on('hi', 'M->hi2',$i);
    $dispatcher->on('hi.g', 'M->hi',$i);
    $dispatcher->on('hi.g.h.j', 'M->hi1',$i);

    $time = round(1e3 * (microtime(true) - $middleTime), 2);
    echo $itTime = $itTime + $time;
    echo PHP_EOL;
}
$middleTime = microtime(true);
echo PHP_EOL;

echo $dispatcher->emit('hi.g',1); // or try snap and lite methods

echo PHP_EOL;
$time = round(1e3 * (microtime(true) - $middleTime), 2);
echo $itTime = $itTime + $time;
echo PHP_EOL;

echo $f3->format('EVENT SYSTEM running {0} msecs and {1} KB memory', $itTime, round(memory_get_usage() / 1e3));
echo PHP_EOL;