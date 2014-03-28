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
$handler->debug('population,popper,totalregion,latperrow', $totalPopulation .' ' .$popPerRegion.' '.$totalRegionCount.' '.$latPerRow);
$currentRow=1;

$polygons = array();

if($startBottemLeft){
    $currentLat=$swlat;
    $currentLng=$swlng;
    $movingRight=TRUE;
}else{
    $currentLat=$swlat;
    $currentLng=$nwlng;
    $movingRight=FALSE;
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
            $sql="SELECT *  FROM censusPopulation where intlat between ".strval($currentLat+(($rowSpan-1)*$latPerRow))." and ".strval($currentLat+($rowSpan*$latPerRow))." order by intlong asc;";
        }else{
            $sql="SELECT *  FROM censusPopulation where intlat between ".strval($currentLat+(($rowSpan-1)*$latPerRow))." and ".strval($currentLat+($rowSpan*$latPerRow))." order by intlong desc;";
        }
        $handler->debug('sql', $sql);
        $result = mysqli_query($con,$sql);
        while($row = mysqli_fetch_array($result))
        {
            $newPopPerRegion += $row['pop10'];
            $newlng=$row['intlong'];
            
            if($newPopPerRegion>$popPerRegion){
                $strLatLong=strval($currentLat).';'.strval($newlng);
                array_push($apoints, $strLatLong);
                $strLatLong=strval($currentLat+($rowSpan*$latPerRow)).';'.strval($newlng);
                array_push($apoints, $strLatLong);
                if($rowSpan=1){
                    $strLatLong=strval($currentLat+($latPerRow)).';'.strval($currentLng);
                    array_push($apoints, $strLatLong);
                }else{
                    $strLatLong=strval($currentLat+($latPerRow*$rowSpan)).';'.strval($newlng);
                    array_push($apoints, $strLatLong);
                    $strLatLong=strval($currentLat+($latPerRow)).';'.strval($newlng);
                    array_push($apoints, $strLatLong);
                    $strLatLong=strval($currentLat+($latPerRow)).';'.strval($currentLng);
                    array_push($apoints, $strLatLong);
                }
                $currentLat=$currentLat+($rowSpan*$latPerRow);
                $currentLng=$newlng;
                $filledRegion=TRUE;
                $handler->debug('filled region', $currentLat .' ' .$currentLng);
                break 2;
            }
        }
        $rowSpan++;
        if($rowSpan>4){
            break;
        }
        $currentRow++;
        $movingRight=!$movingRight;
        
    }
    
    
    $polygons[$curRegion][0]=$curRegion;
    $polygons[$curRegion][1]=$apoints;
    
}

/*

*/
mysqli_close($con);

echo json_encode($polygons);




?>
