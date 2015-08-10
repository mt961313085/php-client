<?php
/*$a[26]=array();
$a[5][]=13;
$a[5][]=3;
$a[5][]=7;
$a[5][]=8;
$str='0000000000000000';
foreach ($a[5] as $key => $value) {
	echo $value."\r\n";
	$str=substr_replace($str,'1',-($value),1);
	
}
echo $str;
*/
/*$i[03][]='5';
$i[02][]='6';
$i[03][]='3';
$i[2][]='4';
print_r($i);
foreach ($i as $key => $value) {
	//print_r($key);
	foreach ($value as $val) {
		echo $val."\r\n";
	}
}*/
$str="001";
$str=str_pad($str,3,'0');
echo $str;