<?php
// Code to randomly generate conjoint profiles to send to a Qualtrics instance

// Terminology clarification: 
// Task = Set of choices presented to respondent in a single screen (i.e. pair of candidates)
// Profile = Single list of attributes in a given task (i.e. candidate)
// Attribute = Category characterized by a set of levels (i.e. education level)
// Level = Value that an attribute can take in a particular choice task (i.e. "no formal education")

// Attributes and Levels stored in a 2-dimensional Array 

// Function to generate weighted random numbers
function weighted_randomize($prob_array, $at_key)
{
	$prob_list = $prob_array[$at_key];
	
	// Create an array containing cutpoints for randomization
	$cumul_prob = array();
	$cumulative = 0.0;
	for ($i=0; $i<count($prob_list); $i++){
		$cumul_prob[$i] = $cumulative;
		$cumulative = $cumulative + floatval($prob_list[$i]);
	}

	// Generate a uniform random floating point value between 0.0 and 1.0
	$unif_rand = mt_rand() / mt_getrandmax();

	// Figure out which integer should be returned
	$outInt = 0;
	for ($k = 0; $k < count($cumul_prob); $k++){
		if ($cumul_prob[$k] <= $unif_rand){
			$outInt = $k + 1;
		}
	}

	return($outInt);

}
                    

$featurearray = array("Border Security" => array("Increase spending on border security","Decrease spending on border security","Not change its level of spending on border security"),"Family Separation" => array("Parents and children who are in immigrant detention cannot be separated","Parents and children who are in immigrant detention can be separated"),"Pathway to Citizenship" => array("Allows undocumented individuals to apply for temporary legal status, with the ability to apply for green cards after five years","Does not include a pathway to citizenship for undocumented immigrants","Allow undocumented individuals who arrived in the U.S. as children to apply for green cards","No pathway for citizenship, but undocumented individuals are granted deferred deportation for two years","No pathway for citizenship, but undocumented individuals who arrived in the U.S. as children are granted deferred deportation for two years"),"Funding for Immigrant Integration" => array("Provides funding to local and state governments for immigrant integration and citizenship programs","Does not provide funding to local and state governments for immigrant integration and citizenship programs"),"Improve Immigration Courts" => array("Increase immigration court judges's discretion to rule in deportation and asylum cases","Decrease immigration court judges's discretion to rule in deportation and asylum cases","Keep immigration court judges's discretion to rule in deportation and asylum cases the same"),"Criminalization of Border Crossing" => array("Unauthorized border crossings will be prosecuted as criminal offenses","Unauthorized border crossings will not be prosecuted as criminal offenses"),"Guestworker Visas" => array("When allocating temporary work visas to foreign nationals, the U.S. will prioritize high-skilled workers","When allocating temporary work visas to foreign nationals, the U.S. will treat all workers equally regardless of their skill level"),"Available Visas" => array("Increase number of guestworker visas and diversity visas","Decrease number of guestworker visas and diversity visas","Keep number of guestworker visas and diversity visas the same"),"Asylum Applications" => array("Increase funding to reduce asylum application backlogs","Decrease funding to reduce asylum application backlogs","Keep funding to reduce asylum application backlogs the same"),"Address Causes of Migration" => array("Increase financial assistance to El Salvador, Guatemala, and Honduras","Decrease financial assistance to El Salvador, Guatemala, and Honduras","Keep financial assistance to El Salvador, Guatemala, and Honduras the same"),"Combat Transnational Crime" => array("Expands transnational anti-gang task forces in Central America","No expansion to transnational anti-gang task forces"));

$restrictionarray = array();

// Indicator for whether weighted randomization should be enabled or not
$weighted = 0;

// K = Number of tasks displayed to the respondent
$K = 5;

// N = Number of profiles displayed in each task
$N = 2;

// num_attributes = Number of Attributes in the Array
$num_attributes = count($featurearray);

// Should duplicate profiles be rejected?
$noDuplicateProfiles = False;


$attrconstraintarray = array();


// Re-randomize the $featurearray

// Place the $featurearray keys into a new array
$featureArrayKeys = array();
$incr = 0;

foreach($featurearray as $attribute => $levels){	
	$featureArrayKeys[$incr] = $attribute;
	$incr = $incr + 1;
}

// Backup $featureArrayKeys
$featureArrayKeysBackup = $featureArrayKeys;

// If order randomization constraints exist, drop all of the non-free attributes
if (count($attrconstraintarray) != 0){
	foreach ($attrconstraintarray as $constraints){
		if (count($constraints) > 1){
			for ($p = 1; $p < count($constraints); $p++){
				if (in_array($constraints[$p], $featureArrayKeys)){
					$remkey = array_search($constraints[$p],$featureArrayKeys);
					unset($featureArrayKeys[$remkey]);
				}
			}
		}
	}
} 
// Re-set the array key indices
$featureArrayKeys = array_values($featureArrayKeys);
// Re-randomize the $featurearray keys
shuffle($featureArrayKeys);

// Re-insert the non-free attributes constrained by $attrconstraintarray
if (count($attrconstraintarray) != 0){
	foreach ($attrconstraintarray as $constraints){
		if (count($constraints) > 1){
			$insertloc = $constraints[0];
			if (in_array($insertloc, $featureArrayKeys)){
				$insert_block = array($insertloc);
				for ($p = 1; $p < count($constraints); $p++){
					if (in_array($constraints[$p], $featureArrayKeysBackup)){
						array_push($insert_block, $constraints[$p]);
					}
				}
				
				$begin_index = array_search($insertloc, $featureArrayKeys);
				array_splice($featureArrayKeys, $begin_index, 1, $insert_block);
			}
		}
	}
}


// Re-generate the new $featurearray - label it $featureArrayNew

$featureArrayNew = array();
foreach($featureArrayKeys as $key){
	$featureArrayNew[$key] = $featurearray[$key];
}
// Initialize the array returned to the user
// Naming Convention
// Level Name: F-[task number]-[profile number]-[attribute number]
// Attribute Name: F-[task number]-[attribute number]
// Example: F-1-3-2, Returns the level corresponding to Task 1, Profile 3, Attribute 2 
// F-3-3, Returns the attribute name corresponding to Task 3, Attribute 3

$returnarray = array();

// For each task $p
for($p = 1; $p <= $K; $p++){

	// For each profile $i
	for($i = 1; $i <= $N; $i++){

		// Repeat until non-restricted profile generated
		$complete = False;

		while ($complete == False){

			// Create a count for $attributes to be incremented in the next loop
			$attr = 0;
			
			// Create a dictionary to hold profile's attributes
			$profile_dict = array();

			// For each attribute $attribute and level array $levels in task $p
			foreach($featureArrayNew as $attribute => $levels){	
				
				// Increment attribute count
				$attr = $attr + 1;

				// Create key for attribute name
				$attr_key = "F-" . (string)$p . "-" . (string)$attr;

				// Store attribute name in $returnarray
				$returnarray[$attr_key] = $attribute;

				// Get length of $levels array
				$num_levels = count($levels);

				// Randomly select one of the level indices
				if ($weighted == 1){
					$level_index = weighted_randomize($probabilityarray, $attribute) - 1;

				}else{
					$level_index = mt_rand(1,$num_levels) - 1;	
				}	

				// Pull out the selected level
				$chosen_level = $levels[$level_index];
			
				// Store selected level in $profileDict
				$profile_dict[$attribute] = $chosen_level;

				// Create key for level in $returnarray
				$level_key = "F-" . (string)$p . "-" . (string)$i . "-" . (string)$attr;

				// Store selected level in $returnarray
				$returnarray[$level_key] = $chosen_level;

			}

			$clear = True;
			// Cycle through restrictions to confirm/reject profile
			if(count($restrictionarray) != 0){

				foreach($restrictionarray as $restriction){
					$false = 1;
					foreach($restriction as $pair){
						if ($profile_dict[$pair[0]] == $pair[1]){
							$false = $false*1;
						}else{
							$false = $false*0;
						}
						
					}
					if ($false == 1){
						$clear = False;
					}
				}
			}
            // Cycle through all previous profiles to confirm no identical profiles
            if ($noDuplicateProfiles == True){
    			if ($i > 1){
    
    				// For each previous profile
    				for($z = 1; $z < $i; $z++){
    					
    					// Start by assuming it's the same
    					$identical = True;
    					
    					// Create a count for $attributes to be incremented in the next loop
    					$attrTemp = 0;
    					
    					// For each attribute $attribute and level array $levels in task $p
    					foreach($featureArrayNew as $attribute => $levels){	
    						
    						// Increment attribute count
    						$attrTemp = $attrTemp + 1;
    
    						// Create keys 
    						$level_key_profile = "F-" . (string)$p . "-" . (string)$i . "-" . (string)$attrTemp;
    						$level_key_check = "F-" . (string)$p . "-" . (string)$z . "-" . (string)$attrTemp;
    						
    						// If attributes are different, declare not identical
    						if ($returnarray[$level_key_profile] != $returnarray[$level_key_check]){
    							$identical = False;
    						}
    					}
    					// If we detect an identical profile, reject
    					if ($identical == True){
    						$clear = False;
    					}
    				} 
                }
            }
			$complete = $clear;
		}
	}


}

// Return the array back to Qualtrics
print  json_encode($returnarray);
?>
