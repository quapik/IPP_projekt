<?php
#Vojtěch Šíma, xsimav01, IPP FIT VUT 2021
#Hlavní funkce pro prochzení složky
function ProchazeniSlozky($directorypath)
{
    if (file_exists($directorypath))
    {
        $open_dir=opendir($directorypath); #otevření složky a čtení souborů z ní
        while($filecheck = readdir($open_dir)) 
        {   
            if($filecheck == "." OR $filecheck == "..")
            {
                continue; //ignorovani . a .. složek
            }
            
            if ($GLOBALS["BylRecursive"]==True) #pokud je argumentem zvoleno rekurzivni volani slozek a narazim na slozku misto souboru => volam funkci
            {
                if(is_dir($directorypath . "/" . $filecheck))
                {
                    $GLOBALS["T1"]=$GLOBALS["T1"]."<tr style=\"background-color:#190061 \">".PHP_EOL."<td colspan=\"5\">".'SLOŽKA '.$filecheck."</td>".PHP_EOL."</tr>".PHP_EOL;
                    ProchazeniSlozky(($directorypath . "/" . $filecheck));

                }
            }
            
            if(is_file($directorypath . "/" . $filecheck)) #pokud narazíme na soubor
            {
                $directorypathfile=$directorypath . "/" . $filecheck;
                $ext = pathinfo($directorypathfile, PATHINFO_EXTENSION);
                
                if ($ext == "src") #který je source 
                {   $GLOBALS["CelkemTestu"]++;
                    $onlyfilename=pathinfo($directorypathfile, PATHINFO_FILENAME); #získání pouhého jména
                    CheckOrCreateFiles($onlyfilename,$directorypath); #funkce pro kontrolu existence dalších souborů
                    if($GLOBALS["IntOnly"]==True) #pouze interpret tests
                    {
                    exec('python3.8 '.$GLOBALS["interpretfile"].' --source='.$directorypath.'/'.$filecheck.' --input='.$directorypath.'/'.$onlyfilename.'.in > outputinterpret', $output, $retval);
                    
                    
                    $filerc = fopen($directorypath.'/'.$onlyfilename.'.rc', 'r'); #otervreni souboru s ocekavanym RC a precteni prvniho stringu (zbytek ignorujeme)
                    $rc = fgets($filerc);  
                    fclose($filerc);
                    if($rc==$retval) #výsledek testu dle očekání
                    {   exec('diff outputinterpret '.$directorypath.'/'.$onlyfilename.'.out >diffinterpret');
                        if ($rc==0) #rc 0 =>kontrola obsahu
                        {
                            if(filesize("diffinterpret")==0) #pokud v diffsouboru nic neni zapsano tak jsou stejne outputy
                            {
                                $GLOBALS["CelkemTestuSpravne"]++;
                                $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>ANO</td>".PHP_EOL."<td style=\"color:green\">OK</td>".PHP_EOL."</tr>".PHP_EOL;
                            }
                            else #ruzne outputy, chyba
                            {
                                $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>NE</td>".PHP_EOL."<td style=\"color:red\">CHYBA</td>".PHP_EOL."</tr>".PHP_EOL;
                            }
                        }
                        else #spravne
                        {
                            $GLOBALS["CelkemTestuSpravne"]++;
                            $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>---</td>".PHP_EOL."<td style=\"color:green\">OK</td>".PHP_EOL."</tr>".PHP_EOL;
                        }
                        
                        #odstraneni vytvorenych souboru file_exists pro jistotu
                        if (file_exists('diffinterpret'))
                        {
                            exec('rm diffinterpret');
                        }
                        if (file_exists('outputinterpret'))
                        {
                            exec('rm outputinterpret');
                        }
                    }
                    else #nesedí RC
                    {
                        $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td> </td>".PHP_EOL."<td style=\"color:red\">CHYBA</td>".PHP_EOL."</tr>".PHP_EOL;
                    }
                    }
                    else if($GLOBALS["ParseOnly"]==True) #pouze parser testy
                    {   
                        $filerc = fopen($directorypath.'/'.$onlyfilename.'.rc', 'r'); #otervreni souboru s ocekavanym RC a precteni prvniho stringu (zbytek ignorujeme)
                        $rc = fgets($filerc); 
                        fclose($filerc);
    
                        exec('php7.4 '.$GLOBALS["parsefile"]. ' <'. $directorypath.'/'.$filecheck. ' >parseout', $output, $retval);
                        
                        if($rc==$retval)
                        {
                            if($retval==0)
                            {   #presemerovani vystupu do dočasneho souboru
                                exec('java -jar '.$GLOBALS["jexamxmlfile"].' parseout '.$directorypath.'/'.$onlyfilename.'.out delta.xml '. $GLOBALS["jexamcfgfile"]. ' >diff');
                                $CheckFile = "diff"; 
                                $lines = file($CheckFile);
                                if ($lines[2] == "Two files are identical\n") #načteni soboru ana druhem řadku info o porovnani
                                {
                                    $GLOBALS["CelkemTestuSpravne"]++;
                                    $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>ANO</td>".PHP_EOL."<td style=\"color:green\">OK</td>".PHP_EOL."</tr>".PHP_EOL;                       
                                }
                                else
                                {
                                    $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>NE</td>".PHP_EOL."<td style=\"color:red\">CHYBA</td>".PHP_EOL."</tr>".PHP_EOL;
                                }
                                if (file_exists('diff'))
                                    {
                                        exec('rm diff');
                                    }
                                if (file_exists('delta.xml'))
                                    {
                                        exec('rm delta.xml');
                                    }
                                
                            }
                            else #stejne RC
                            {
                                $GLOBALS["CelkemTestuSpravne"]++;
                                $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>---</td>".PHP_EOL."<td style=\"color:green\">OK</td>".PHP_EOL."</tr>".PHP_EOL;
                            }
                        
                        }
                        else #nsedei RC
                        {
                            $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td> </td>".PHP_EOL."<td style=\"color:red\">CHYBA</td>".PHP_EOL."</tr>".PHP_EOL;
                        }
                        if (file_exists('parseout'))
                            {
                                exec('rm parseout');
                            }
                        
    
                    }
                    else #vykonavani both testů
                    {
                        $filerc = fopen($directorypath.'/'.$onlyfilename.'.rc', 'r'); #otervreni souboru s ocekavanym RC a precteni prvniho stringu (zbytek ignorujeme)
                        $rc = fgets($filerc); 
                        fclose($filerc);
    
                        exec('php7.4 '.$GLOBALS["parsefile"]. ' <'. $directorypath.'/'.$filecheck.' >parseout', $output, $retval);
                        if ($retval!=0) #pokud nastala chyba v parseru
                        {
                            if ($retval==$rc) #chyba byla očekavana
                            {
                                $GLOBALS["CelkemTestuSpravne"]++;
                                $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>ANO</td>".PHP_EOL."<td style=\"color:green\">OK(P)</td>".PHP_EOL."</tr>".PHP_EOL;
                            }
                            else
                            {
                                $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>NE</td>".PHP_EOL."<td style=\"color:red\">CHYBA(P)</td>".PHP_EOL."</tr>".PHP_EOL;
                            }
                            if (file_exists('parseout')) 
                            {
                                exec('rm parseout');
                            }
                        }
                        else
                        {
                            exec('python3.8 '.$GLOBALS["interpretfile"].' --source=parseout --input='.$directorypath.'/'.$onlyfilename.'.in > outputinterpret', $output, $retval);
                    
                            if($rc==$retval) #spravne
                            {   exec('diff outputinterpret '.$directorypath.'/'.$onlyfilename.'.out >diffinterpret');
                                if ($rc==0) #spravne ale treba kontrola out
                                {
                                    if(filesize("diffinterpret")==0) #pokud v diffsouboru nic neni zapsano tak jsou stejne outputy
                                    {
                                        $GLOBALS["CelkemTestuSpravne"]++;
                                        $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>ANO</td>".PHP_EOL."<td style=\"color:green\">OK</td>".PHP_EOL."</tr>".PHP_EOL;
                                    }
                                    else
                                    {
                                        $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>NE</td>".PHP_EOL."<td style=\"color:red\">CHYBA</td>".PHP_EOL."</tr>".PHP_EOL;
                                    }
                                }
                                else #spravne
                                {
                                    $GLOBALS["CelkemTestuSpravne"]++;
                            
                                    $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>---</td>".PHP_EOL."<td style=\"color:green\">OK</td>".PHP_EOL."</tr>".PHP_EOL;
                                }
                                
                                if (file_exists('diffinterpret')) #docasne soubory
                                {
                                    exec('rm diffinterpret');
                                }
                                
                            }
                            else #spatne
                            {
                                $GLOBALS["T1"]=$GLOBALS["T1"]."<tr>".PHP_EOL."<td>".$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td> </td>".PHP_EOL."<td style=\"color:red\">CHYBA</td>".PHP_EOL."</tr>".PHP_EOL;
                            }
                            if (file_exists('parseout')) #docasne soubory
                            {
                                exec('rm parseout');
                            }
                            if (file_exists('outputinterpret'))
                            {
                                exec('rm outputinterpret');
                            }
                        }
                    
                        
                    }
                    
                }
            }
        }
    }
        
    }

#Funkce na základě jména zjisti zda potřebné soubory pro kontrolu existují a pokud ne, tak vytvoří dle zadání prázdné
function CheckOrCreateFiles($onlyfilename,$directorypath)
{
if (!file_exists($directorypath . "/" .  $onlyfilename.".rc"))
{
    touch($directorypath . "/" .  $onlyfilename .".rc");
    exec('echo "0">' . $directorypath . "/" .$onlyfilename . ".rc");
}
if (!(file_exists($directorypath . "/" .  $onlyfilename."out")))
{
    touch($directorypath . "/" .  $onlyfilename .".out");
   
}
if (!(file_exists($directorypath . "/" .  $onlyfilename."in")))
{
    touch($directorypath . "/" .  $onlyfilename .".in");
}
}
#Funkce na tisk uvodu html dokumentu, css, head pro první hlavní tabulku
function HTMLStart()
{
echo("<!doctype html>".PHP_EOL);
echo("<html lang=\"cz\" style=\"background-color:#000;\">".PHP_EOL);
echo("<head>".PHP_EOL);
echo("<title>test.php pro IPPCode21</title>".PHP_EOL);
echo("<style> ".PHP_EOL);
echo("tr:hover {background-color: #05386B;}".PHP_EOL);
echo("#T1 {float:left; font-family: Arial, Helvetica, sans-serif;border-collapse: collapse;border: 2px solid #000;width: 100%;}".PHP_EOL);
echo("#T2 {float:left; font-family: Arial, Helvetica, sans-serif;border-collapse: collapse;border: 2px solid #000;width: 100%;}".PHP_EOL);
echo("#T1 td, #T1 th {border: 1px solid #282828;padding: 8px; text-align: center; color: white;}".PHP_EOL);
echo("#T1 th {padding-top: 12px;padding-bottom: 12px; background-color: #0c0032; color: white;text-align: center;}".PHP_EOL);
echo("#T2 td, #T2 th {border: 1px solid #282828;padding: 8px; text-align: center; color: white;}".PHP_EOL);
echo("#T2 th {padding-top: 12px;padding-bottom: 12px; background-color: #45a29e; color: white;text-align: center;}".PHP_EOL);
echo("</style>".PHP_EOL);
echo("</head>".PHP_EOL);
echo("<body>".PHP_EOL);
}
#Funkce pro koncové tagy htmml + tabulku s celkovou úspěšností
function HTMLEnd()
{

echo("<table id=\"T2\">".PHP_EOL);
$Spatne=$GLOBALS["CelkemTestu"]-$GLOBALS["CelkemTestuSpravne"];
$Procenta=($GLOBALS["CelkemTestuSpravne"]/$GLOBALS["CelkemTestu"])*100; #procentualni uspěšnost
echo("<tr>".PHP_EOL."<th>Celkový počet testů</th>".PHP_EOL."<th>Počet správných testů</th>".PHP_EOL."<th>Počet špatných testů</th>".PHP_EOL."<th>Výsledek v %</th>".PHP_EOL."</tr>".PHP_EOL);
echo("<tr>".PHP_EOL."<td>".$GLOBALS["CelkemTestu"]."</td>".PHP_EOL."<td>".$GLOBALS["CelkemTestuSpravne"]."</td>".PHP_EOL."<td>".$Spatne."</td>".PHP_EOL."<td>".$Procenta."</td>".PHP_EOL."</tr>".PHP_EOL);
echo("</table>".PHP_EOL);
echo($GLOBALS["T1"]."</table>".PHP_EOL);
echo("</body>".PHP_EOL);
echo("</html>".PHP_EOL);
if (file_exists('parseout.log'))
    {
     exec('rm parseout.log');
    }
}
#----------------------------------------------------------------------------------------------------------------------
$BylDircetory=$BylParseScript=$BylInterpretScript=$ParseOnly=$IntOnly=$Byljexamxml=$Byljexamcfg=false;
$BylRecursive=false;
$CelkemTestu=0;
$CelkemTestuSpravne=0;
$T1="<table id=\"T1\">".PHP_EOL."<tr>".PHP_EOL."<th>Jméno testu</th>".PHP_EOL."<th>Očekáváný návratový kód</th>".PHP_EOL."<th>Návratový kód</th>".PHP_EOL."<th>Stejné výstupy</th>".PHP_EOL."<th>Výsledek</th>".PHP_EOL."</tr>".PHP_EOL;
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
for ($i=1; $i<$argc; $i++) #cyklus která projde všechny zadnané argumenty
{
    #echo ($argv[$i].PHP_EOL);
    if ($argv[$i]=="--help") #pokud je zadána help jinde než osamoceně => chyba
    {
        exit(10);
    } 
    else if (preg_match("/^--directory=(\S)*$/",$argv[$i]))
    {   
        $BylDircetory=true; #nastavení na true pro následnou kontroli
        $splitted=explode("=",$argv[$i]); #rozdělení na část před = a po a uložení pouze cesty
        $directorypath=$splitted[1]; 
        if (empty($directorypath)) # pokud pouze --directory= tak aktuílní adresář
        {
            $directorypath=getcwd();
        }
        if (!(is_dir($directorypath))) #pokud zadáno není cesta do složky, tak chyba
        {
            exit(41);
        }
    }
    else if ($argv[$i]=="--recursive")   #rekurzivní procházení složek
    {
        $BylRecursive=true;
    }
    else if (preg_match("/^--parse-script=(\S)*$/",$argv[$i])) #cesta k parse file
    {
        $pom=explode("=",$argv[$i]);
        $parsefile=$pom[1];
        if (!(file_exists($parsefile))) #kontrola zda soubor existuje
        {
            exit(41);
        }
        $BylParseScript=true;
    }
    else if (preg_match("/^--int-script=(\S)+$/",$argv[$i])) #cesta k interpret file
    {
        $pom=explode("=",$argv[$i]);
        $interpretfile=$pom[1];
        print($interpretfile);
        if (!(file_exists($interpretfile)))
            {
                exit(41);
            }
        $BylInterpretScript=true;
    }
    else if ($argv[$i]=="--parse-only") #přepínače pro vykonávání kontroly pouze části
    {
        $ParseOnly=true;
    }
    else if ($argv[$i]=="--int-only")
    {
        $IntOnly=true;
    }
    else if (preg_match("/^--jexamxml=(\S)+$/",$argv[$i])) #pro jexaxml když zadáno
    {    
        $Byljexamxml=true;
        $pom=explode("=",$argv[$i]);
        $jexamxmlfile=$pom[1];
        if (!(file_exists($jexamxmlfile)))
        {
            exit(41);
        }
    }
    else if (preg_match("/^--jexamcfg=(\S)+$/",$argv[$i]))
    {    
        $Byljexamcfg=true;
        $pom=explode("=",$argv[$i]);
        $jexamcfgfile=$pom[1];
        if (!(file_exists($jexamcfgfile)))
        {
            exit(41);
        }
    }
    else
    {
        exit(10); #chyba - zadn neznámý argument
    }
}
if ($ParseOnly==true)  #kontrola že nekoliduje prase only s int only a cestou k němu a naopak
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
if ($BylDircetory==false) #pokud nebyly zadány přepínače, tak je brána aktuálni složka, v ní parse.php a intepret.py a defuaultní umístění jeaxamxml
{
    $directorypath=getcwd();
}
if ($BylParseScript==false)
{
    $parsefile=getcwd()."/parse.php"; 
}
if ($BylInterpretScript==false)
{
    $interpretfile=getcwd()."/interpret.py"; 
}
if ($Byljexamxml==false)
{
    $jexamxmlfile="/pub/courses/ipp/jexamxml/jexamxml.jar";
}
if ($Byljexamcfg==false)
{
    $jexamcfgfile="/pub/courses/ipp/jexamxml/options";
}

HTMLStart(); #vypis startu html
ProchazeniSlozky($directorypath); #volani funcke prochazeni složky
HTMLEnd(); #vypis konce html
exit(0);
?>
