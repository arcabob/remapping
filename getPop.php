<?php

require_once('/Users/bob/dev/src/PhpConsole/__autoload.php');

$handler = PhpConsole\Handler::getInstance();
$handler->start(); // start handling PHP errors & exceptions
$handler->getConnector()->setSourcesBasePath($_SERVER['DOCUMENT_ROOT']); // so files paths on client will be shorter (optional)

$indexRect = intval($_GET['indexRect']);
$strState = $_GET['state'];
$swlat = number_format($_GET['swlat'],10);
$swlng = number_format($_GET['swlng'],10);
$nelat = number_format($_GET['nelat'],10);
$nelng = number_format($_GET['nelng'],10);

echo $indexRect.";";

$con = mysqli_connect("127.0.0.1","mapuser","arcabob1","map");
if (mysqli_connect_errno())
{
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$sql="SELECT sum(DP0010001) as pop FROM censusPopulationWithBreakout where (statechar = '".$strState."') and (INTPTLAT10 between ".$swlat." and ".$nelat.") and (INTPTLON10 between ".$swlng." and ".$nelng.");";

//$sql="SELECT sum(pop10) as pop FROM censusPopulation where (intlat between ".$swlat." and ".$nelat.") and (intlong between ".$swlng." and ".$nelng.");";

$result = mysqli_query($con,$sql);

while($row = mysqli_fetch_array($result))
{

    $newValue = $row['pop'];

    echo number_format( $newValue , 0 , '.' , ',' );

}

mysqli_close($con);

?>
