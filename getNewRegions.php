<?php

require_once('/Users/bob/dev/src/PhpConsole/__autoload.php');

$handler = PhpConsole\Handler::getInstance();
$handler->start(); // start handling PHP errors & exceptions
$handler->getConnector()->setSourcesBasePath($_SERVER['DOCUMENT_ROOT']); // so files paths on client will be shorter (optional)


//Get all vars passed
$colCount = intval($_GET['colCount']);
$rowCount = intval($_GET['rowCount']);
$swlat = number_format($_GET['swlat'],10);
$swlng = number_format($_GET['swlng'],10);
$nelat = number_format($_GET['nelat'],10);
$nelng = number_format($_GET['nelng'],10);

$startBottemLeft = TRUE;

//setup connection
$con = mysqli_connect("127.0.0.1","mapuser","arcabob1","map");

if (!$con)
{
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

//get total population
$sql="SELECT sum(pop10) as pop FROM censusPopulation where (intlat between ".$swlat." and ".$nelat.") and (intlong between ".$swlng." and ".$nelng.");";

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

if($startBottemLeft){
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
    
    $strLatLong=strval($currentLat).';'.strval($currentLng);
    array_push($apoints, $strLatLong);
    
    $filledRegion=FALSE;
    $rowSpan=1;
    
    while(!$filledRegion){
        if($movingRight){
            $sql="SELECT *  FROM censusPopulation where intlat between ".strval($currentLat+(($rowSpan-1)*$latPerRow))." and ".strval($currentLat+($rowSpan*$latPerRow))." and intlong > ".strval($currentLng)." order by intlong asc;";
        }else{
            $sql="SELECT *  FROM censusPopulation where intlat between ".strval($currentLat+(($rowSpan-1)*$latPerRow))." and ".strval($currentLat+($rowSpan*$latPerRow))." and intlong < ".strval($currentLng)." order by intlong desc;";
        }
        //$handler->debug('sql', $sql);
        $result = mysqli_query($con,$sql);
        $numResults = mysqli_num_rows($result);
        $counter = 0;
        while($row = mysqli_fetch_array($result))
        {
            $newPopPerRegion += $row['pop10'];
            $newlng=$row['intlong'];
            
            if($newPopPerRegion>$popPerRegion || ((++$counter == $numResults) && $curRegion == ($totalRegionCount-1))){                
                if($rowSpan==1){
                    $strLatLong=strval($currentLat).';'.strval($newlng);
                    array_push($apoints, $strLatLong);
                    $strLatLong=strval($currentLat+($rowSpan*$latPerRow)).';'.strval($newlng);
                    array_push($apoints, $strLatLong);
                    $strLatLong=strval($currentLat+($latPerRow)).';'.strval($currentLng);
                    array_push($apoints, $strLatLong);
                }else{
                    if($startedRight){
                        $strLatLong=strval($currentLat).';'.strval($nelng);
                        array_push($apoints, $strLatLong);
                        $strLatLong=strval($currentLat+($latPerRow*$rowSpan)).';'.strval($nelng);
                        array_push($apoints, $strLatLong);
                    
                    }else{
                        $strLatLong=strval($currentLat).';'.strval($swlng);
                        array_push($apoints, $strLatLong);
                        $strLatLong=strval($currentLat+($latPerRow*$rowSpan)).';'.strval($swlng);
                        array_push($apoints, $strLatLong);
                    }
                    $strLatLong=strval($currentLat+($latPerRow*$rowSpan)).';'.strval($newlng);
                    array_push($apoints, $strLatLong);
                    $strLatLong=strval($currentLat+($latPerRow*($rowSpan-1))).';'.strval($newlng);
                    array_push($apoints, $strLatLong);
                    $strLatLong=strval($currentLat+($latPerRow*($rowSpan-1))).';'.strval($originalLng);
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
        if($rowSpan>4){
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
    $polygons[$curRegion][0]=strval($curRegion).';'.strval($newPopPerRegion);
    $polygons[$curRegion][1]=$apoints;
    
}

/*

*/
mysqli_close($con);

echo json_encode($polygons);




?>
