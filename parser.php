<?php
#Vojtěch Šíma, xsimav01, IPP FIT VUT 2021
ini_set('display_errors','stderr'); #varovani na stan. chybový výstup
$counter = 0; #counter instrukci

#funkce na kontorlu validnosti <var>
function checkVar($var){
    if(preg_match("/^(TF|GF|LF)@[A-Za-z$?!&%*\-_][A-Za-z0-9\w$?!&%*\-_]*$/",$var)) // spec znaku + pismena + cisla (ne na zacatku)
    {
        return true;
    }
    #echo "\033[31m chyba s $var \033[0m  \n"; //DEBUG
   return false;
   
    }
#funkce na kontorlu validnosti <type>
function checkType($type)
    {
        if($type=="int"||$type=="string"||$type=="bool")
        {
            return true;
        }
        #echo "\033[31m chyba s $type \033[0m  \n"; //DEBUG
        return false;
    }
#start instrukce
function instructionStart($poradi,$opcode)
    {
        echo"\t<instruction order=\"$poradi\" opcode=\"$opcode\">\n";
    }
#konec instrukce
function instructionEnd()
    {
        echo"\t</instruction>\n";
    }    
#funkce na kontorlu validnosti <symbol>
function checkSymbol($symbol){
    if(preg_match("/^(TF|GF|LF)@[A-Za-z$?!&%*\-_][A-Za-z0-9\w$?!&%*\-_]*$/",$symbol)
    ||preg_match("/^int@[+-]?[0-9]+$/",$symbol)
    ||preg_match("/^bool@(true|false)$/",$symbol)
    ||preg_match("/^nil@nil$/",$symbol)
    ||preg_match("/^string@$/",$symbol)
    ||preg_match("/^string@((\\\\[0]([0-3][0125]|[9][2]))?(\S*)(\\\\[0]([0-3][0125]|[9][2]))?)*$/",$symbol)
    ) 
    {
        return true; 
    }
    #echo "\033[31m chyba ss $symbol \033[0m  \n"; //DEBUG
    return false;
}   
#funkce na print symbolu jako argument
function SymbolPrint($symbol,$number)
{  
    $type=explode("@",$symbol,2);
    //$type[1]=str_replace("&","&amp;",$type[1]);
    $type[1] = htmlspecialchars($type[1],ENT_QUOTES | ENT_XML1); //nahrazeni & " ' < > 
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
#funkce na print var jako argument
function VarPrint($variable,$number)
{
    echo "\t\t<arg$number type=\"var\">$variable</arg$number>\n";
}
#funkce na print labelu
function LabelPrint($labeltext,$number)
{
    echo "\t\t<arg$number type=\"label\">$labeltext</arg1>\n";
}
if($argc>2) #Máme moc parametrů
{
    exit(10);
}


if ($argc==2)
    {
        if($argv[1]=="--help") #Nápověda
        {
            echo "Nápověda pro  Analyzátor kódu v IPPcode21 (parse.php)
    -----------------------------------------------------------
    Jediný možný způsob spuštění je parser.php a STDIN vstup
    Vzor spuštění na Merlinovy: php7.4 parser.php <vstupnisoubor
    Jiná kombinace parametrů vede k chybě
    Autor: Vojtěch Šíma, xsimav01, 2021, FIT VUT";
        exit(0);
        }
        else
        {
            exit(10); #2 parametry a druhý není --help
        }
        
    }
    

$pom=0;
$BylIPPcode=false;
$checkSTDIN=false;
    while ($BylIPPcode==false&&$firstline=fgets(STDIN)) //while prochazejici dokuď jsou komentare nebo prazdne radky pred .IPPcode21 (pokud instrukce, chyba)
    {  
        $checkSTDIN=true;
        $firstline=trim($firstline); //odstraneni EOLu a mezer pred a po
    
        $pom=strpos($firstline , "#"); //hledani # (pokud je, vrati jeho pozici)
        if($pom!=NULL)
        {
            $firstline = substr($firstline, 0, $pom); //uzitecny string je od zacatku po #
           
        }
        
        $firstline=strtolower($firstline);
        if(strcmp($firstline,".ippcode21")==0) #hlavička
        {
            $BylIPPcode=true;
            echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<program language=\"IPPcode21\">\n";
        }
        else if (!($firstline==NULL||$firstline[0]=="#")) //pokud to neni  null(empty line) nebo to neni # tak chyba
        {
            echo "instrukce pred IPPcode  ".PHP_EOL; //DEBUG
            exit(21); //todo oopravdu 21?
        }

    } 
    if($checkSTDIN!=true)
    {
        exit(0); #nebyl stdin
    }
    if($BylIPPcode==False)
    {
        exit(21); #nebyla hlavicka
    }
    
#while který se opakuje po nalezená hlavičky do konce
while ($line=fgets(STDIN))
    {
        $line=trim($line); //odstraneni EOLu a mezer pred a po
        $pom=strpos($line , "#");
        if($pom!=NULL)
        {
            $line = substr($line, 0, $pom);
        }
        //$line = explode("#",$line); //smazani komentaru
        $word = preg_split('/[\s]+/',$line); //odmazani bilych znaku
        $word[0]=strtoupper($word[0]); //neni case sensitive
        if($word[0][0]=="#")
        {
            $word[0]=NULL;
        }
        if($word[0]!=NULL)  //pokud neni prazdny radek => bude instrukce
        {
            //echo $word[0]."\n"; //DEBUG
            $counter=$counter+1;

            switch ($word[0]){

                case ".IPPCODE21":
                    #echo "\033[31m hlavicka podruhe \033[0m  \n"; //DEBUG
                    exit(21);
                    break;

                #<var>
                case "POPS": 
                case "DEFVAR":     
                        if(checkVar($word[1])!=true||$word[2]!=NULL)
                        {
                            #echo "\033[31m chyba DEFVAR/POPS \033[0m  \n"; //DEBUG
                            exit(23);
                        }

                        instructionStart($counter,$word[0]);
                        VarPrint($word[1],1);
                        instructionEnd();

                    break;     
                
                case "MOVE": //<var> <symbol>
                    if((checkVar($word[1])==true)&&(checkSymbol($word[2])==true))
                    {
                        instructionStart($counter,$word[0]);
                        VarPrint($word[1],1);
                        SymbolPrint($word[2],2);
                        instructionEnd();
                    }
                    else
                    {             
                        echo "\033[31m MOVE NOT OK  \033[0m  \n"; //DEBUG
                        exit(23);
                    }
                    break;
                #<label>
                case "LABEL":
                case "CALL":
                case "JUMP":
                
                    if (($word[2]!=NULL)||$word[1]==NULL)
                    {   
                        echo "\033[31m chyba LABEL\CALL \033[0m  \n"; //DEBUG
                        exit(23);    
                    }
                        instructionStart($counter,$word[0]);
                        LabelPrint($word[1],1);
                        instructionEnd();
                    break;
                #<label> <symb> <symb>
                case "JUMPIFEQ": 
                case "JUMPIFNEQ":

                    if($word[1]==NULL||checkSymbol($word[2])!=true||checkSymbol($word[3])!=true||$word[4]!=NULL)
                        {
                            #echo "\033[31m chyba JUMPIFNEQ \033[0m  \n"; //DEBUG
                            exit(23);
                        }

                        instructionStart($counter,$word[0]);
                            LabelPrint($word[1],1); //label na ktery se skace 
                            SymbolPrint($word[2],2); //arg2 arg3
                            SymbolPrint($word[3],3);
                            instructionEnd();
                    break;
                   
                    #<symb> 
                case "WRITE":
                case "DPRINT":  
                case "EXIT":
                case "PUSHS":          
                        if((checkSymbol($word[1])!=true)||$word[2]!=NULL)
                        {
                            #echo "\033[31m chyba DPRINT/WRITE/EXIT/PUSHS \033[0m  \n"; //DEBUG
                            exit(23);             
                        }
                        instructionStart($counter,$word[0]);
                        SymbolPrint($word[1],1);
                        instructionEnd();
                        break;
                #⟨var⟩ ⟨type⟩  
                case "READ":
                        if(checkVar($word[1])!=true||checkType($word[2])!=true||$word[3]!=NULL)
                        {
                            #echo "\033[31m chyba READ \033[0m  \n"; //DEBUG
                            exit(23); 
                        }
                        instructionStart($counter,$word[0]);
                        VarPrint($word[1],1);
                        echo "\t\t<arg2 type=\"type\">$word[2]</arg2>\n";
                        instructionEnd();
                        
                        break;

                #⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
                case "ADD":
                case "SUB":
                case "MUL":
                case "IDIV":
                case "GT":
                case "LT":
                case "EQ":   
                case "AND":
                case "OR":
                case "NOT":
                case "STRI2INT":
                case "GETCHAR": 
                case "SETCHAR":   
                case "CONCAT": 
                        if(checkVar($word[1])!=true||checkSymbol($word[2])!=true||checkSymbol($word[3])!=true||$word[4]!=NULL)
                        {   
                            #echo "\033[31m chyba tam kde je hodne casu ($word[0]) \033[0m  \n"; //DEBUG
                            exit(23);  
                        }
                        instructionStart($counter,$word[0]);
                        VarPrint($word[1],1);
                        SymbolPrint($word[2],2);
                        SymbolPrint($word[3],3);
                        instructionEnd();
                        break;
                #<var><symbol>
                case "TYPE": 
                case "INT2CHAR":
                case "STRLEN": 
                        if(checkVar($word[1])!=true||checkSymbol($word[2])!=true||$word[3]!=NULL)
                        {
                            #echo "\033[31m chyba TYPE/INTTOCHAR/STRLEN \033[0m  \n"; //DEBUG
                            exit(23);  
                        }
                        instructionStart($counter,$word[0]);
                        VarPrint($word[1],1);
                        SymbolPrint($word[2],2);
                        instructionEnd();
                        break;
                #<> 
                case "CREATEFRAME":
                case "PUSHFRAME":
                case "POPFRAME":
                case "RETURN":
                case "BREAK": 
                        if($word[1]!=NULL)
                        {
                            #echo "\033[31m chyba tam kde je hodně casu \033[0m  \n"; //DEBUG
                            exit(23);
                        }
                        instructionStart($counter,$word[0]);
                        instructionEnd();
                    break;

                #pokud nenastane žadna očekavana instrukce
                default:   
                    #echo "\033[31m DEFAULT - neznamy operacni kod $word[0] \033[0m  \n"; //DEBUG
                    exit(22);
                             
            }  //konec switche

        } //konec ifu pokud neni prazdny radek => bude instrukce
       
          
    } //konec main whilu
    
       
        echo "</program>";
        //echo  "\033[32mOK\033[0m \n"; //DEBUG
        exit(0);
  
?>
