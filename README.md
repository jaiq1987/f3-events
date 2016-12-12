# f3-events

Hello everyone by Ayrat :)

I was inspired by the ikkez Event System library.

https://groups.google.com/d/msg/f3-framework/gKquuIu7Pxo/a81UXkVNFQAJ

I thank him for that!

And I use the concept of the ikkez library.


Also, I had to remake some of the methods that I picked up from Fat Free Framework Base to integrate Dice DI. But you can use it without Dice without making changes :)

This event system need a debug and evolution

I'll add a few more methods later

**Why I decided to make my implementation that is compliant with the ikkez "Event System" library?**

When I studied "Event System" library, I decided to rewrite the "emit" method, for better understand how it works, I decided to benchmark between different event systems, including my simple implementation. And I was confused "Event System" speed compared to other. And I began to develop my implementation to fully reproduce the functionality of Event System but with greater speed of working.

At the moment, I have reproduced the full functionality Event Systems. And also I added a few methods:

"lite" - a challenge only those listeners who recorded "in the same plane."

"snap" - to call only those listeners who recorded "in the same plane and below"

"once" - equivalent to "on", but after the call, the listener will be removed.

Also, there are several overkills (see f3_events.php). Maybe they do not need.

----------

**Added**

===================


Now f3-events fully compatibility with Event System (can pass all ikkez Event System tests).

added method config:

for example:

```
$dispatcher->config('full'); //full emit system
$dispatcher->config('snap'); //cutted emit system
$dispatcher->config('lite'); //lite emit system
```

===================

Now the "search" may be made not only by event name but also by listener and priority. I.e "has" and "off" method now have 2 more variables. This "listener" and "priority".

for example:

```
$dispatcher->on('event', 'Class->method', 1);

$dispatcher->has('event', 'Class->method', 1); //true
$dispatcher->has('event', 'Class->method', 2); //false

$dispatcher->has('event', 'Class->method'); //true
$dispatcher->has('event', 'Class->method2'); //false

$dispatcher->has('event', null, 1); //true
$dispatcher->has('event', null, 2); //false
```

===================

Only for anonymous functions:

You can add listener to the event as an array with id and anonymous function. And search for this anonymous function by id.

for example:

```
$dispatcher->on('event', array('id', function(){}));

$dispatcher->has('event', 'id'); //true
```
