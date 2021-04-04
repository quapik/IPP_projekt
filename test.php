<?php
#Vojtěch Šíma, xsimav01, IPP FIT VUT 2021
$BylDircetory=$BylRecursive=$BylParseScript=$BylInterpretScript=$ParseOnly=$IntOnly=$Byljexamxml=$Byljexamcfg=false;
if ($argv[1]=="--help")
    {
        if ($argc==2)
        {
        echo("Nápověd pro skript test.php".PHP_EOL);
        echo("------------------------------------------------------------------------------------------------------------------".PHP_EOL);
        echo ("|--directory=path -> Testy jsou prováděny v zadaném adresáři                                                     |".PHP_EOL);
        echo ("|--recursive -> Testy jsou provádněy rekurzivně i v podsložkách|                                                 |".PHP_EOL);
        echo ("|--parse-script=file -> Soubor s parse skriptem pro IPPCODE21 (pokud chybí, využívá se parse.php v tomto adresáři|".PHP_EOL);
        echo ("|--int-script=file -> Soubor s interpretem pro IPPCODE21 (pokud chybí, využívá se interpret.py v tomto adresáři  |".PHP_EOL);
        echo ("|--parse-only -> Bude testován pouze parse.php                                                                   |".PHP_EOL);
        echo ("|--int-only -> Bude testován pouze interpret.py                                                                  |".PHP_EOL);
        echo ("|--jexamxml=file -> Soubor s JAR balíčkem s nástrojem A7Soft JExamXML                                            |".PHP_EOL);
        echo ("|--jexamcfg=file -> Soubor s konfigurací nástroje A7Soft JExamXML                                                |".PHP_EOL);
        echo("------------------------------------------------------------------------------------------------------------------".PHP_EOL);
        exit(0);
        }
        else  exit(10); 
    }   
for ($i=1; $i<$argc; $i++)
{
    echo ($argv[$i].PHP_EOL);
    if ($argv[$i]=="--help")
    {
        exit(10);
    } 
    else if (preg_match("/^--directory=(\S)+$/",$argv[$i]))
    {
        $BylDircetory=true;
        $directorypath=explode("=",$argv[$i]);
    }
    else if ($argv[$i]=="--recursive")
    {
        $BylRecursive=true;
    }
    else if (preg_match("/^--parse-script=(\S)+$/",$argv[$i]))
    {
        $pom=explode("=",$argv[$i]);
        $parsefile=$pom[1];
        $BylParseScript=true;
    }
    else if (preg_match("/^--int-script=(\S)+$/",$argv[$i]))
    {
        $pom=explode("=",$argv[$i]);
        $interpretfile=$pom[1];
        $BylInterpretScript=true;
    }
    else if ($argv[$i]=="--parse-only")
    {
        $ParseOnly=true;
    }
    else if ($argv[$i]=="--int-only")
    {
        $IntOnly=true;
    }
    else if (preg_match("/^--jexamxml=(\S)+$/",$argv[$i]))
    {    
        $Byljexamxml=true;
        $pom=explode("=",$argv[$i]);
        $jexamxmlfile=$pom[1];
    }
    else if (preg_match("/^--jexamcfg=(\S)+$/",$argv[$i]))
    {    
        $Byljexamcfg=true;
        $pom=explode("=",$argv[$i]);
        $jexamcfgfile=$pom[1];
    }
}
if ($ParseOnly==true)
{
    if ($IntOnly==true||$BylInterpretScript==true)
    {
        exit(10);
    }
}
if ($IntOnly==true)
{
    if ($ParseOnly==true||$BylParseScript==true)
    {
        exit(10);
    }
}
echo("OK");
exit(0);
?>
