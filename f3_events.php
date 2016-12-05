<?php
/*
 * Hello everyone by Ayrat :)
 *
 * I was inspired by the ikkez Event System library.
 * https://groups.google.com/d/msg/f3-framework/gKquuIu7Pxo/a81UXkVNFQAJ
 * I thank him for that!
 * And I use the concept of the ikkez library.
 * Also, I had to remake some of the methods that I picked up from Fat Free Framework Base to integrate Dice DI
 *
 * This event system need a debug and evolution
 */
class F3_Events
{
    protected $f3;
    protected $dice;
    protected $mode;
    protected $listeners;

    public function __construct(\Base $f3, $obj = null, $mode = 'full') //mode - if needed
    {
        $this->f3 = $f3;
        if ($obj !== null) {
            $this->ekey = 'EVENTS_local.'.$this->f3->hash(spl_object_hash($obj)).'.';
        } else {
            $this->ekey = 'EVENTS.';
        }
        $this->listeners = &$this->f3->ref($this->ekey);
        $this->listeners = array();
        $this->mode = $mode;
        $this->dice = $this->f3->get('Dice');
    }

    public function watch($obj = null, $mode = 'full')
    {
        if ($this->dice === null) {
            return new self($this->f3, $obj, mode);
        } else {
            return $this->dice->create(get_class($this), array($this->f3, $obj, $mode));
        }
    }

    public function unwatch($obj)
    {
        $this->f3->clear('EVENTS_local.'.$this->f3->hash(spl_object_hash($obj)));
    }

    public function once($event, $listener, $priority = 10, $options = array())
    {
        $this->on($event, $listener, $priority, $options, true);
    }

    public function on($event, $listener, $priority = 10, $options = array(), $once = false)
    {
        $keys = explode('.', $event);
        $count = count($keys);
        if ($options) {
            $listener = array(
                'func' => $listener,
                'options' => $options,
            );
        }
        if ($once === true) {
            if (is_array($listener) && !empty($listener['func'])) {
                $listener['once'] = true;
            } else {
                $listener = array(
                    'func' => $listener,
                    'once' => true,
                );
            }
        }
        if ($count > 1) {
            $listeners = &$this->listeners;
            foreach ($keys as $i => $key) {
                if (++$i == $count) {
                    $listeners[$key][$priority][] = $listener;
                } else {
                    $listeners = &$listeners[$key];
                }
            }
        } else {
            $this->listeners[$event][$priority][] = $listener;
        }
    }

    public function has($event, $listener = null, $priority = null) //perhaps overkill
    {
        return $this->exists($event, $listener, $priority);
    }

    public function off($event = null, $listener = null, $priority = null) //perhaps overkill
    {
        return $this->exists($event, $listener, $priority, true);
    }

    protected function exists($event = null, $listener = null, $priority = null, $delete = false) //perhaps overkill
    {
        $exists = false;
        if ($event !== null) {
            if ($this->f3->exists($this->ekey.$event, $e) && !empty($e)) {
                if ($listener !== null) {
                    if ($priority !== null) {
                        foreach ($e[$priority] as $i => $array) {
                            if ($array == $listener || is_array($array) && $array['func'] == $listener) {
                                if ($delete === true) {
                                    $this->f3->clear($this->ekey.$event.'.'.$priority.'.'.$i);
                                }
                                $exists = true;
                            }
                        }
                    } else {
                        foreach ($e as $priority => $listeners) {
                            if (is_numeric($priority)) {
                                foreach ($listeners as $i => $array) {
                                    if ($array == $listener || is_array($array) && $array['func'] == $listener) {
                                        if ($delete === true) {
                                            $this->f3->clear($this->ekey.$event.'.'.$priority.'.'.$i);
                                        }
                                        $exists = true;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    if ($delete === true) {
                        $this->f3->clear($this->ekey.$event);
                    }
                    $exists = true;
                }
            }
        } elseif ($delete === true) {
            $ek = explode('.', $this->ekey);
            $this->f3->clear($ek[0]);
            $exists = true;
        }

        return $exists;
    }

    public function lite($event, $arguments = null, &$context = array(), $hold = true)
    {
        if ($this->f3->exists($this->ekey.$event, $e) && !empty($e)) {
            $arguments = $this->parse($e, $event, $arguments, $context, $hold, false, true);
        }

        return $arguments;
    }

    public function snap($event, $arguments = null, &$context = array(), $hold = true)
    {
        if ($this->f3->exists($this->ekey.$event, $e) && !empty($e)) {
            $arguments = $this->parse($e, $event, $arguments, $context, $hold);
        }

        return $arguments;
    }

    public function emit($event, $arguments = null, &$context = array(), $hold = true)
    {
        if ($this->mode == 'full') {
            if ($this->f3->exists($this->ekey.$event, $e) && !empty($e)) {
                $ek = explode('.', $event);
                $arguments = $this->parse($e, $event, $arguments, $context, $hold);
                if (count($ek) > 1) {
                    $e = $this->f3->ref($this->ekey.$ek[0], false);
                    $arguments = $this->parse($e, $ek, $arguments, $context, $hold, true);
                }
            }
        } elseif ($this->mode == 'snap') {
            $arguments = $this->snap($event, $arguments, $context, $hold);
        } elseif ($this->mode == 'lite') {
            $arguments = $this->lite($event, $arguments, $context, $hold);
        }

        return $arguments;
    }

    protected function call($listener, array $args)
    {
        if (is_string($listener)) {
            if ($this->dice === null) {
                $listener = $this->f3->grab($listener);
            } else {
                $listener = $this->grab($listener);
            }
        }

        return call_user_func_array($listener, $args ? $args : array());
    }

    protected function grab($func, $args = null)
    {
        if (preg_match('/(.+)\h*(->|::)\h*(.+)/s', $func, $parts)) {
            if (!class_exists($parts[1])) {
                user_error(sprintf(self::E_Class, $parts[1]), E_USER_ERROR);
            }
            if ($parts[2] == '->') {
                if (is_subclass_of($parts[1], 'Prefab')) {
                    $rule = $this->dice->getRule($parts[1]);
                    if ($rule['shared'] !== true) {
                        $this->dice->addRule($parts[1], array('shared' => true));
                    }
                    $parts[1] = $this->dice->create($parts[1]);
                } else {
                    $parts[1] = method_exists($parts[1], '__construct') ?
                        $this->dice->create($parts[1], array($args)) :
                        $this->dice->create($parts[1]);
                }
            }
            $func = array($parts[1], $parts[3]);
        }

        return $func;
    }

    protected function parse($e, $key, $arguments = null, &$context = array(), $hold = true, $rev = false, $lite = false)
    {
        $count = 0;
        $once = false;
        if ($rev === true && is_array($key)) {
            array_pop($key);
            $count = count($key);
            $impl = implode('.', $key);
            $ev = array(
                'name1' => $impl,
                'key' => end($key),
            );
            $ec = $e;
            for ($a = 1; $a < $count; ++$a) {
                $e = $e[$key[$a]];
            }
        } else {
            $subs = array();
            $expl = explode('.', $key);
            $ev = array(
                'name' => $key,
                'key' => array_pop($expl),
            );
        }
        krsort($e);
        foreach ($e as $i => $listeners) {
            if (is_numeric($i) && $listeners) {
                foreach ($listeners as $n => $func) {
                    if (!is_array($func)) {
                        $func = array('func' => $func, 'options' => array());
                    } elseif (is_array($func)) {
                        if (!empty($func['func'])) {
                            if (empty($func['options'])) {
                                $func['options'] = array();
                            }
                            if ($func['once'] === true) {
                                $once = true;
                            }
                        } else {
                            $func = array('func' => $func, 'options' => array());
                        }
                    }
                    $ev['options'] = $func['options'];
                    $out = $this->call($func['func'], array($arguments, &$context, $ev));
                    if ($once === true) {
                        $this->f3->clear($this->ekey.$key.'.'.$i.'.'.$n);
                    }
                    if ($hold && $out === false) {
                        return $arguments;
                    }
                    if ($out) {
                        $arguments = $out;
                    }
                }
            } else {
                if ($rev === false) {
                    array_push($subs, $e[$i]);
                }
            }
        }
        if ($rev === true && $count > 1) {
            $arguments = $this->parse($ec, $key, $arguments, $context, $hold, $rev);
        } else {
            if ($subs && $lite === false) {
                foreach ($subs as $sub) {
                    $arguments = $this->parse($sub, $key, $arguments, $context, $hold);
                }
            }
        }

        return $arguments;
    }
}
