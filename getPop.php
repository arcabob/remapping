<?php

$indexRect = intval($_GET['indexRect']);
echo $indexRect;

$con = mysqli_connect("127.0.0.1","mapuser","arcabob1","map");
if (mysqli_connect_errno())
{
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

//$sql="SELECT * FROM testTable WHERE id = '".$q."'";
$sql="SELECT * FROM testTable;";


$result = mysqli_query($con,$sql);

while($row = mysqli_fetch_array($result))
{

    $newValue = $row['pop'];

}

mysqli_close($con);

?>
