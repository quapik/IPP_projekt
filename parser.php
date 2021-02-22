
<?php
#Vojtěch Šíma, xsimav01, IPP FIT VUT 2021
ini_set('display_errors','stderr'); #varovani na stan. chybový výstup

if ($argc==2 && $argv[1]=="--help")
    {
    echo "Zde bude napoveda";
    exit(0);
    }

$line=fgets(STDIN);
$firstline=explode(PHP_EOL,$line); //oddeleni pomoci konce radku => na prvni pozici pak musi byt .IPPcode21
echo $firstline[0]."\n"; //DEBUG PRINT

if($firstline[0]==".IPPcode21")
    {
        echo "ok";
    }
    else  
    {
        echo "chyba";
    }

while ($line=fgets(STDIN))
    {
        $line=str_replace(PHP_EOL,"",$line);
        $splitted=explode(" ",$line); 
        echo $splitted[0]."\n";
    }
  
?>