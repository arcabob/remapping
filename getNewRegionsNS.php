<?php

require_once('/Users/bob/dev/src/PhpConsole/__autoload.php');

$handler = PhpConsole\Handler::getInstance();
$handler->start(); // start handling PHP errors & exceptions
$handler->getConnector()->setSourcesBasePath($_SERVER['DOCUMENT_ROOT']); // so files paths on client will be shorter (optional)


//Get all vars passed
$strStart = $_GET['start'];
$colCount = intval($_GET['colCount']);
$rowCount = intval($_GET['rowCount']);
$swlat = number_format($_GET['swlat'],10);
$swlng = number_format($_GET['swlng'],10);
$nelat = number_format($_GET['nelat'],10);
$nelng = number_format($_GET['nelng'],10);


//$handler->debug('start', $strStart);

if($strStart=="true"){
    $startTopLeft = TRUE;
    
}else{
    $startTopLeft = FALSE;
}
    

//
//setup connection
$con = mysqli_connect("127.0.0.1","mapuser","arcabob1","map");

if (!$con)
{
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

//get total population
$sql="SELECT sum(pop10) as pop FROM censusPopulation where (intlat between ".$swlat." and ".$nelat.") and (intlong between ".$swlng." and ".$nelng.");";
//$handler->debug('sql', $sql);
$result = mysqli_query($con,$sql);
if (!$result) {
    echo "Failed to select from MySQL: " . mysql_error();
}

$row = $result->fetch_assoc();
$totalPopulation = $row['pop'];
$popPerRegion = intval(($totalPopulation) / ($colCount * $rowCount));
$totalRegionCount = ($colCount * $rowCount);
$lngPerCol = abs(($swlng-$nelng) / $colCount); 
//$handler->debug('population,popper,totalregion,latperrow', $totalPopulation .' ' .$popPerRegion.' '.$totalRegionCount.' '.$lngPerCol);
$currentCol=1;

$polygons = array();

if($startTopLeft){
    $currentLat=$nelat; 
    $currentLng=$swlng;
    $originalLat=$nelat;
    $movingRight=TRUE;
    $startedRight=TRUE;
}else{
    $currentLat=$swlat;
    $currentLng=$swlng;
    $originalLat=$swlat;
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
    $colSpan=1;
    
    while(!$filledRegion){
        if($movingRight){
            $sql="SELECT pop10,intlat FROM censusPopulation where (intlong between ".strval($currentLng+(($colSpan-1)*$lngPerCol))." and ".strval($currentLng+($colSpan*$lngPerCol)).") and (intlat between ".$swlat." and ".strval($currentLat).") order by intlat desc;";
        }else{
            $sql="SELECT pop10,intlat FROM censusPopulation where (intlong between ".strval($currentLng+(($colSpan-1)*$lngPerCol))." and ".strval($currentLng+($colSpan*$lngPerCol)).") and (intlat between ".strval($currentLat)." and ".$nelat.") order by intlat asc;";
        }
        //$handler->debug('sql', $sql);
        $result = mysqli_query($con,$sql);
        $numResults = mysqli_num_rows($result);
        $counter = 0;
        while($row = mysqli_fetch_array($result))
        {
            $newPopPerRegion += $row['pop10'];
            $newlat=$row['intlat'];
            
            if($newPopPerRegion>$popPerRegion || ((++$counter == $numResults) && $curRegion == ($totalRegionCount-1) && $currentCol==$rowCount)){   
                //set the final end to the end of the rectangle                
                
                if($curRegion == ($totalRegionCount-1) && $currentCol==$colCount){
                    //$handler->debug('last region', $sql);
                    if($movingRight){
                        $newlat = $swlat;
                    }else{
                        $newlat = $nelat;
                    }
                }
                
                if($colSpan==1){                    
                    $strLatLong=strval($newlat).';'.strval($currentLng);
                    array_push($apoints, $strLatLong);
                    $strLatLong=strval($newlat).';'.strval($currentLng+($lngPerCol));
                    array_push($apoints, $strLatLong);
                    $strLatLong=strval($currentLat).';'.strval($currentLng+($lngPerCol));
                    array_push($apoints, $strLatLong);
                    
                }else{
                    if($startedRight){
                        //2nd point
                        $strLatLong=strval($swlat).';'.strval($currentLng);
                        array_push($apoints, $strLatLong);
                        //3rd point
                        $strLatLong=strval($swlat).';'.strval($currentLng+($lngPerCol*($colSpan-($colSpan%2))));
                        array_push($apoints, $strLatLong);
                        //4th point
                        $strLatLong=strval($newlat).';'.strval($currentLng+($lngPerCol*($colSpan-($colSpan%2))));
                        array_push($apoints, $strLatLong);
                        //5th point                     
                        $strLatLong=strval($newlat).';'.strval($currentLng+($lngPerCol*($colSpan-($colSpan-1)%2)));
                        array_push($apoints, $strLatLong);
                        //6th
                        $strLatLong=strval($nelat).';'.strval($currentLng+($lngPerCol*($colSpan-($colSpan-1)%2)));
                        array_push($apoints, $strLatLong);
                        //7th
                        $strLatLong=strval($nelat).';'.strval($currentLng+($lngPerCol));
                        array_push($apoints, $strLatLong);
                    }else{
                        //2nd point
                        $strLatLong=strval($nelat).';'.strval($currentLng);
                        array_push($apoints, $strLatLong);
                        //3rd point
                        $strLatLong=strval($nelat).';'.strval($currentLng+($lngPerCol*($colSpan-($colSpan%2))));
                        array_push($apoints, $strLatLong);
                        //4th point
                        $strLatLong=strval($newlat).';'.strval($currentLng+($lngPerCol*($colSpan-($colSpan%2))));
                        array_push($apoints, $strLatLong);
                        //5th point                     
                        $strLatLong=strval($newlat).';'.strval($currentLng+($lngPerCol*($colSpan-($colSpan-1)%2)));
                        array_push($apoints, $strLatLong);
                        //6th
                        $strLatLong=strval($swlat).';'.strval($currentLng+($lngPerCol*($colSpan-($colSpan-1)%2)));
                        array_push($apoints, $strLatLong);
                        //7th
                        $strLatLong=strval($swlat).';'.strval($currentLng+($lngPerCol));
                        array_push($apoints, $strLatLong);
                    }
                    //8th                    
                    $strLatLong=strval($originalLat).';'.strval($currentLng+($lngPerCol));                        
                    array_push($apoints, $strLatLong);                    
                }
                
                $currentLat=$newlat;
                $currentLng=$currentLng+(($colSpan-1)*$lngPerCol);;
                $filledRegion=TRUE;
                //$handler->debug('filled region', $currentLat .' ' .$currentLng);
                break 2;
            }
        }
        $colSpan++;
        if($colSpan>9){
            break;
        }
        $currentCol++;
        if($movingRight){
            $currentLat=$swlat;
        }else{
            $currentLat=$nelat;
        }
        $movingRight=!$movingRight;
        
    }
    
    $startedRight=$movingRight;
    $originalLat=$currentLat;
    $polygons[$curRegion][0]=strval($curRegion).';'.strval($newPopPerRegion).';'.strval($colSpan);
    $polygons[$curRegion][1]=$apoints;
    
}

/*

*/
mysqli_close($con);

echo json_encode($polygons);




?>
