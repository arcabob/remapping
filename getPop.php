<?php

$indexRect = intval($_GET['indexRect']);
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

$sql="SELECT sum(pop10) as pop FROM censusPopulation where (intlat between ".$swlat." and ".$nelat.") and (intlong between ".$swlng." and ".$nelng.");";

$result = mysqli_query($con,$sql);

while($row = mysqli_fetch_array($result))
{

    $newValue = $row['pop'];

    echo number_format( $newValue , 0 , '.' , ',' );

}

mysqli_close($con);

?>
