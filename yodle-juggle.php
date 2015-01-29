<?php
// http://www.yodlecareers.com/puzzles/jugglefest.html

ini_set ("max_execution_time", 300); // SET 300 seconds = 5 minutes

// SET VERBOSE CONDITION
$verbose = isset ($_GET ["verbose"]);

// SET CIRCUIT
$row = isset ($_GET ["row"]) ? $_GET ["row"] : 1970;

$input = "<pre><p>CIRCUIT: " . $row . "</p>";

// SET FILENAME
$filename = isset ($_GET ["filename"]) ? $_GET ["filename"] : "yodle-juggle.txt";
$juggle_test = @file_get_contents ($filename);
if ($juggle_test === false) exit ("INVALID FILENAME:" . $filename);

$input .= "<h2>Input</h2>";
$input .= $juggle_test;

// VARIBALES
$circuits = array ();
$jugglers = array ();

// SET TEST ARRAY OF LINES
$juggle_test = explode ("\r\n", $juggle_test);

// SET TEST ARRAY IN VARIABLES
for ($a = 0; $a < count ($juggle_test); $a ++) {
	$juggle_test [$a] = explode (" ", $juggle_test [$a]);

	if ($juggle_test [$a] [0] == "C") {
		$circuits [] = $juggle_test [$a];

		$b = count ($circuits) - 1;

		$circuits [$b] [2] = explode (":", $circuits [$b] [2]);
		$circuits [$b] [3] = explode (":", $circuits [$b] [3]);
		$circuits [$b] [4] = explode (":", $circuits [$b] [4]);

		$circuits [$circuits [$b] [1]] = array (
		$circuits [$b] [2] [0] => $circuits [$b] [2] [1],
		$circuits [$b] [3] [0] => $circuits [$b] [3] [1],
		$circuits [$b] [4] [0] => $circuits [$b] [4] [1],
		"J" => array (),
		"D" => array ());
		
		unset ($circuits [$b]);
	}
	else if ($juggle_test [$a] [0] == "J") {
		$jugglers [] = $juggle_test [$a];
		$b = count ($jugglers) - 1;
		$jugglers [$b] [2] = explode (":", $jugglers [$b] [2]);
		$jugglers [$b] [3] = explode (":", $jugglers [$b] [3]);
		$jugglers [$b] [4] = explode (":", $jugglers [$b] [4]);
		$jugglers [$b] [5] = explode (",", $jugglers [$b] [5]);

		$jugglers [$jugglers [$b] [1]] = array (
		$jugglers [$b] [2] [0] => $jugglers [$b] [2] [1],
		$jugglers [$b] [3] [0] => $jugglers [$b] [3] [1],
		$jugglers [$b] [4] [0] => $jugglers [$b] [4] [1],
		"C" => $jugglers [$b] [5]);
		
		unset ($jugglers [$b]);
	}
}

unset ($juggle_test);

// CALCULATE DOT PRODUCTS
foreach ($jugglers as $jugglers_key => $jugglers_value) {
	// SET DOT PRODUCT ARRAY
	$jugglers [$jugglers_key] ["D"] = array ();
	
	foreach ($jugglers [$jugglers_key] ["C"] as $jugglers_c_key => $jugglers_c_value) {
		// SET DOT PRODUCT VALUE PER CIRCUIT
		$jugglers [$jugglers_key] ["D"] [$jugglers_c_value] = 
		($circuits [$jugglers_c_value] ["H"] * $jugglers [$jugglers_key] ["H"]) + 
		($circuits [$jugglers_c_value] ["E"] * $jugglers [$jugglers_key] ["E"]) + 
		($circuits [$jugglers_c_value] ["P"] * $jugglers [$jugglers_key] ["P"]);
	}
}

// SORT CIRCUIT DOT PRODUCTS
foreach ($jugglers as $jugglers_key => $jugglers_value) {
	foreach ($jugglers [$jugglers_key] ["D"] as $jugglers_d_key => $jugglers_d_value) {
		$circuits [$jugglers_d_key] ["D"] [$jugglers_key] = $jugglers_d_value;
		arsort ($circuits [$jugglers_d_key] ["D"]);
	}
}

// DETERMINE FIXED JUGGLERS PER CIRCUIT
$team = count ($jugglers) / count ($circuits);

$changes = 1;

/*
CALCULATE PREFERENCES AND PROMOTIONS UNTIL ...
no juggler could switch to a circuit that they prefer more than the one they are assigned to and be a better fit for that circuit than one of the other jugglers assigned to it
*/

while ($changes > 0) {
	$changes = 0;
	foreach ($circuits as $circuits_key => $circuits_value) {
		foreach ($circuits [$circuits_key] ["D"] as $circuits_d_key => $circuits_d_value) {
			$juggler_key = $circuits_d_key;

			// IF $CIRCUIT J HAS LESS THAN $TEAM && PREFERRED: PROMOTE $JUGGLER
			if (count ($circuits [$circuits_key] ["J"]) < $team 
			&& preferred ($juggler_key, $circuits_key)) {
				$changes ++;
				promote ($juggler_key, $circuits_key);
			}
		}
	}
}

// OUTPUT
$output = "<h2>Output</h2>";
$email = 0;

$circuit_row_juggler_values = array ();
	
foreach ($circuits as $circuits_key => $circuits_value) {
	$output .= "$circuits_key";

	foreach ($circuits [$circuits_key] ["J"] as $circuits_j_key => $circuits_j_value) {
		/*
		CALCULATE ...
		the sum of the names of the jugglers (taking off the leading letter J) that are assigned to row/CIRCUIT
		*/
		$circuits_row = str_replace ("C", "", $circuits_key);
		
		if ($row == $circuits_row) {
			$number = str_replace ("J", "", $circuits_j_key);
			$number += 0; // INTEGER
			$email += $number;
			
			$circuit_row_juggler_values [] = $number;
		}

		$output .= " $circuits_j_key";
		foreach ($jugglers [$circuits_j_key] ["D"] as $jugglers_d_key => $jugglers_d_value) {
			$array = array ($jugglers_d_key, $jugglers_d_value);
			$output .= " " . implode (":", $array);
		}
		$output .= ",";
	}
	
	$output = rtrim ($output, ",");
	$output .= PHP_EOL;
}

if ($verbose) {
	echo $input;
	echo $output;
}

echo "<p>C$row Juggler Values: ";
echo implode (", ", $circuit_row_juggler_values) . "</p>";	

echo "<h1>EMAIL: $email@yodle.com</h1>";

$filename = explode (".", $filename);
$filename = $filename [0];

file_put_contents ($filename . "-answered-" . time () . ".txt", $output);

function preferred ($juggler_key, $circuit_key) {
	global $circuits, $jugglers;
	$origin = array_search ($circuit_key, $jugglers [$juggler_key] ["C"]);
	if ($origin == 0) return true;
	
	$origin --;
	
	for ($origin; $origin >= 0; $origin --) {
		$check = $jugglers [$juggler_key] ["C"] [$origin];
		
		if (isset ($circuits [$check] ["J"] [$juggler_key])) {
			return false;
		}
	}
	
	return true;
}

function promote ($juggler_key, $circuit_key) {
	global $circuits, $jugglers;
	// MOVE JUGGLER FROM CIRCUIT [D] TO [J]
	$circuits [$circuit_key] ["J"] [$juggler_key] = $circuits [$circuit_key] ["D"] [$juggler_key];
	arsort ($circuits [$circuit_key] ["J"]);
	
	// REMOVE CIRCUIT [KEY] [D] [JUGGLER_KEY]
	unset ($circuits [$circuit_key] ["D"] [$juggler_key]);
	
	// REMOVE OTHER CIRCUIT [J] INSTANCES OF JUGGLER_KEY
	foreach ($circuits as $circuits_key => $circuit_value) {
		if ($circuit_key != $circuits_key 
		&& isset ($circuits [$circuits_key] ["J"] [$juggler_key])) {
			unset ($circuits [$circuits_key] ["J"] [$juggler_key]);
			arsort ($circuits [$circuits_key] ["J"]);
		}
	}
}
?>