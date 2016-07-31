glicko2-php
===========

A PHP implementation of [Glicko2][1], a rating system for game ladders.
Glicko2 is a refinement of the well-known [Elo][2] rating system that adds
the concepts of rating deviation, volatility, and rating decay.

This is a fork of Guangcong Luo's version, going straight from the [PDF][3] and trying
to match exactly what the PDF states. I also confirmed the test case mentioned
in the PDF was accurate, I believe Luo's version was not accurate - Therefore
I created this. This also keeps tracks of wins/losses/draws for players.

The original glicko2phplib was written by Noah Smith on 2011 June 7. That
version contains significant mathematical errors. This is a fork by Guangcong
Luo on 2012 Sept 28 that corrects these errors, and also updates it to be
compatible with PHP 5. It should also work on PHP 4, although I have not
tested that.

 [1]: http://en.wikipedia.org/wiki/Glicko_rating_system
 [2]: http://en.wikipedia.org/wiki/Elo_rating_system
 [3]: http://www.glicko.net/glicko/glicko2.pdf

Usage
-----

	Glicko2Player([$rating = 1500 [, $rd = 350 [, $volatility = 0.06 [, $mu [, $phi [, $systemconstant = 0.50 ]]]]]])

For new players, use the default values for `rating`, `rd`, and `volatility`.

The `systemconstant` should be between 0.3 and 1.2, depending on system itself
(this is game dependent, and must be set by estimation or experimentation)

Updating a Glicko2Player
------------------------

Add wins, losses, and draws to a player:

	$Alice = new Glicko2Player();
	$Bob = new Glicko2Player();
	$Charlie = new Glicko2Player();
	$David = new Glicko2Player();

	$Alice->AddWin($Bob);
	$Alice->AddWin($Charlie)

	$Bob->AddLoss($Alice);
	$Bob->AddWin($Charlie);

	$Charlie->AddLoss($Alice);
	$Charlie->AddLoss($Bob);

	$Alice->Update();
	$Bob->Update();
	$Charlie->Update();
	$David->Update(); // David did not participate, but must be updated


PDF Test case (see: http://www.glicko.net/glicko/glicko2.pdf)
------------------------

    $Todd = new Glicko2Player(1500,200);
    $Alice = new Glicko2Player(1400,30);
    $Bob = new Glicko2Player(1550,100);
    $Charlie = new Glicko2Player(1700,300);

    $Todd->AddWin($Alice);
    $Todd->AddLoss($Bob);
    $Todd->AddLoss($Charlie);
    $Todd->Update();

    var_dump($Todd);
    /*
    object(Glicko2Player)#1 (9) {
      ["rating"]=> float(1464.0506705393)
      ["rd"]=> float(151.51652412386)
      ["vol"]=> float(0.059995984286488)
      ["mu"]=> float(-0.20694096667525)
      ["phi"]=> float(0.87219918813073)
      ["tau"]=> float(0.5)
      ["wins"]=> int(1)
      ["losses"]=> int(2)
      ["M":"Glicko2Player":private]=> array(0) {}
    }
    */

License
-------

[GNU LGPL version 3.0][3]

 [3]: http://www.gnu.org/copyleft/lesser.html
