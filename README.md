# f3-events

Hello everyone by Ayrat :)

I was inspired by the ikkez Event System library.

https://groups.google.com/d/msg/f3-framework/gKquuIu7Pxo/a81UXkVNFQAJ

I thank him for that!

And I use the concept of the ikkez library.


Also, I had to remake some of the methods that I picked up from Fat Free Framework Base to integrate Dice DI. But you can use it without Dice without making changes :)

This event system need a debug and evolution

I'll add a few more methods later

** Why I decided to make my implementation that is compliant with the ikkez "Event System" library?**
When I studied "Event System" library, I decided to rewrite the calling method, for better understand how it works, I decided to benchmark between different event systems, including my simple implementation. And I was confused "Event System" speed compared to other. And I began to develop my implementation to fully reproduce the functionality of Event System but with greater speed of working.

At the moment, I have reproduced the full functionality Event Systems. And also I added a few methods:

"lite" - a challenge only those listeners who recorded "in the same plane."

"snap" - to call only those listeners who recorded "in the same plane and below"

"once" - equivalent to "on", but after the call, the listener will be removed.
