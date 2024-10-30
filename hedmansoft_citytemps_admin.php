<?php 
	$hs_cityTempConfiguration = array();
    if(get_option("hs_city_temp_configuration") != null) {
		$hs_cityTempConfiguration = get_option("hs_city_temp_configuration");
    }
    $cityArray = array();
    if($hs_cityTempConfiguration['hs_cityArray'] != null) {
		$cityArray = $hs_cityTempConfiguration['hs_cityArray'];
	}
    $celcius = 'false';
    $citiesToDisplayInWidget = 0;		//This is the number of items in the city list to display randomly (should be <= the array list)
	$size = count($cityArray);
	$postAction = null;
    if($_POST['hscitytemp_action']) {
		wp_verify_nonce('hscitytemp_nonce', 'post-hscitytemp-data');
		check_admin_referer('post-hscitytemp-data', 'hscitytemp_nonce' );
		$postAction = sanitize_text_field($_POST['hscitytemp_action']);
		$citiesToDisplayInWidget = $hs_cityTempConfiguration['hs_citiestodisplay'];
	    if($postAction == 'P') {
	        //Form data sent
			$homeCity = new hscitytempCity();
	        $homeCity->cityName = sanitize_text_field($_POST['homename']);
			$homeCity->cityID = sanitize_text_field($_POST['homecityid']);    
	        $hs_cityTempConfiguration['hs_homeCity'] = $homeCity;
	         
	        $celcius = sanitize_text_field($_POST['hscelcius']);
	        $hs_cityTempConfiguration['hs_celcius'] = $celcius;
	        
	        $citiestextdisplay = sanitize_text_field($_POST['citiestextdisplay']);
	        $hs_cityTempConfiguration['citiestextdisplay'] = $citiestextdisplay;
	        
	 		//Look for changes in the city list
			unset($cityArray);
			for($i = 0; $i < $size; ++$i) {
	        	$cityname = sanitize_text_field($_POST['cname'.$i]);
	        	$cid = sanitize_text_field($_POST['cid'.$i]);
				$buildCity = new hscitytempCity();
				$buildCity->cityName=$cityname;
				$buildCity->cityID=$cid;
				$cityArray[$i] = $buildCity;
	        }
			$sanitizedValue = sanitize_text_field($_POST['citiestodisplay']);
	        $citiesToDisplayInWidget = intval($sanitizedValue);
	        $hs_cityTempConfiguration['hs_citiestodisplay'] = $citiesToDisplayInWidget;

	        ?>
	        <div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
	        <?php
		} else if($postAction == 'R') {
			//REMOVE A ROW - also rebuild other rows for possible changes
			$ordinal = sanitize_text_field($_POST['hscitytemp_actionOrdinal']);
			$index = 0;
			unset($cityArray);
			for($i = 0; $i < $size; ++$i) {
				if($i != intval($ordinal)) {
		        	$cityname = sanitize_text_field($_POST['cname'.$i]);
		        	$cid = sanitize_text_field($_POST['cid'.$i]);
					$buildCity = new hscitytempCity();
					$buildCity->cityName=$cityname;
					$buildCity->cityID=$cid;
					$cityArray[$index] = $buildCity;
					$index ++;
				}
				else {
					//Skip this row
				}
			}
		} else if($postAction == 'A') {
			//ADD A NEW ROW - also rebuild other rows for possible changes
			unset($cityArray);
			for($i = 0; $i < $size; ++$i) {
	        	$cityname = sanitize_text_field($_POST['cname'.$i]);
	        	$cid = sanitize_text_field($_POST['cid'.$i]);
				$buildCity = new hscitytempCity();
				$buildCity->cityName=$cityname;
				$buildCity->cityID=$cid;
				$cityArray[$i] = $buildCity;
			}
			$ordinal = sanitize_text_field($_POST['hscitytemp_actionOrdinal']);
			$buildCity = new hscitytempCity();
			$buildCity->cityName=sanitize_text_field($_POST['cname'.$ordinal]);
			$buildCity->cityID=sanitize_text_field($_POST['cid'.$ordinal]);
			$cityArray[$ordinal] = $buildCity;
    	}
	}
	if($postAction == 'C') {
		//Clear all values
		delete_option("hs_city_temp_configuration");
		delete_transient("hs_city_temps");
		$cityArray = array();
		$citiesToDisplayInWidget = 0;
		$citiestextdisplay = null;
		$homeCity = new hscitytempCity();
		$size = count($cityArray);
		?><div class="updated"><p><strong><?php _e('Options reset to empty values.' ); ?></strong></p></div><?php
	}
	else {
	    $homeCity = new hscitytempCity();
	    if($hs_cityTempConfiguration['hs_homeCity'] != null) {
	    	$homeCity = $hs_cityTempConfiguration['hs_homeCity'];
	    }
	    if($hs_cityTempConfiguration['hs_celcius'] != null) {
	    	$celcius = $hs_cityTempConfiguration['hs_celcius'];
	    }
	    $citiestextdisplay = $hs_cityTempConfiguration['citiestextdisplay'] != null?$hs_cityTempConfiguration['citiestextdisplay']:null;
	    $hs_cityTempConfiguration['hs_cityArray'] = $cityArray;
		$size = count($cityArray);
	    if($citiesToDisplayInWidget > $size) {
		    $citiesToDisplayInWidget = $size;
		    ?><div class="updated"><p><strong><?php _e('Options saved.  Number of Cities to Display was reduced to '.$size.' based on your revised list.' ); ?></strong></p></div><?php
		}
	    $hs_cityTempConfiguration['hs_citiestodisplay'] = $citiesToDisplayInWidget;
		update_option('hs_city_temp_configuration',$hs_cityTempConfiguration);
	}	
?>
<div class="wrap">

    <?php    echo "<h2>" . __( 'HedmanSoft City Temps Admin Options', 'hs_trans' )."</h2>"; ?>
     
    <form name="hscitytemp_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
        <input type="hidden" name="hscitytemp_action" value="P">
        <input type="hidden" name="hscitytemp_actionOrdinal" >
        <?php    echo "<h4>" . __( 'Home Location', 'hs_trans' ) . "</h4>"; ?>
        <p><div id="homenamediv"><?php _e("Home Display Name: " ); ?><input type="text" name="homename" value="<?php echo stripcslashes($homeCity->cityName); ?>" size="20">&nbsp;<?php _e(" ex: Boston MA" ); ?></div></p>
        <p><div id="homecityiddiv"><?php _e("Home ID: " ); ?><input type="text" name="homecityid" value="<?php echo $homeCity->cityID; ?>" size="10"><?php _e(" ex: 2367105" ); ?>&nbsp;<a href="" onclick="window.open('http://woeid.rosselliot.co.nz/'); return false;">(Click here to find location IDs.)</a></div></p>
        <p><?php _e("Display Celcius: " ); ?>
			<input type="radio" name="hscelcius" value="true" <?php if($celcius === 'true') echo ' checked="checked"'?>>Celcius&nbsp
			<input type="radio" name="hscelcius" value="false" <?php if($celcius === 'false') echo ' checked="checked"'?>>Fahrenheit
		</p>
        <p><div id="citiestodisplaydiv"><?php _e("Number of Cities to Display in Widget: " ); ?><input type="text" name="citiestodisplay" value="<?php echo $citiesToDisplayInWidget; ?>" size="2">&nbsp;<?php _e(" ex: 4" ); ?></div></p>
     
        <hr/>
        <p><div id="citiestextdisplaydiv"><?php _e("Header Text for Cities (optional): " ); ?><input type="text" name="citiestextdisplay" value="<?php echo stripcslashes($citiestextdisplay); ?>" size="40">&nbsp;<?php _e(" ex: Other temperatures in the area:" ); ?></div></p>
        <?php    
        echo "<table><tr><td colspan='4' align='center'>List of Cities and Yahoo City IDs</td></tr>";
    	echo "<tr><td></td>";
    	echo "<td>City Display Name</td>";
    	echo "<td>Yaho City ID</td>";
    	echo "<td>Action</td>";
    	echo "</tr>";
		for($i = 0; $i < $size; ++$i) {
			$rowNumber = 0 + $i + 1;
        	echo "<tr>";
        	echo "<td>".$rowNumber."</td>";
        	echo "<td><div id='cname".$i."'><input type='text' name='cname".$i."' value='".stripcslashes($cityArray[$i]->cityName)."'></div></td>";
        	echo "<td><div id='cid".$i."'><input type='text' name='cid".$i."' value='".$cityArray[$i]->cityID."'></div></td>";
        	echo "<td><a href='' onclick='hscitytempRemoveMe(".$i.");return false;'>Remove</a></td>";
        	echo "</tr>";
        }
        $rowNumber = 0 + $size + 1;
    	echo "<tr>";
    	echo "<td>".($rowNumber)."</td>";
    	echo "<td><div id='cname".$size."'><input type='text' name='cname".$size."'></div></td>";
    	echo "<td><div id='cid".$size."'><input type='text' name='cid".$size."'></div></td>";
    	echo "<td><a href='' onclick='hscitytempAddMe(".$size.");return false;'>Add</a></td>";
    	echo "</tr>";
        echo "</table>";
		wp_nonce_field( 'post-hscitytemp-data', 'hscitytemp_nonce' );
        ?>
        <p class="submit">
        <input type="button" onClick="hscitytempSubmitMe()" name="Submit" value="<?php _e('Update Options', 'hs_trans' ) ?>" />
        <input type="button" onClick="hscitytempClearValues()" name="Reset" value="<?php _e('Reset All Values', 'hs_trans' ) ?>" />
        </p>
        
    </form>
    <script language="javascript">
    var theForm = window.document.hscitytemp_form;
    var tableSize = <?php echo $size ?>;
    function hscitytempRemoveMe(what) {
    	theForm.hscitytemp_action.value="R";
    	theForm.hscitytemp_actionOrdinal.value=what;
    	theForm.submit();
    }
    function hscitytempAddMe(what) {
    	theForm.hscitytemp_action.value="A";
    	theForm.hscitytemp_actionOrdinal.value=what;
    	var issues = "";
		if(eval('theForm.cname'+tableSize).value == "") {
    		issues += "You need to provide a home name for row " + (i+1) + ".\n";
    		window.document.getElementById("cname"+tableSize).style.borderColor = "red";
    		window.document.getElementById("cname"+tableSize).style.borderStyle = "solid";
		}
		cityid = eval('theForm.cid'+tableSize).value;
		if(!hscitytempIsNumeric(cityid)) {
    		issues += "City ID must be entered and numeric for row " + (i+1) + ".\n";
    		window.document.getElementById("cid"+tableSize).style.borderColor = "red";
    		window.document.getElementById("cid"+tableSize).style.borderStyle = "solid";
		}
    	if(issues != "") {
    		alert(issues);
    		return false;
    	}
    	theForm.submit();
    }
    
    function hscitytempClearValues() {
    	theForm.hscitytemp_action.value="C";
    	if(confirm("Are you sure you want to reset all values?")) {
    		theForm.submit();
    	}
    }
    	
    function hscitytempSubmitMe() {
    	//Check form values for valid entries
    	theForm.hscitytemp_action.value="P";
    	var issues = "";
		window.document.getElementById("homenamediv").style.borderColor = "initial";
		window.document.getElementById("homenamediv").style.borderStyle = "none";
		window.document.getElementById("homecityiddiv").style.borderColor = "initial";
		window.document.getElementById("homecityiddiv").style.borderStyle = "none";
		window.document.getElementById("citiestodisplaydiv").style.borderColor = "initial";
		window.document.getElementById("citiestodisplaydiv").style.borderStyle = "none";
    	for(i=0; i<tableSize; i++) {
    		window.document.getElementById("cname"+i).style.borderColor = "initial";
    		window.document.getElementById("cname"+i).style.borderStyle = "none";
    		window.document.getElementById("cid"+i).style.borderColor = "initial";
    		window.document.getElementById("cid"+i).style.borderStyle = "none";
    	}
    	if(theForm.homename.value == "") {
    		issues += "You need to provide a home name.\n";
    		window.document.getElementById("homenamediv").style.borderColor = "red";
    		window.document.getElementById("homenamediv").style.borderStyle = "solid";
    	}
    	var cityid = theForm.homecityid.value;
    	var displCount = theForm.citiestodisplay.value;
    	if(!hscitytempIsNumeric(cityid)) {
    		issues += "Home city ID must be entered and numeric.\n";
    		window.document.getElementById("homecityiddiv").style.borderColor = "red";
    		window.document.getElementById("homecityiddiv").style.borderStyle = "solid";
    	}
    	if(displCount > tableSize || displCount < 0) {
    		issues += "Number of Cities to Display cannot be greater than the number of cities you have registered or less than zero.\n";
    		window.document.getElementById("citiestodisplaydiv").style.borderColor = "red";
    		window.document.getElementById("citiestodisplaydiv").style.borderStyle = "solid";
    	}
    	for(i=0; i<tableSize; i++) {
    		if(eval('theForm.cname'+i).value == "") {
	    		issues += "You need to provide a home name for row " + (i+1) + ".\n";
	    		window.document.getElementById("cname"+i).style.borderColor = "red";
	    		window.document.getElementById("cname"+i).style.borderStyle = "solid";
    		}
    		cityid = eval('theForm.cid'+i).value;
    		if(!hscitytempIsNumeric(cityid)) {
	    		issues += "City ID must be entered and numeric for row " + (i+1) + ".\n";
	    		window.document.getElementById("cid"+i).style.borderColor = "red";
	    		window.document.getElementById("cid"+i).style.borderStyle = "solid";
    		}
    	}
    	if(issues != "") {
    		alert(issues);
    		return false;
    	}
    	theForm.submit();
    }
    function hscitytempIsNumeric(val) {
    	if(val == "") return false;
    	var numbers = new RegExp(/[-+]?([0-9]*\.[0-9]+|[0-9]+)/);
    	var matches = numbers.exec(val);
    	if(matches == null || matches[0] == null) return false;
    	if(matches[0].length != val.length) return false;
    	return true;
    }
    
    </script>
    	
</div>

