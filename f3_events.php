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
class F3_Events extends Prefab
{
    protected $f3;
    protected $dice;
    protected $mode;
    protected $ckey;
    protected $ekey;

    public function __construct(\Base $f3 = null, $obj = null, $mode = 'full')
    {
        if ($f3 === null) {
            $this->f3 = \Base::instance();
        } else {
            $this->f3 = $f3;
        }
        if ($obj !== null) {
            $this->ckey = 'EVENTS_local.'.$this->f3->hash(spl_object_hash($obj));
        } else {
            $this->ckey = 'EVENTS';
        }
        $this->ekey = $this->ckey.'.';
        $this->dice = $this->f3->get('Dice');
        $this->config($mode);
    }

    public function config($mode = 'full') //mode - if needed
    {
        $this->mode = $mode;
    }

    public function once($event, $listener, $priority = 10, $options = array())
    {
        $this->on($event, $listener, $priority, $options, true);
    }

    public function on($event, $listener, $priority = 10, $options = array(), $once = false)
    {
        $listeners = &$this->f3->ref($this->ckey);
        $keys = explode('.', $event);
        $count = count($keys);
        if (is_array($listener) && is_callable($listener[1])) {
            $listener = array('id' => $listener[0], 'func' => $listener[1]);
        }
        if ($options) {
            if (is_array($listener) && !empty($listener['func'])) {
                $listener['options'] = $options;
            } else {
                $listener = array('func' => $listener, 'options' => $options);
            }
        }
        if ($once) {
            if (is_array($listener) && !empty($listener['func'])) {
                $listener['once'] = true;
            } else {
                $listener = array('func' => $listener, 'once' => true);
            }
        }
        if ($count > 1) {
            foreach ($keys as $i => $key) {
                if (++$i == $count) {
                    $listeners[$key][$priority][] = $listener;
                } else {
                    $listeners = &$listeners[$key];
                }
            }
        } else {
            $listeners[$event][$priority][] = $listener;
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
                            $trueArr = is_array($array);
                            if ($array == $listener || $trueArr && $array['func'] == $listener || $trueArr && $array['id'] == $listener) {
                                if ($delete) {
                                    $this->f3->clear($this->ekey.$event.'.'.$priority.'.'.$i);
                                }
                                $exists = true;
                            }
                        }
                    } else {
                        foreach ($e as $priority => $listeners) {
                            if (is_numeric($priority)) {
                                foreach ($listeners as $i => $array) {
                                    $trueArr = is_array($array);
                                    if ($array == $listener || $trueArr && $array['func'] == $listener || $trueArr && $array['id'] == $listener) {
                                        if ($delete) {
                                            $this->f3->clear($this->ekey.$event.'.'.$priority.'.'.$i);
                                        }
                                        $exists = true;
                                    }
                                }
                            }
                        }
                    }
                } elseif ($priority !== null) {
                    if (!empty($e[$priority])) {
                        if ($delete) {
                            $this->f3->clear($this->ekey.$event.'.'.$priority);
                        }
                        $exists = true;
                    }
                } else {
                    if ($delete) {
                        $this->f3->clear($this->ekey.$event);
                    }
                    $exists = true;
                }
            }
        } elseif ($delete) {
            $this->f3->clear($this->ckey);
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
            $ek = explode('.', $event);
            $startRev = true;
            $arguments = $this->parse($e, $event, $arguments, $context, $hold, false, true, $startRev);
            if (count($ek) > 1 && $startRev) {
                $this->f3->exists($this->ekey.$ek[0], $e);
                $arguments = $this->parse($e, $ek, $arguments, $context, $hold, true);
            }
        }

        return $arguments;
    }

    public function emit($event, $arguments = null, &$context = array(), $hold = true)
    {
        if ($this->mode == 'full') {
            if ($this->f3->exists($this->ekey.$event, $e) && !empty($e)) {
                $ek = explode('.', $event);
                $startRev = true;
                $arguments = $this->parse($e, $event, $arguments, $context, $hold, false, false, $startRev);
                if (count($ek) > 1 && $startRev) {
                    $this->f3->exists($this->ekey.$ek[0], $e);
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
                $listener = $this->f3->grab($listener, $args);
            } else {
                $listener = $this->grab($listener, $args);
            }
        }

        return call_user_func_array($listener, $args);
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
                    $parts[1] = method_exists($parts[1], '__construct') && $args ?
                        $this->dice->create($parts[1], $args) :
                        $this->dice->create($parts[1]);
                }
            }
            $func = array($parts[1], $parts[3]);
        }

        return $func;
    }

    protected function parse($e, $key, $arguments = null, &$context = array(), $hold = true, $rev = false, $lite = false, &$startRev = true, $subK = null)
    {
        $count = 0;
        $once = false;
        if ($rev && is_array($key)) {
            array_pop($key);
            $count = count($key);
            $impl = implode('.', $key);
            $ev = array('name' => $impl, 'key' => end($key));
            $ec = $e;
            for ($a = 1; $a < $count; ++$a) {
                $e = $e[$key[$a]];
            }
        } else {
            $subs = array();
            if ($subK !== null) {
                $key = $key.'.'.$subK;
                $expl = explode('.', $key);
            } else {
                $expl = explode('.', $key);
            }
            $ev = array('name' => $key, 'key' => array_pop($expl));
            $impl = $key;
        }
        krsort($e);
        $listeners = array();
        foreach ($e as $nkey => $nval) {
            if (is_numeric($nkey)) {
                foreach ($nval as $n => $val) {
                    if (is_array($val) && $val['once']) {
                        $val['once'] = $nkey.'.'.$n;
                    }
                    $listeners[] = $val;
                }
            } else {
                if ($rev === false && $lite === false) {
                    $subs[$nkey] = $e[$nkey];
                }
            }
        }
        if ($listeners) {
            foreach ($listeners as $n => $func) {
                if (!is_array($func)) {
                    $func = array('func' => $func, 'options' => array());
                } else {
                    if (!empty($func['func'])) {
                        if (empty($func['options'])) {
                            $func['options'] = array();
                        }
                        if (!empty($func['once'])) {
                            $this->f3->clear($this->ekey.$impl.'.'.$func['once']);
                        }
                    } else {
                        $func = array('func' => $func, 'options' => array());
                    }
                }
                $ev['options'] = $func['options'];
                $out = $this->call($func['func'], array($arguments, &$context, $ev));
                if ($hold && $out === false) {
                    if (empty($subK)) {
                        $startRev = false;

                        return $arguments;
                    } elseif ($rev || $lite) {
                        return $arguments;
                    } else {
                        break;
                    }
                }
                if ($out) {
                    $arguments = $out;
                }
            }
        }
        if ($rev && $count > 1) {
            $arguments = $this->parse($ec, $key, $arguments, $context, $hold, $rev);
        } else {
            if ($subs && $lite === false) {
                foreach ($subs as $subK => $sub) {
                    $arguments = $this->parse($sub, $key, $arguments, $context, $hold, $rev, $lite, $srev, $subK);
                }
            }
        }

        return $arguments;
    }

    public function watch($obj = null, $mode = 'full')
    {
        if ($this->dice === null) {
            return new self($this->f3, $obj, $mode);
        } else {
            return $this->dice->create(get_class($this), array($this->f3, $obj, $mode));
        }
    }

    public function unwatch($obj)
    {
        $this->f3->clear('EVENTS_local.'.$this->f3->hash(spl_object_hash($obj)));
    }
}
