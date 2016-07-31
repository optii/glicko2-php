<?php

/*******************************************************************************

glicko-2 ranking system

Written by Noah Smith 2011, June 7
megiddo ( @t ) thirdform ( dot ) com

Based on http://www.glicko.net/glicko/glicko2.pdf

Usage
Glicko2Player([$rating = 1500 [, $rd = 350 [, $volatility = 0.06 [, $mu [, $phi [, $systemconstant = 0.50 ]]]]]])
	For new players, use the default values for rating, rd, and volatility.
	The systemconstant should be between 0.3 and 1.2, depending on system itself (this is game dependent, and must be set
		by estimation or experimentation)
		
Updating a Glicko2Player

Add wins, losses, and draws to a player:

$Todd = new Glicko2Player(1500,200);
$Alice = new Glicko2Player(1400,30);
$Bob = new Glicko2Player(1550,100);
$Charlie = new Glicko2Player(1700,300);

$Todd->AddWin($Alice);
$Todd->AddLoss($Bob);
$Todd->AddLoss($Charlie);
$Todd->Update();

var_dump($Todd);
var_dump($Alice);
$Alice->Update();
var_dump($Alice);

This message and the following may not be removed or modified:

Caveat Emptor
	I make no assertions that either Glicko-2 or this code are correct.  Use at your own risk.

*******************************************************************************/

class Glicko2Player {
	public  $rating;
	public  $rd;
	public  $vol;
	
	public  $mu;
	public  $phi;
	public  $tau;
	
	public  $wins = 0;
	public  $losses = 0;
	
	private $M = array();

	function __construct($rating = 1500, $rd = 350, $volatility = 0.06, $mu = null, $phi = null, $systemconstant = 0.5) {
		
		// Step 1
		$this->rating = $rating;
		$this->rd = $rd;
		// volatility
		$this->vol = $volatility;

		// System Constant
		$this->tau = $systemconstant;

		// Step 2
		// Rating
		if (is_null($mu)) {
			$this->mu = ( $this->rating - 1500 ) / 173.7178;
		} else {
			$this->mu = $mu;
		}
		// Rating Deviation
		if (is_null($phi)) {
			$this->phi = $this->rd / 173.7178;
		} else {
			$this->phi = $phi;
		}
	}

	function AddWin($OtherPlayer) {
		array_push($this->M, $OtherPlayer->MatchElement(1));
		$this->wins++;
	}

	function AddLoss($OtherPlayer) {
		array_push($this->M, $OtherPlayer->MatchElement(0));
		$this->losses++;
	}

	function AddDraw($OtherPlayer) {
		array_push($this->M, $OtherPlayer->MatchElement(0.5));
		$this->draws++;
	}

	function Update() {
		$Results = $this->AddMatches();
		$this->rating = $Results['r'];
		$this->rd = $Results['RD'];
		$this->mu = $Results['mu'];
		$this->phi = $Results['phi'];
		$this->vol = $Results['vol'];
		$this->M = array();
	}

	function MatchElement($score) {
		return array( 'mu' => $this->mu, 'phi' => $this->phi, 'score' => $score );
	}

	function AddMatches() {
		global $tausq;
		global $phsq;
		global $deltasq;
		global $a;
		global $v;


		if (count($this->M) == 0) {
			$phi_p = sqrt( ( $this->phi * $this->phi ) + ( $this->vol * $this->vol ) );
			return array( 'r' => $this->rating, 'RD' => 173.7178 * $phi_p, 'mu' => $this->mu, 'phi' => $phi_p, 'vol' => $this->vol ) ;
		}


		
		// Step 3 & 4 & 7
		// Estimated variance
		$v = 0;
		$v_summation = 0;
		// Estimated improvment in rating
		$delta = 0;
				
		// New mu
		$mu_p = 0;

		$delta_and_mu_p_summation = 0;

		for ($j = 0; $j < count($this->M); $j++) {
			$E = $this->E( $this->mu, $this->M[$j]['mu'], $this->M[$j]['phi'] );
			$g = $this->g( $this->M[$j]['phi'] );
			$v_summation +=   ( $g * $g * $E * ( 1 - $E ) );
			
			$delta_and_mu_p_summation += $g * ( $this->M[$j]['score'] - $E );
		}
		
		$v = 1 / $v_summation;
 		
		// Step 4 (finalize)
		
		$delta = $v * $delta_and_mu_p_summation;

		// Step 5

		$tausq = $this->tau * $this->tau;
		$phsq = $this->phi * $this->phi;
		$deltasq = $delta * $delta;

		$A = $a = log($this->vol * $this->vol);
		if($deltasq > ($phsq + $v)) {
			$B = log($deltasq - $phsq - $v);
		} else {			
			$k = 1;
			while($this->f($a - $k * sqrt($tausq)) < 0) {
				$k++;
			}
				$B = $a - $k * sqrt($tausq);
		}

		
		$fA = $this->f($A);
		$fB = $this->f($B);

		$epi = 0.000001;

		while(abs($B-$A) > $epi) {
			$C = $A + $fA *($A-$B) / ($fB - $fA);
			$fC = $this->f($C);
			if($fC*$fB < 0) {
				$A = $B;
				$fA = $fB;
			} else {
				$fA = $fA / 2;
			}
			
			$B = $C;
			$fB = $fC;
		}
		
		$vol_p = exp( $A / 2 );

		// Step 6
		$phi_star = sqrt( $phsq + ( $vol_p * $vol_p ) );
		

		// Step 7
		$phi_p = 1.0 / ( sqrt( ( 1.0 / ( $phi_star * $phi_star ) ) + ( 1.0 / $v ) ) );

		$mu_p = $this->mu + $phi_p * $phi_p * $delta_and_mu_p_summation;

		return array( 'r' => ( 173.7178 * $mu_p ) + 1500, 'RD' => 173.7178 * $phi_p, 'mu' => $mu_p, 'phi' => $phi_p, 'vol' => $vol_p ) ;
	}

	function f($x) {
		global $tausq;
		global $phsq;
		global $deltasq;
		global $a;
		global $v;

	    return ((
			(exp($x)*($deltasq - $phsq - $v - exp($x))) 
						/ 
			(2* pow( ($phsq + $v + exp($x) ),2))
	    ) - (
			($x - $a) / $tausq
	    ));
	}

	
	function g($phi) {
		return 1.0 / ( sqrt( 1.0 + ( 3.0 * $phi * $phi) / ( pi() * pi() ) ) );
	}

	function E($mu, $mu_j, $phi_j) {
		return 1.0 / ( 1.0 + exp( -$this->g($phi_j) * ( $mu - $mu_j ) ) );
	}
}

?>
