
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
function instructionStart($poradi,$opcode)
    {
        echo"\t<instruction order=\"$poradi\" opcode=\"$opcode\">\n";
    }
function instructionEnd()
    {
        echo"\t</instruction>\n";
    }    
   
function checkSymbol($symbol){
    if(preg_match("/^(TF|GF|LF)@[A-Za-z$?!&%*\-_][A-Za-z0-9\w$?!&%*\-_]*$/",$symbol)
    ||preg_match("/^int@[+-]?[0-9]+$/",$symbol)
    ||preg_match("/^bool@(true|false)$/",$symbol)
    ||preg_match("/^nil@nil$/",$symbol)
    ||preg_match("/^string@$/",$symbol)
    ||preg_match("/^string@(.)*$/",$symbol)
    ) //TODO
    {
        /*$checkType=explode("@",$symbol);
        echo $checkType[0].PHP_EOL;
        echo $checkType[1].PHP_EOL;*/

        return true; 
    }
    return false;
}   
#funkce na print symbolu jako argument
function SymbolPrint($symbol,$number)
{
    $type=explode("@",$symbol);
    if($type[0]=="string"||$type[0]=="int"||$type[0]=="nil"||$type[0]=="float"||$type[0]=="bool")
        {
            if($type[1]==NULL&&$type[0]=="string")
            {
                echo "\t\t<arg$number type=\"$type[0]\"/>\n";
            }
            else
            {
                echo "\t\t<arg$number type=\"$type[0]\">$type[1]</arg$number>\n";
            }
           
        }
        else
        {
            echo "\t\t<arg$number type=\"var\">$type[0]@$type[1]</arg$number>\n";
        }
}
function VarPrint($variable,$number)
{
    echo "\t\t<arg$number type=\"var\">$variable</arg$number>\n";
}
    
$counter = 0;
$labels = [];
$labelsJump=[];

if ($argc==2 && $argv[1]=="--help")
    {
    echo "Zde bude napoveda";
    exit(0);
    }

    $BylIPPcode=false;
    while (($BylIPPcode==false&&$firstline=fgets(STDIN))) //while prochazejici dokuď jsou komentare nebo prazdne radky pred .IPPcode21 (pokud instrukce, chyba)
    {
        $firstline=str_replace(PHP_EOL,"",$firstline); //odstraneni EOLu
        $firstline = explode("#",$firstline); //smazani komentaru
        
        
        if($firstline[0]==".IPPcode21")
        {
            $BylIPPcode=true;
            echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<program language=\"IPPcode21\">\n";
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
            //echo $word[0]."\n"; //DEBUG
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
                        SymbolPrint($word[2],2);
                        echo"\t</instruction>\n";
                    }
                    else
                    {             
                        echo "\033[31m MOVE NOT OK  \033[0m  \n"; //DEBUG
                        exit(23);
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
                        exit(23);
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
                 
                 //TODO error
                 case "JUMPIFEQ": //<label> <symb> <symb>
                 case "JUMPIFNEQ":

                    if($word[0]!=NULL&&checkSymbol($word[2])==true&&checkSymbol($word[3])==true&&$word[4]==NULL)
                    {
                        echo"\t<instruction order=\"$counter\" opcode=\"$word[0]\">\n";
                        echo "\t\t<arg1 type=\"label\">$word[1]</arg1>\n"; //label na ktery se skace 
                        $labelsJump[] = $word[1]; //pridani do pole jumpLabelu
                        SymbolPrint($word[2],2); //arg2 arg3
                        SymbolPrint($word[3],3);
                        echo"\t</instruction>\n";

                    }
                    else
                        {
                            echo "\033[31m chyba JUMPIFNEQ \033[0m  \n"; //DEBUG
                            exit(23);
                        }
                    break;
                    case "EXIT": // <symb>
                        if($word[1]!=NULL&&$word[2]==NULL)
                        {
                            $exitCodeCheck=explode("@",$word[1]);
                            if($exitCodeCheck[0]=="int" && $exitCodeCheck[1]>=0 && $exitCodeCheck[1]<=49) //pouze int v rozsahu 0-49
                            {
                                echo"\t<instruction order=\"$counter\" opcode=\"$word[0]\">\n";
                                echo "\t\t<arg1 type=\"int\">$exitCodeCheck[1]</arg1>\n";
                                echo"\t</instruction>\n";
                            }
                            else
                            {
                                echo "\033[31m chyba EXIT \033[0m  \n"; //DEBUG
                                exit(57);
                            }
                        }
                        else
                        {
                            exit(23);
                        }
                        break;
                    case "DPRINT": //<symb>         
                        if((checkSymbol($word[1])!=true)||$word[2]!=NULL)
                        {
                            echo "\033[31m chyba DPRINT \033[0m  \n"; //DEBUG
                            exit(23);             
                        }
                        instructionStart($counter,$word[0]);
                        SymbolPrint($word[1],1);
                        instructionEnd();

                        break;

                    case "BREAK":  //<>
                        
                        break;
                    case "WRITE": //<symb>
                        if(checkSymbol($word[1])!=true||$word[2]!=NULL)
                        {
                            echo "\033[31m chyba WRITE \033[0m  \n"; //DEBUG
                            exit(23);  
                        }
                        instructionStart($counter,$word[0]);
                        SymbolPrint($word[1],1);
                        instructionEnd();
                        break;
                        
                    case "READ": //⟨var⟩ ⟨type⟩
                        
                        
                        break;

                    case "CONCAT": //⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
                        if(checkVar($word[1])!=true||checkSymbol($word[2])!=true||checkSymbol($word[3])!=true||$word[4]!=NULL)
                        {
                            echo "\033[31m chyba CONCAT \033[0m  \n"; //DEBUG
                            exit(23);  
                        }
                        instructionStart($counter,$word[0]);
                        echo "\t\t<arg1 type=\"var\">$word[1]</arg1>\n";
                        SymbolPrint($word[2],2);
                        SymbolPrint($word[3],3);
                        instructionEnd();
                        break;

                    case "STRLEN": //⟨var⟩ ⟨symb⟩ 
                        if(checkVar($word[1])!=true||checkSymbol($word[2])!=true||$word[3]!=NULL)
                        {
                            echo "\033[31m chyba STRLEN \033[0m  \n"; //DEBUG
                            exit(23);  
                        }
                        instructionStart($counter,$word[0]);
                        VarPrint($word[1],1);
                        SymbolPrint($word[2],2);
                        instructionEnd();
                    break;

                    case "GETCHAR": //⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
                    case "SETCHAR":    
                        if(checkVar($word[1])!=true||checkSymbol($word[2])!=true||checkSymbol($word[3])!=true||$word[4]!=NULL)
                        {
                            echo "\033[31m chyba GETCHAR \033[0m  \n"; //DEBUG
                            exit(23);  
                        }
                        instructionStart($counter,$word[0]);
                        VarPrint($word[1],1);
                        SymbolPrint($word[2],2);
                        SymbolPrint($word[3],3);
                        instructionEnd();
                        //TODO chceck STRLEN podle zadani!!!!!!
                        
                        break;

    
                default:   
                    echo "\033[31m DEFAULT - neznamy operacni kod \033[0m  \n"; //DEBUG
                    exit(22);
                
                   
    
     
    
                       
            }  //konec switche


    
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
        } //foreach konec 



       
        echo "</program>";
        //echo  "\033[32mOK\033[0m \n"; //DEBUG
        exit(0);
  
?>
