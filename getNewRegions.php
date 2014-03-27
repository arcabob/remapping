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
$handler->debug('population,popper,totalregion', $totalPopulation .' ' .$popPerRegion.' '.$totalRegionCount);

if($startBottemLeft){
    $startBottom=$swlat;
    $startTop=$nelat;
    $movingRight=TRUE;
}

for($curRegion=0; $curRegion < $totalRegionCount+1;$curRegion++){
    $newPopPerRegion=0;
    
    
}

/*
while($row = mysqli_fetch_array($result))
{
    $totalPopulation = $row['pop'];
      
}
*/
echo $totalPopulation;
mysqli_close($con);

?>
