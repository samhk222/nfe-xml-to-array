# nfe-xml-to-array

Biblioteca simples para Parsear os dados de uma nota fiscal eletrônica.

Para utilizar

```composer require samhk222/nfe-xml-to-obj```

E para na chamada do PHP

```PHP
<?PHP
include("vendor/autoload.php");

use samhk222\NFE\Parser;

$nfe = new Parser();
$nfe->CHECK_VALID_NF(file_get_contents('31180310494067000930550010000017761001433110-nfe.xml'));


echo "\n<pre>";
print_r($nfe);
print_r($nfe->__msg__);
echo "\n</pre>";
```