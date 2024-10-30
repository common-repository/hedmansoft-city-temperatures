<?php 
    /*
    Plugin Name: HedmanSoft City Temperatures
    Plugin URI: https://hedmansoft.com/city-temperatures-plugin/
    Description: Plugin for displaying temperatures from Yahoo Weather for random locations in one widget
    Author: D. Hedman
    Version: 1.0
    Author URI: http://www.hedmansoft.com
    */

class hscitytempCity {
	public $cityName;
	public $cityID;
	public $currentTemp;
	public $currentWeatherCondition;
	public $weatherIcon;
	public $timeStamp;
	public $cityForecastArray;
}
class hscitytempCityForecast {
	public $minTemp;
	public $maxTemp;
	public $dayOfWeek;
}
	

function hscitytemps_admin() {
    include('hedmansoft_citytemps_admin.php');
}
function hs_citytemps_admin_actions() {
 add_options_page("HedmanSoft City Temps", "HedmanSoft City Temps", 1, "HedmanSoft_City_Temps", "hscitytemps_admin");
}

function hs_citytemps_stylesheet( $posts ) {
	wp_enqueue_style( 'hs_citytemps', plugins_url( '/hs_citytemps.css', __FILE__ ) );
}
 
add_action('admin_menu', 'hs_citytemps_admin_actions');

add_action('wp_enqueue_scripts', 'hs_citytemps_stylesheet');


/* This function will attempt to draw the temperature tables by doing the following:
1. Look for get_option('hs_cityArray') [the array of cities as defined in the admin function]
2. Look for get_option('hs_citiestodisplay') [the number of cities from the array that we will display at one time]
3. Look for get_option('hs_homeCity') [the base city for which we always display the temperature]
4. Look for get_option('hs_celcius') ['true' means send 'metric' to the API else send 'imperial']
5. Use Yahoo yql to pull multiple city temperatures and weather conditions to display in the widget
*/
function hs_citytemps_display_temperatures($atts) {
	$a = shortcode_atts( array('hide_home_city' => 'false'), $atts );
	$homeCityOnly = false;
    $yahooURL = "https://query.yahooapis.com/v1/public/yql";
    $yahooIconURL = "http://l.yimg.com/a/i/us/we/52/";		
	$hs_cityTempConfiguration = get_option("hs_city_temp_configuration");
	if(!isset($hs_cityTempConfiguration)) {
		echo('<script>console.log("No hs_city_temp_configuration found.");</script>');
		return;
	}
	
	$cityArray = $hs_cityTempConfiguration['hs_cityArray'];
	$citiesToDisplay = $hs_cityTempConfiguration['hs_citiestodisplay'];
	$homeCity = $hs_cityTempConfiguration['hs_homeCity'];
	$celcius = 'false';
	if($hs_cityTempConfiguration['hs_celcius']) $celcius = $hs_cityTempConfiguration['hs_celcius'];
	$size = count($cityArray);
	$degrees = "<sup>&deg;".($celcius=='true'?'C':'F')."</sup>";
	if(!isset($homeCity->cityID)) {
		echo('<script>console.log("No home city defined.");</script>');
		return;
	}
	if($size == 0 || $citiesToDisplay > $size) {
		echo('<script>console.log("#Cities to display too large or there are no cities to display.  Reverting to standard weather widget with home city.");</script>');
		$a['hide_home_city'] = 'false';
		$homeCityOnly = true;
	}
	$cityNameList = array();
	$cityData = array();
	$cachedValues = get_transient("hs_city_temps");
	if($cachedValues && $cachedValues['city_count'] != null && $cachedValues['city_count'] == ($citiesToDisplay + 1)) {
		//Use cached values
		echo('<script>console.log("Using cached values for cities.");</script>');
		$cityNameList = $cachedValues['city_names'];
		$cityData = $cachedValues['city_data'];		
	}
	else {
		//get random numbers to pull separate cities.  We need $citiesToDisplay random numbers and they should be unique
		$random = array();
		$cityNameList = array();
		$IDList = strval($homeCity->cityID);
		$cityNameList[0] = $homeCity->cityName;
		$counter = 0;
		echo('<script>console.log("Generating new values for cities.");</script>');
		$logger = "";
		if(!$homeCityOnly) {
			while (count($random) < $citiesToDisplay) {
				$rndom = rand(0, $size-1);
				$found = false;
				for($j=0; $j < count($random); $j++) {
					if($random[$j] == $rndom) {
						$found=true;
						break;
					}
				}
				if(!$found) {
					$random[$counter] = $rndom;
					if($IDList != "") $IDList = $IDList.",";
					$IDList = $IDList.strval($cityArray[$rndom]->cityID);
					$cityNameList[$counter + 1] = $cityArray[$rndom]->cityName;
					$counter = $counter + 1;
				}
			}
		}		

		//YAHOO API TO BE USED
	    $yql_query = 'select * from weather.forecast where woeid in('.$IDList.')';
	    if($celcius=='true') {
	    	$yql_query = $yql_query." and u='c'";
	    }
	    $yql_query_url = $yahooURL . "?q=" . urlencode($yql_query) . "&format=json";
	    // Make call with cURL
	    $session = curl_init($yql_query_url);
	    curl_setopt($session, CURLOPT_RETURNTRANSFER,true);
	    $json = curl_exec($session);
	    // Convert JSON to PHP object
	    $forecastJSON =  json_decode($json);
		$numResults = $forecastJSON->query->count;
	    //Save off the home city forecast from first result
		$counter = 0;
		if($numResults == 1) {
			//If there is only one result then this is the home city and there are no others.
			//So we have no array of results but a single $forecastJSON->query->results->channel
			$cityTempData = $forecastJSON->query->results->channel;
			$newCityItem = new hscitytempCity();
			$newCityItem->cityName = $cityNameList[0];
			$newCityItem->currentTemp = $cityTempData->item->condition->temp;
			$newCityItem->currentWeatherCondition = $cityTempData->item->condition->text;
			$newCityItem->weatherIcon = $cityTempData->item->condition->code;
			$newCityItem->timeStamp = $cityTempData->item->condition->date;
			$newCityItem->cityForecastArray = array();
			$dayOfWeekInt = 0;
			foreach( (array) $cityTempData->item->forecast as $hourlyForecastData ) {
				$forecastItem = new hscitytempCityForecast();	
				$forecastItem->minTemp 	= $hourlyForecastData->low;
				$forecastItem->maxTemp 	= $hourlyForecastData->high;
				$forecastItem->dayOfWeek = $hourlyForecastData->day;
				$newCityItem->cityForecastArray[$dayOfWeekInt] = $forecastItem;	
				$dayOfWeekInt ++;
			}
			$cityData[0] = $newCityItem;
		
		}
		else {
			foreach( (array) $forecastJSON->query->results->channel as $cityTempData ) {
				$newCityItem = new hscitytempCity();
				$newCityItem->cityName = $cityNameList[$counter];
				$newCityItem->currentTemp = $cityTempData->item->condition->temp;
				$newCityItem->currentWeatherCondition = $cityTempData->item->condition->text;
				$newCityItem->weatherIcon = $cityTempData->item->condition->code;
				$newCityItem->timeStamp = $cityTempData->item->condition->date;
				$newCityItem->cityForecastArray = array();
				$dayOfWeekInt = 0;
				foreach( (array) $cityTempData->item->forecast as $hourlyForecastData ) {
					$forecastItem = new hscitytempCityForecast();	
					$forecastItem->minTemp 	= $hourlyForecastData->low;
					$forecastItem->maxTemp 	= $hourlyForecastData->high;
					$forecastItem->dayOfWeek = $hourlyForecastData->day;
					$newCityItem->cityForecastArray[$dayOfWeekInt] = $forecastItem;	
					$dayOfWeekInt ++;
				}
				
				$cityData[$counter] = $newCityItem;
				$counter ++;
			}	
		}
		$cachedValues['city_data'] = $cityData;
		$cachedValues['city_names'] = $cityNameList;
		$cachedValues['city_count'] = count($cityData);
		set_transient('hs_city_temps', $cachedValues, 600);
	}
	$htmlSrc = '<div class="hs-city-temp-wrapper">';
	$numResults = $cachedValues['city_count'];
	$homeCity = $cityData[0];
	if(strtolower($a['hide_home_city']) != 'true') {
		//Unfortunately, the timestamp coming back from yahoo is in  RFC822 date format which was difficult to convert to a local timezone time
		//so I have chunked the date to grab the last three segments for display (eg. 10:00 AM EST)
		$pieces = explode(" ", $homeCity->timeStamp);
		$pieceCount = count($pieces);
		$time = "";
		if($pieceCount > 3) {
			$time = "<br/>(as of ".$pieces[$pieceCount-3]." ".$pieces[$pieceCount-2]." ".$pieces[$pieceCount-1].")";
		}
		$htmlSrc = $htmlSrc.'<div class="hs-city-temp-header">Current Temperature'.$time.'</div>';
		$htmlSrc = $htmlSrc.'  <div class="hs-city-temp-current-temp-city"><span class="hs-city-temp-city-name">'.stripcslashes($homeCity->cityName).'</span></div>';
		$htmlSrc = $htmlSrc.'  <div class="hs-city-temp-current-temp-city"><span class="hs-city-temp-home-temp">'.round($homeCity->currentTemp).$degrees.'</span><br/>';
		$htmlSrc = $htmlSrc.$homeCity->currentWeatherCondition;
		$imageSrc = $yahooIconURL.$homeCity->weatherIcon.'.gif';
		$htmlSrc = $htmlSrc.'<br/><img src="'.$imageSrc.'">';
		$htmlSrc = $htmlSrc.'</div>';
		$htmlSrc = $htmlSrc.'<div>';
		$htmlSrc = $htmlSrc.'  <div class="hs-city-temp-forecast-label">Forecast:</div>';
		$forecastsDisplayed = 0;
		for($iter = 1; $iter <4; $iter++) {
			$dailyForecastData = $homeCity->cityForecastArray[$iter];
			$htmlSrc = $htmlSrc.'  <div class="hs-city-temp-three-day-temp">'.$dailyForecastData->dayOfWeek.'<br/>'.round($dailyForecastData->minTemp).'&deg/'.round($dailyForecastData->maxTemp).'&deg</div>';
		}
		$htmlSrc = $htmlSrc.'</div>';
	}
	if(!$homeCityOnly) {
		$otherCitiesHeader = "Weather in Other Cities";
		if(isset($hs_cityTempConfiguration['citiestextdisplay']) && $hs_cityTempConfiguration['citiestextdisplay'] != null && $hs_cityTempConfiguration['citiestextdisplay'] != "") {
			$otherCitiesHeader = stripcslashes($hs_cityTempConfiguration['citiestextdisplay']);
		}
		$htmlSrc = $htmlSrc.'<div class="hs-city-temp-header">'.$otherCitiesHeader.'</div>';
		
		for($i=1; $i<count($cityData); $i++) {
			$htmlSrc = $htmlSrc.'<div>';
			$thisResult = $cityData[$i];
			$htmlSrc = $htmlSrc.'  <div class="hs-city-temp-other-city">'.stripcslashes($thisResult->cityName).'</div>';
			$htmlSrc = $htmlSrc.'  <div class="hs-city-temp-other-city-temp">'.round($thisResult->currentTemp).$degrees.'</div>';
			$htmlSrc = $htmlSrc.'  <div class="hs-city-temp-other-city-weather">';
			$imageSrc = $yahooIconURL.$thisResult->weatherIcon.'.gif';
			$htmlSrc = $htmlSrc.'<img src="'.$imageSrc.'" title="'.$thisResult->currentWeatherCondition.'">';
			$htmlSrc = $htmlSrc.'</div>';
			$htmlSrc = $htmlSrc.'</div>';
		}
	}
	$htmlSrc = $htmlSrc.'<div style="text-align:right;"><a href="https://www.yahoo.com/?ilc=401" target="_blank"> <img src="https://poweredby.yahoo.com/purple.png" width="134" height="29"/> </a></div>';
	$htmlSrc = $htmlSrc.'</div>';
	return $htmlSrc;
}


function hs_temp_display_func( $atts ){
	return hs_citytemps_display_temperatures($atts);
}
add_shortcode( 'hs_temps', 'hs_temp_display_func' );
  
?>
