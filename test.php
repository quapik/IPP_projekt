<?php
#Vojtěch Šíma, xsimav01, IPP FIT VUT 2021
#Hlavní funkce pro prochzení složky
function ProchazeniSlozky($directorypath)
{
    if (file_exists($directorypath))
    {
        $open_dir=opendir($directorypath);
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
                    echo("<tr style=\"background-color:#ccccff \">".PHP_EOL."<td colspan=\"5\">".'SLOŽKA '.$filecheck."</td>".PHP_EOL."</tr>".PHP_EOL);
                    ProchazeniSlozky(($directorypath . "/" . $filecheck));

                }
            }
            
            if(is_file($directorypath . "/" . $filecheck))
            {
                $directorypathfile=$directorypath . "/" . $filecheck;
                $ext = pathinfo($directorypathfile, PATHINFO_EXTENSION);
                
                if ($ext == "src")
                {   $GLOBALS["CelkemTestu"]++;
                    $onlyfilename=pathinfo($directorypathfile, PATHINFO_FILENAME);
                    CheckOrCreateFiles($onlyfilename,$directorypath);
                    if($GLOBALS["IntOnly"]==True)
                    {
                    exec('python3.8 '.$GLOBALS["interpretfile"].' --source='.$directorypath.'/'.$filecheck.' --input='.$directorypath.'/'.$onlyfilename.'.in > outputinterpret', $output, $retval);
                    
                    
                    $filerc = fopen($directorypath.'/'.$onlyfilename.'.rc', 'r'); #otervreni souboru s ocekavanym RC a precteni prvniho stringu (zbytek ignorujeme)
                    $rc = fgets($filerc); 
                    fclose($filerc);
                    if($rc==$retval)
                    {   exec('diff outputinterpret '.$directorypath.'/'.$onlyfilename.'.out >diffinterpret');
                        if ($rc==0)
                        {
                            if(filesize("diffinterpret")==0) #pokud v diffsouboru nic neni zapsano tak jsou stejne outputy
                            {
                                $GLOBALS["CelkemTestuSpravne"]++;
                                echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>YES</td>".PHP_EOL."<td style=\"color:green\">OK</td>".PHP_EOL."</tr>".PHP_EOL);
                            }
                            else
                            {
                                echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>NO</td>".PHP_EOL."<td style=\"color:red\">NOT OK</td>".PHP_EOL."</tr>".PHP_EOL);
                            }
                        }
                        else
                        {
                            $GLOBALS["CelkemTestuSpravne"]++;
                            echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>---</td>".PHP_EOL."<td style=\"color:green\">OK</td>".PHP_EOL."</tr>".PHP_EOL);
                        }
                        
                        #odstraneni vytvorenych souboru
                        exec('rm diffinterpret');
                        exec('rm outputinterpret');
                    }
                    else
                    {
                        echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td> </td>".PHP_EOL."<td style=\"color:red\">NOT OK</td>".PHP_EOL."</tr>".PHP_EOL);
                    }
                    }
                    else if($GLOBALS["ParseOnly"]==True) 
                    {   
                        $filerc = fopen($directorypath.'/'.$onlyfilename.'.rc', 'r'); #otervreni souboru s ocekavanym RC a precteni prvniho stringu (zbytek ignorujeme)
                        $rc = fgets($filerc); 
                        fclose($filerc);
    
                        exec('php7.4 '.$GLOBALS["parsefile"]. ' <'. $directorypath.'/'.$filecheck. ' >parseout', $output, $retval);
                        
                        if($rc==$retval)
                        {
                            if($retval==0)
                            {
                                exec('java -jar '.$GLOBALS["jexamxmlfile"].' parseout '.$directorypath.'/'.$onlyfilename.'.out delta.xml '. $GLOBALS["jexamcfgfile"]. ' >diff');
                                $myFile = "diff";
                                $lines = file($myFile);//file in to an array
                                if ($lines[2] == "Two files are identical\n")
                                {
                                    $GLOBALS["CelkemTestuSpravne"]++;
                                    echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>YES</td>".PHP_EOL."<td style=\"color:green\">OK</td>".PHP_EOL."</tr>".PHP_EOL);                       
                                }
                                else
                                {
                                    echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>NO</td>".PHP_EOL."<td style=\"color:red\">NOT OK</td>".PHP_EOL."</tr>".PHP_EOL);
                                }
                                exec('rm diff');
                                exec('rm delta.xml');
                            }
                            else
                            {
                                $GLOBALS["CelkemTestuSpravne"]++;
                                echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>---</td>".PHP_EOL."<td style=\"color:green\">OK</td>".PHP_EOL."</tr>".PHP_EOL);
                            }
                        
                        }
                        else
                        {
                            echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td> </td>".PHP_EOL."<td style=\"color:red\">NOT OK</td>".PHP_EOL."</tr>".PHP_EOL);
                        }
                        exec('rm parseout');
                        
    
                    }
                    else
                    {
                        $filerc = fopen($directorypath.'/'.$onlyfilename.'.rc', 'r'); #otervreni souboru s ocekavanym RC a precteni prvniho stringu (zbytek ignorujeme)
                        $rc = fgets($filerc); 
                        fclose($filerc);
    
                        exec('php7.4 '.$GLOBALS["parsefile"]. ' <'. $directorypath.'/'.$filecheck.' >parseout', $output, $retval);
                        if ($retval!=0) #pokud nastala chyba v parseru
                        {
                            if ($retval==$rc)
                            {
                                $GLOBALS["CelkemTestuSpravne"]++;
                                echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>YES</td>".PHP_EOL."<td style=\"color:green\">OK(P)</td>".PHP_EOL."</tr>".PHP_EOL);
                            }
                            else
                            {
                                echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>NO</td>".PHP_EOL."<td style=\"color:red\">NOT OK(P)</td>".PHP_EOL."</tr>".PHP_EOL);
                            }
                            exec('rm parseout');
                        }
                        else
                        {
                            exec('python3.8 '.$GLOBALS["interpretfile"].' --source=parseout --input='.$directorypath.'/'.$onlyfilename.'.in > outputinterpret', $output, $retval);
                    
                            if($rc==$retval)
                            {   exec('diff outputinterpret '.$directorypath.'/'.$onlyfilename.'.out >diffinterpret');
                                if ($rc==0)
                                {
                                    if(filesize("diffinterpret")==0) #pokud v diffsouboru nic neni zapsano tak jsou stejne outputy
                                    {
                                        $GLOBALS["CelkemTestuSpravne"]++;
                                        echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>YES</td>".PHP_EOL."<td style=\"color:green\">OK</td>".PHP_EOL."</tr>".PHP_EOL);
                                    }
                                    else
                                    {
                                        echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>NO</td>".PHP_EOL."<td style=\"color:red\">NOT OK</td>".PHP_EOL."</tr>".PHP_EOL);
                                    }
                                }
                                else
                                {
                                    $GLOBALS["CelkemTestuSpravne"]++;
                            
                                    echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td>---</td>".PHP_EOL."<td style=\"color:green\">OK</td>".PHP_EOL."</tr>".PHP_EOL);
                                }
                                
                            
                                exec('rm diffinterpret');
                            }
                            else
                            {
                                echo("<tr>".PHP_EOL."<td>".$directorypath.'/'.$onlyfilename."</td>".PHP_EOL."<td>".$rc."</td>".PHP_EOL."<td>".$retval."</td>".PHP_EOL."<td> </td>".PHP_EOL."<td style=\"color:red\">NOT OK</td>".PHP_EOL."</tr>".PHP_EOL);
                            }
                        exec('rm parseout');
                        exec('rm outputinterpret');
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
echo("<html lang=\"cz\">".PHP_EOL);
echo("<head>".PHP_EOL);
echo("<title>test.php pro IPPCode21</title>".PHP_EOL);
echo("<style> ".PHP_EOL);
echo("table, th, td {border: 1px solid black;}".PHP_EOL);
echo("th, td { border-bottom: 1px solid #ddd;}".PHP_EOL);
echo("th, td{text-align: center;}".PHP_EOL);
echo("tr:hover {background-color: #f5f5f5;}".PHP_EOL);
echo("div>#T1 { float:left;}".PHP_EOL);
echo("div>#T2 { float:left;}".PHP_EOL);
echo("</style>".PHP_EOL);
echo("</head>".PHP_EOL);
echo("<body>".PHP_EOL."<div>".PHP_EOL);
echo("<table id=\"T1\">".PHP_EOL);
echo("<tr>".PHP_EOL."<th>Jméno testu</th>".PHP_EOL."<th>Očekáváný návratový kód</th>".PHP_EOL."<th>Návratový kód</th>".PHP_EOL."<th>OUTPUTY</th>".PHP_EOL."<th>Výsledek</th>".PHP_EOL."</tr>".PHP_EOL);
echo("<body>".PHP_EOL);
}
#Funkce pro koncové tagy htmml + tabulku s celkovou úspěšností
function HTMLEnd()
{
echo("</table>".PHP_EOL);
echo("<table id=\"T2\">".PHP_EOL);
$Spatne=$GLOBALS["CelkemTestu"]-$GLOBALS["CelkemTestuSpravne"];
$Procenta=($GLOBALS["CelkemTestuSpravne"]/$GLOBALS["CelkemTestu"])*100; #procentualni uspěšnost
echo("<tr>".PHP_EOL."<th>Počet testů</th>".PHP_EOL."<th>Správně</th>".PHP_EOL."<th>Špatně</th>".PHP_EOL."<th>Výsledekv %</th>".PHP_EOL."</tr>".PHP_EOL);
echo("<tr>".PHP_EOL."<td>".$GLOBALS["CelkemTestu"]."</td>".PHP_EOL."<td>".$GLOBALS["CelkemTestuSpravne"]."</td>".PHP_EOL."<td>".$Spatne."</td>".PHP_EOL."<td>".$Procenta."</td>".PHP_EOL."</tr>".PHP_EOL);
echo("</table>".PHP_EOL."</div>".PHP_EOL);
echo("</body>".PHP_EOL);
echo("</html>".PHP_EOL);
if (file_exists('parseout.log'))
    {
     exec('rm parseout.log');
    }
}
$BylDircetory=$BylParseScript=$BylInterpretScript=$ParseOnly=$IntOnly=$Byljexamxml=$Byljexamcfg=false;
$BylRecursive=false;
$CelkemTestu=0;
$CelkemTestuSpravne=0;
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
    #echo ($argv[$i].PHP_EOL);
    if ($argv[$i]=="--help")
    {
        exit(10);
    } 
    else if (preg_match("/^--directory=(\S)*$/",$argv[$i]))
    {   
        $BylDircetory=true;
        $splitted=explode("=",$argv[$i]);
        $directorypath=$splitted[1];
        if (empty($directorypath))
        {
            $directorypath=getcwd();
        }
    }
    else if ($argv[$i]=="--recursive")
    {
        $BylRecursive=true;
    }
    else if (preg_match("/^--parse-script=(\S)*$/",$argv[$i]))
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
    else
    {
        echo("spatny argument vstupu"); #DEBUG
        exit(10); 
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
if ($BylParseScript==false)
{
    $parsefile=getcwd()."/parse.php";  #TODO LINUX
}
if ($BylInterpretScript==false)
{
    $interpretfile=getcwd()."/interpret.py"; #TODO LINUX
}
if ($Byljexamxml==false)
{
    $jexamxmlfile="/pub/courses/ipp/jexamxml/jexamxml.jar";
}
if ($Byljexamcfg==false)
{
    $jexamcfgfile="/pub/courses/ipp/jexamxml/options";
}

HTMLStart();
ProchazeniSlozky($directorypath);
HTMLEnd();
exit(0);
?>
