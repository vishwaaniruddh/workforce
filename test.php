<?php 

include('./config.php');

$sql = mysqli_query($conn,"select * from Members");
if($sql_result = mysqli_fetch_assoc($sql)){
    var_dump($sql_result);
}

?>