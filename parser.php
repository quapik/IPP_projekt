
<?php
#Vojtěch Šíma, xsimav01, IPP FIT VUT 2021
ini_set('display_errors','stderr'); #varovani na stan. chybový výstup

function checkVar($var){
    if(preg_match("/^(TF|GF|LF)@[A-Za-z$?!&%*\-_][A-Za-z0-9\w$?!&%*\-_]*$/",$var)) // spec znaku + pismena + cisla (ne na zacatku)
    {
        return true;
    }
   return false;
   
    }
function checkSymbol($symbol){
    if(preg_match("/^(TF|GF|LF)@[A-Za-z$?!&%*\-_][A-Za-z0-9\w$?!&%*\-_]*$/",$symbol)
    ||preg_match("/^int@[+-]?[0-9]+$/",$symbol)
    ||preg_match("/^bool@(true|false)$/",$symbol)
    ||preg_match("/^nil@nil$/",$symbol)
    ||preg_match("/^string@$/",$symbol)) //TODO
    {
        /*$checkType=explode("@",$symbol);
        echo $checkType[0].PHP_EOL;
        echo $checkType[1].PHP_EOL;*/

        return true; 
    }
    return false;
}   
    
$counter = 0;
$labels = [];
$labelsJump=[];

if ($argc==2 && $argv[1]=="--help")
    {
    echo "Zde bude napoveda";
    exit(0);
    }

//$line=fgets(STDIN);
//$firstline=explode(PHP_EOL,$line); //oddeleni pomoci konce radku => na prvni pozici pak musi byt .IPPcode21
#echo $firstline[0]."\n"; //DEBUG PRINT

/*if($firstline[0]==".IPPcode21") //todo predelat (pred timto muzou byt komentare)
    {
        
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<program language=\"IPPcode21\">\n";

    }
    else  
    {
        echo "chyba";
        exit(21);
    }
    */
    $BylIPPcode=false;
    while (($firstline=fgets(STDIN))&&$BylIPPcode==false) //while prochazejici dokuď jsou komentare nebo prazdne radky pred .IPPcode21 (pokud instrukce, chyba)
    {
        $firstline=str_replace(PHP_EOL,"",$firstline); //odstraneni EOLu
        $firstline = explode("#",$firstline); //smazani komentaru
        
        
        if($firstline[0]==".IPPcode21")
        {
            $BylIPPcode=true;
        }
        else if ($firstline[0]!=NULL) //komentare vynulovany
        {
            echo "\033[31m instrukce pred IPPcode \033[0m  \n"; //DEBUG
            exit(21); //todo oopravdu 21?
        }

    }
    

while ($line=fgets(STDIN))
    {
        $line=str_replace(PHP_EOL,"",$line); //odstraneni EOLu
        $line = explode("#",$line); //smazani komentaru
        $word=explode(" ",$line[0]); //odmazani bilych znaku


        $word[0]=strtoupper($word[0]); //neni case sensitive
        if($word[0]!=NULL)  //pokud neni prazdny radek => bude instrukce
        {
            //echo $word[0]."\n"; DEBUG
            $counter=$counter+1;

            switch ($word[0]){

                case ".IPPCODE21":
                    echo "\033[31m hlavicka podruhe \033[0m  \n"; //DEBUG
                    exit(21);
                    break;

                case "DEFVAR": //TODO Specialni znaky?
                    if(checkVar($word[1])==true) //nesmi zacinat cislem 
                    {
                        echo"\t<instruction order=\"$counter\" opcode=\"$word[0]\">\n";
                        echo "\t\t<arg1 type=\"var\">$word[1]</arg1>\n";
                        echo"\t</instruction>\n";
                    }
                    else
                    {
                        
                        echo "\033[31m chyba DEFVAR variable \033[0m  \n"; //DEBUG
                        exit(23);
                    }
                     break;
                
                case "MOVE":
                    if((checkVar($word[1])==true)&&(checkSymbol($word[2])==true))
                    {
                        echo"\t<instruction order=\"$counter\" opcode=\"$word[0]\">\n";
                        echo "\t\t<arg1 type=\"var\">$word[1]</arg1>\n";
                       // TODO echo "\t\t<arg2 type=\"var\">$word[2]</arg2>\n";
                        echo"\t</instruction>\n";
                       // echo "\033[31m MOVE OK \033[0m  \n"; //DEBUG
                    }
                    else
                    {
                       // echo "\033[31m MOVE NOT OK  \033[0m  \n"; //DEBUG
                    }
                    break;

                case "LABEL":
                    if (($word[2]==NULL)&&$word[1]!=NULL)
                    {   
                        foreach ($labels as $label)
                        {
                            if ($label==$word[1])
                            {
                                echo "\033[31m LABEL $word[1] PODRUHE \033[0m  \n"; //DEBUG
                                exit(52);
                            }

                        }
                        $labels[] = $word[1];
                        echo"\t<instruction order=\"$counter\" opcode=\"$word[0]\">\n";
                        echo "\t\t<arg1 type=\"label\">$word[1]</arg1>\n";
                        echo"\t</instruction>\n";
                    }
                    else
                    {
                        echo "\033[31m chyba LABEL \033[0m  \n"; //DEBUG
                        //TODO ERROR
                    }
                    break;
                case "JUMP":
                    if (($word[2]==NULL)&&$word[1]!=NULL)
                    {  $labelsJump[] = $word[1];
                        
                        echo"\t<instruction order=\"$counter\" opcode=\"$word[0]\">\n";
                        echo "\t\t<arg1 type=\"label\">$word[1]</arg1>\n";
                        echo"\t</instruction>\n";

                    }
                    break;
                 default:
                 //TODO error
                   
    
     
    
                       
            }


        


        } //konec ifu pokud neni prazdny radek => bude instrukce
       
        
    
    } //konec main whilu
    
    #kontrola zda jumpy odkazuji na definovana navesti
    foreach ($labelsJump as $labelJump)
        {         
            $nalezenJump=false;
            foreach ($labels as $label)
            {  
                if($label==$labelJump)
                {
                    $nalezenJump=true;
                }
            }

            if( $nalezenJump==false)
            {
                echo "\033[31m jump na neexistujici label \033[0m  \n"; //DEBUG
                exit(51);
            }
        }
        echo  "\033[32mOK\033[0m \n"; //DEBUG
        exit(0);
  
?>
