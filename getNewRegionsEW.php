<?php

require_once('/Users/bob/dev/src/PhpConsole/__autoload.php');

$handler = PhpConsole\Handler::getInstance();
$handler->start(); // start handling PHP errors & exceptions
$handler->getConnector()->setSourcesBasePath($_SERVER['DOCUMENT_ROOT']); // so files paths on client will be shorter (optional)


//Get all vars passed
$strStart = $_GET['start'];
$strState = $_GET['state'];
$colCount = intval($_GET['colCount']);
$rowCount = intval($_GET['rowCount']);
$swlat = number_format($_GET['swlat'],10);
$swlng = number_format($_GET['swlng'],10);
$nelat = number_format($_GET['nelat'],10);
$nelng = number_format($_GET['nelng'],10);


//$handler->debug('start', $strStart);

if($strStart=="true"){
    $startBottomLeft = TRUE;
    
}else{
    $startBottomLeft = FALSE;
}
    

//
//setup connection
$con = mysqli_connect("127.0.0.1","mapuser","arcabob1","map");

if (!$con)
{
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

//get total population
//$sql="SELECT sum(pop10) as pop FROM censusPopulation where (intlat between ".$swlat." and ".$nelat.") and (intlong between ".$swlng." and ".$nelng.");";
$sql="SELECT sum(DP0010001) as pop FROM censusPopulationWithBreakout where (statechar = '".$strState."') and (INTPTLAT10 between ".$swlat." and ".$nelat.") and (INTPTLON10 between ".$swlng." and ".$nelng.");";
//$handler->debug('sql', $sql);
$result = mysqli_query($con,$sql);
if (!$result) {
    echo "Failed to select from MySQL: " . mysql_error();
}

$row = $result->fetch_assoc();
$totalPopulation = $row['pop'];
$popPerRegion = intval(($totalPopulation) / ($colCount * $rowCount));
$totalRegionCount = ($colCount * $rowCount);
$latPerRow = ($nelat-$swlat) / $rowCount; 
//$handler->debug('population,popper,totalregion,latperrow', $totalPopulation .' ' .$popPerRegion.' '.$totalRegionCount.' '.$latPerRow);
$currentRow=1;

$polygons = array();

if($startBottomLeft){
    $currentLat=$swlat; 
    $currentLng=$swlng;
    $originalLng=$swlng;
    $movingRight=TRUE;
    $startedRight=TRUE;
}else{
    $currentLat=$swlat;
    $currentLng=$nelng;
    $originalLng=$nelng;
    $movingRight=FALSE;
    $startedRight=FALSE;
}

for($curRegion=0; $curRegion < $totalRegionCount;$curRegion++){
    $newPopPerRegion=0;
    $polygons[$curRegion]=array();
    $apoints = array();
    //1st point
    $strLatLong=strval($currentLat).';'.strval($currentLng);
    array_push($apoints, $strLatLong);
    
    $filledRegion=FALSE;
    $rowSpan=1;
    
    while(!$filledRegion){
        if($movingRight){
            $sql="SELECT DP0010001,INTPTLON10 FROM censusPopulationWithBreakout where (statechar = '".$strState."') and (INTPTLAT10 between ".strval($currentLat+(($rowSpan-1)*$latPerRow))." and ".strval($currentLat+($rowSpan*$latPerRow)).") and (INTPTLON10 between ".strval($currentLng)." and ".$nelng.") order by INTPTLON10 asc;";
        }else{
            $sql="SELECT DP0010001,INTPTLON10 FROM censusPopulationWithBreakout where (statechar = '".$strState."') and (INTPTLAT10 between ".strval($currentLat+(($rowSpan-1)*$latPerRow))." and ".strval($currentLat+($rowSpan*$latPerRow)).") and (INTPTLON10 between ".$swlng." and ".strval($currentLng).") order by INTPTLON10 desc;";
        }
        //$handler->debug('sql', $sql);
        $result = mysqli_query($con,$sql);
        $numResults = mysqli_num_rows($result);
        $counter = 0;
        while($row = mysqli_fetch_array($result))
        {
            $newPopPerRegion += $row['DP0010001'];
            $newlng=$row['INTPTLON10'];
            
            if($newPopPerRegion>$popPerRegion || ((++$counter == $numResults) && $curRegion == ($totalRegionCount-1) && $currentRow==$rowCount)){   
                //set the final end to the end of the rectangle                
                
                if($curRegion == ($totalRegionCount-1) && $currentRow==$rowCount){
                    if($movingRight){
                        $newlng = $nelng;
                    }else{
                        $newlng = $swlng;
                    }
                }
                
                if($rowSpan==1){                    
                    $strLatLong=strval($currentLat).';'.strval($newlng);
                    array_push($apoints, $strLatLong);
                    $strLatLong=strval($currentLat+($rowSpan*$latPerRow)).';'.strval($newlng);
                    array_push($apoints, $strLatLong);
                    $strLatLong=strval($currentLat+($latPerRow)).';'.strval($currentLng);
                    array_push($apoints, $strLatLong);
                    
                }else{
                    if($startedRight){
                        //2nd point
                        $strLatLong=strval($currentLat).';'.strval($nelng);
                        array_push($apoints, $strLatLong);
                        //3rd point
                        $strLatLong=strval($currentLat+($latPerRow*($rowSpan-($rowSpan%2)))).';'.strval($nelng);
                        array_push($apoints, $strLatLong);
                        //4th point
                        $strLatLong=strval($currentLat+($latPerRow*($rowSpan-($rowSpan%2)))).';'.strval($newlng);
                        array_push($apoints, $strLatLong);
                        //5th point
                        $strLatLong=strval($currentLat+($latPerRow*($rowSpan-($rowSpan-1)%2))).';'.strval($newlng);                        
                        array_push($apoints, $strLatLong);
                        //6th
                        $strLatLong=strval($currentLat+($latPerRow*($rowSpan-($rowSpan-1)%2))).';'.strval($swlng);
                        array_push($apoints, $strLatLong);
                        //7th                           
                        $strLatLong=strval($currentLat+($latPerRow)).';'.strval($swlng);
                        array_push($apoints, $strLatLong);
                    }else{
                        //2nd point
                        $strLatLong=strval($currentLat).';'.strval($swlng);
                        array_push($apoints, $strLatLong);
                        //3rd point
                        $strLatLong=strval($currentLat+($latPerRow*($rowSpan-($rowSpan%2)))).';'.strval($swlng);
                        array_push($apoints, $strLatLong);
                        //4th point
                        $strLatLong=strval($currentLat+($latPerRow*($rowSpan-($rowSpan%2)))).';'.strval($newlng);
                        array_push($apoints, $strLatLong);
                        //5th point                     
                        $strLatLong=strval($currentLat+($latPerRow*($rowSpan-($rowSpan-1)%2))).';'.strval($newlng);
                        array_push($apoints, $strLatLong);
                        //6th
                        $strLatLong=strval($currentLat+($latPerRow*($rowSpan-($rowSpan-1)%2))).';'.strval($nelng);
                        array_push($apoints, $strLatLong);
                        //7th
                        $strLatLong=strval($currentLat+($latPerRow)).';'.strval($nelng);
                        array_push($apoints, $strLatLong);
                    }
                    //8th                    
                    $strLatLong=strval($currentLat+($latPerRow)).';'.strval($originalLng);                        
                    array_push($apoints, $strLatLong);                    
                }
                
                $currentLat=$currentLat+(($rowSpan-1)*$latPerRow);
                $currentLng=$newlng;
                $filledRegion=TRUE;
                //$handler->debug('filled region', $currentLat .' ' .$currentLng);
                break 2;
            }
        }
        $rowSpan++;
        if($rowSpan>9){
            break;
        }
        $currentRow++;
        if($movingRight){
            $currentLng=$nelng;
        }else{
            $currentLng=$swlng;
        }
        $movingRight=!$movingRight;
        
    }
    
    $startedRight=$movingRight;
    $originalLng=$currentLng;
    $polygons[$curRegion][0]=strval($curRegion).';'.strval($newPopPerRegion).';'.strval($rowSpan);
    $polygons[$curRegion][1]=$apoints;
    
}

/*

*/
mysqli_close($con);

echo json_encode($polygons);




?>
