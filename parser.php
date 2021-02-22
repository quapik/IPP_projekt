
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
#echo $firstline[0]."\n"; //DEBUG PRINT

if($firstline[0]==".IPPcode21")
    {
        
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<program language=\"IPPcode21\">\n";

    }
    else  
    {
        echo "chyba";
    }

while ($line=fgets(STDIN))
    {
        $line=str_replace(PHP_EOL,"",$line); //odstraneni EOLu
        $line = explode("#",$line); //smazani komentaru
        $splitted=explode(" ",$line[0]); //odmazani bilych znaku
        if($splitted[0]!=NULL)
        {
            echo $splitted[0]."\n";
        }
       
    
    }
  
?>
