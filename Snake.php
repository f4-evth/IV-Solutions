<?php
/*
 * Вывести массив змейкой на php. Размеры массива M*N. 
 *  
 */

	$M = 5;	// Number of rows
	$N = 6;	// Number of cols	

	// Initialise array
	$matrix = array();
	for( $i = 0; $i < $M; $i++ )
	{
		$matrix[$i] = array();
		for( $j = 0; $j < $N; $j++ )
			$matrix[$i][$j] = 0;
	}
	
	$i = 0; $j = 0;		// Current position
	$di = 1; $dj = 1;	// Direction unit vector
	$c = 1;				// Current cell number

	// The last thing to be modified is j-coord
	//  so we only need to check if N != 0
	while( $N != 0 )
	{	
		// We reuse the same 2 loops for both forward and backward movement along the axis
		// Counters n/m are here for the loop control, whereas j/i is used for the address resolution
		// And since we're counting not the number of elements, but rather number of hops between them,
		//  we should use N-1/M-1 or set n/m to 1 instead of 0
		for( $n = 1; $n < $N; $n++, $j += $dj )
		{
			$matrix[ $i ][ $j ] = $c++;
		}
		if( $i != 0 ) $N -= 1;	// Workaround/Dirtyhack: First line needs to have a head start
								//  as the algorithm increments the index before writing number into cell,
								//  but this very behaviour completely ruins array by shifting 
								//  all consequtive rows to the right by 1
		$dj *= -1;				// Alternate horizontal moving direction

		// Same for the rows
		for( $m = 1; $m < $M; $m++, $i += $di )
		{
			$matrix[ $i ][ $j ] = $c++;
		}
		$M--;	 // Since we already at a line 0 - no correction is needed
		$di *= -1; // Alternate horizontal moving direction
	}

	echo '<table>';
	for( $i = 0; $i < 8 ; $i++ )
	{
		echo '\n<tr>';
		
		for( $j = 0; $j < 8; $j++ )
		{
			echo '<td>'.$matrix[$i][$j].'</td>';
		}
		
		echo '</tr>';
	}
	echo '</table>';

?>
