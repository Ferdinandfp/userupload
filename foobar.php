<?php
$FOO = "foo";
$BAR = "bar";
$Output;
for ($x = 1; $x <= 100; $x++){
    //echo $x . ", ";
    //echo $x % 3 == 0 ? $FOO.", " : $x.", ";
    $Output .= ($x % 3 == 0 && $x % 5 == 0)? $FOO.$BAR.", " : (($x % 3 == 0) ? $FOO.", " : (($x % 5 == 0) ? $BAR.", " : $x.", "));
}

echo substr($Output, 0 , -2);