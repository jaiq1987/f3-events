<?php
/*
 *
 * Привет всем от Айрата :)
 * This event system need debug and evolution
 *
 */
class F3_Events
{
    protected $f3;
    protected $dice;
    protected $mode;
    protected $listeners;

    public function __construct(\Base $f3, $mode = 'full')
    {
        $this->f3 = $f3;
        $this->dice = $this->f3->get('Dice');
        $this->ekey = 'EVENTS.';
        $this->listeners = &$this->f3->ref($this->ekey);
        $this->listeners = [];
        $this->mode = $mode;
    }

    public function on($event, $listener, $priority = 0)
    {
        $keys = explode('.', $event);
        $count = count($keys);
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

    public function off($event = null, $listener = null)
    {
        if ($event !== null) {
            if (!isset($this->listeners[$event])) {
                return;
            }
            if ($listener !== null) {
                foreach ($this->listeners[$event] as $priority => $listeners) {
                    if (false !== ($key = array_search($listener, $listeners, true))) {
                        unset($this->listeners[$event][$priority][$key]);
                    }
                }
            } else {
                unset($this->listeners[$event]);
            }
        } else {
            $this->listeners = [];
        }
    }

    public function lite($event, $arguments = null, $subj = null)
    {
        if (isset($this->listeners[$event])) {
            krsort($this->listeners[$event]);
            foreach ($this->listeners[$event] as $i => $listeners) {
                if (is_numeric($i) && $listeners) {
                    foreach ($listeners as $func) {
                        $out = $this->call($func, array($arguments, $subj));
                        if ($out === false) {
                            return $arguments;
                        }
                        if ($out) {
                            $arguments = $out;
                        }
                    }
                }
            }
        }

        return $arguments;
    }

    public function snap($event, $arguments = null, $subj = null)
    {
        if ($this->f3->exists($this->ekey.$event, $e) && !empty($e)) {
            $arguments = $this->parse($e, $event, $arguments, $subj);
        }

        return $arguments;
    }

    public function emit($event, $arguments = null, $subj = null)
    {
        if ($this->mode == 'full') {
            if ($this->f3->exists($this->ekey.$event, $e) && !empty($e)) {
                $ek = explode('.', $event);
                $arguments = $this->parse($e, $event, $arguments, $subj);
                if (count($ek) > 1) {
                    $e = $this->f3->ref($this->ekey.$ek[0], false);
                    $arguments = $this->parse($e, $ek, $arguments, $subj, true);
                }
            }
        } elseif ($this->mode == 'snap') {
            $arguments = $this->snap($event, $arguments, $subj);
        } elseif ($this->mode == 'lite') {
            $arguments = $this->lite($event, $arguments, $subj);
        }

        return $arguments;
    }

    protected function call($listener, array $args)
    {
        if ($this->dice === null) {
            return $this->f3->call($listener, $args);
        }
        if (is_string($listener)) {
            $listener = $this->grab($listener);
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

    protected function parse($e, $key, $arguments = null, $subj = null, $rev = false)
    {
        if ($rev === true && is_array($key)) {
            array_pop($key);
            $count = count($key);
            $ec = $e;
            for ($a = 1; $a < $count; ++$a) {
                $e = $e[$key[$a]];
            }
        } else {
            $subs = array();
        }
        krsort($e);
        foreach ($e as $i => $listeners) {
            if (is_numeric($i) && $listeners) {
                foreach ($listeners as $func) {
                    $out = $this->call($func, array($arguments, $subj));
                    if ($out === false) {
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
        if ($rev === true && count($key) > 1) {
            $arguments = $this->parse($ec, $key, $arguments, $subj, $rev);
        } else {
            if ($subs) {
                foreach ($subs as $sub) {
                    $arguments = $this->parse($sub, $key, $arguments, $subj);
                }
            }
        }

        return $arguments;
    }
}
