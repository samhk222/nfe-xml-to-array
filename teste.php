<?PHP
include("src/NFE.php");

use samhk222\NFE\Parser;

$nfe = new Parser();
$nfe->CHECK_VALID_NF(file_get_contents('31180310494067000930550010000017761001433110-nfe.xml'));


echo "\n<pre>";
print_r($nfe);
print_r($nfe->__msg__);
echo "\n</pre>";
