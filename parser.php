
<?php
#Vojtěch Šíma, xsimav01, IPP FIT VUT 2021
ini_set('display_errors','stderr'); #varovani na stan. chybový výstup
$counter = 0;
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
        exit(21);
    }

while ($line=fgets(STDIN))
    {
        $line=str_replace(PHP_EOL,"",$line); //odstraneni EOLu
        $line = explode("#",$line); //smazani komentaru
        $word=explode(" ",$line[0]); //odmazani bilych znaku
        $word[0]=strtoupper($word[0]); //neni case sensitive
        if($word[0]!=NULL)  //pokud neni prazdny radek => bude instrukce
        {
            //echo $splitted[0]."\n";
            $counter=$counter+1;

            switch ($word[0]){
                case "DEFVAR": //TODO Specialni znaky?
                    if(preg_match("/(TF|GF|LF)@[A-Za-z][A-Za-z0-9]*/",$word[1])) //nesmi zacinat cislem 
                    {
                        echo"\t<instruction order=\"$counter\" opcode=\"$word[1]\">\n";
                        echo "\t\t<arg1 type=\"var\">$word[1]</arg1>\n";
                        echo"\t</instruction>\n";
                    }
                    else
                    {
                        
                        echo "\033[31m chyba variable \033[0m  \n";
                        //TODO
                    }
                    
    
                     break;
                
                case "MOVE":
                    
                    break;
    
    
    
    
    
            }




        } //konec ifu pokud neni prazdny radek => bude instrukce
       

        



       
    
    }
  
?>
