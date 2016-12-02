<?php
require_once 'lib/autoload.php';
$f3 = \Base::instance();
$f3->set('AUTOLOAD', 'modules/');
$f3->set('DEBUG', 3);
$dice = new \Dice\Dice();
$f3->set('Dice', $dice);
$rule = ['substitutions' => ['Base' => $f3]];
$dice->addRule('*', $rule);
$dispatcher = $dice->create('F3_Events');
$f3->set('eventD', $dispatcher);
$n = 10000;
$itTime = 0;

class M
{
    public function hi($n)
    {
        return ++$n;
    }

    public function hi1($n)
    {
        return ++$n;
    }

    public function hi2($n)
    {
        return ++$n;
    }
}

for ($i = 0; $i < $n; ++$i) {
    $middleTime = microtime(true);

    $dispatcher->on('hi', 'M->hi2',$i);
    $dispatcher->on('hi.g', 'M->hi',$i);
    $dispatcher->on('hi.g.h.j', 'M->hi1',$i);

    $dispatcher->on('hi', 'M->hi2',$i);
    $dispatcher->on('hi.g', 'M->hi',$i);
    $dispatcher->on('hi.g.h.j', 'M->hi1',$i);

    echo $time = round(1e3 * (microtime(true) - $middleTime), 2);
    echo PHP_EOL;
    $itTime = $itTime + $time;
}

echo PHP_EOL;
echo $f3->format('Listeners are added per {0} msec', round($itTime, 2));
echo PHP_EOL;
echo PHP_EOL;

$middleTime = microtime(true);
echo 'Output: '.$dispatcher->emit('hi',1);
echo PHP_EOL;
echo PHP_EOL;

$time = round(1e3 * (microtime(true) - $middleTime), 2);
echo $f3->format('Emited per {0} msec', $time);
echo PHP_EOL;
echo PHP_EOL;

echo $f3->format('EVENT SYSTEM running {0} msecs and {1} KB memory', round(($itTime + $time), 2), round(memory_get_usage() / 1e3));
echo PHP_EOL;
echo PHP_EOL;
