#Vojtěch Šíma, xsimav01, IPP FIT VUT 2021
import sys
import xml.etree.ElementTree as ET
import re

BylSource=False
BylInput=False

if (len(sys.argv)>3) or len(sys.argv)==1 :
    print("Špatný počet argumentů")
    exit(21)
if(len(sys.argv)==2):
    if (sys.argv[1]=="--help"):
        print("Vypsat nápovědu")
        exit(0)
    elif (sys.argv[1][0:9]=="--source="):
        sourcefile=sys.argv[1][9:len(sys.argv[1])]
        BylSource=True
    elif(sys.argv[1][0:8]=="--input="):
        inputfile=sys.argv[1][8:len(sys.argv[1])]
        BylInput=True
    else:
        print("Chyba v argumetnech")
        exit(10)
else:
    if(sys.argv[1][0:9]=="--source="):
        sourcefile=sys.argv[1][9:len(sys.argv[1])]
        BylSource=True

    elif(sys.argv[1][0:8]=="--input="):
        inputname=sys.argv[1][8:len(sys.argv[1])]
        BylInput=True
    else:
        print("errror - spatny argument")
        exit(10) #TODO

    if(sys.argv[2][0:9]=="--source="):
        if(BylSource is True):
            exit(10) 
        sourcefile=sys.argv[2][9:len(sys.argv[2])]
        BylSource=True

    elif(sys.argv[2][0:8]=="--input="):
        if(BylInput is True):
            exit(10) #chech exit
        inputname=sys.argv[2][8:len(sys.argv[2])]
        BylInput=True
    else:
        print("errror - spatny argument") #DEBUG
        exit(10) #TODO

    if BylInput is False and BylSource is False:
        print("nebyl zadan ani input ani source") #DEBUG
        exit(10)
#------------------------------------------------------------------------------------------------------------------------------
variables=[]
variablesValues=[]
LFStack=[]
all=[]
labels=[]
numbers=[]
stackValues = []
stackVolani = []
SymbolTypeList=["int","var","nil","string","bool"]
BylCreatframe=False
TF=None
def CheckTypes(argtype,data):
    if argtype=="int":
        if not (re.search('^[-+]?[0-9]+$',data)):
            print("Chyba - type int, ale text not int")#DEBUG
            exit(32)
    elif argtype=="string":
        if not(re.search(r'\S+',data)): #TODO!
            exit(32)
    elif argtype=="bool":
        if not (re.search('(true|false)$',data)):
            print("Chyba - type bool") #DEBUG
            exit(32)
    elif argtype=="var":
        if not (re.search(r'((TF|GF|LF)@[A-Za-z$?!&%*\-_]+[A-Za-z0-9\w$?!&%*\-_]*)',data)): #chech tento regex
            print("Chyba - type var") #DEBUG
            exit(32)
    elif argtype=="label":
        pass
    elif argtype=="nil":
        if data != "nil":
            exit(32)
    elif argtype=="type":
        if not (re.search('(int|bool|string)$',data)):
            exit(32)
    else:
        print("Špatný argtyp")
        exit(32)
    #TODO VAR,LABEL,NIL? 
def CheckSymbolType(symboltype):
    if symboltype not in SymbolTypeList:
        print("Chyba arg type=symbol") #DEBUG
        exit(32)
#funkce který zkontroluje počet argumentů instrukce
def CheckPocetArgumentuInstukce(child,pocet):
    for i in range(pocet):
        try:
            child[i].attrib['type']
        except:
                print("Error s počtem argumentů- CheckPocetArgumentuInstukce") #DEBUG
                exit(32)
        CheckTypes(child[i].attrib['type'],child[i].text)
        text=str(child[i])
        if(text[10:13]!="arg" or int(text[13])!=(i+1)): #TODO or minimalně čeknout
            print("Chyba v argumentu") #DEBUG
            exit(32)
    try:
        child[pocet].attrib['type']
    except:
        pass
    else:
        print("Error s počtem argumentů- CheckPocetArgumentuInstukce") #DEBUG
        exit(32)

def VarTypeCheckReturn(child,datovytyp):
    if(child.attrib['type']==datovytyp):
        return child.text
    elif(child.attrib['type']=="var"):
        try:
            index=variables.index(child.text)
        except ValueError:  #hodnota nebyla definovana => error a pridame
            print("promenna",child.text,"nebyla definovana" ) #DEBUG
            exit(55)
        else:
            if variablesValues[index][0] == datovytyp:
                return variablesValues[index][1]
            else:
                print("Chybas")
                exit(53)
    else:
        print("Špatné typy operandů")
        exit(53)
def VarCheckDefinovanaReturnIndex(variable):
    try:
        index=variables.index(variable)
    except ValueError:  #hodnota nebyla definovana => error a pridame
        print("promenna",variable,"nebyla definovana" ) #DEBUG
        exit(54)
    else:
        return index

            
#-----------------------------------------------------------------------------------------------------
try:
    if(BylInput==True): #pokud je input file jako parametr, jinak stdin
        inputfile = open(inputname, 'r')
    else:
        inputfile=sys.stdin
except:
    print("Nejde otevrit vstupni soubor") #DEBUG
    exit(32)

try:
    if(BylSource==True):  #pokud je source file jako parametr, jinak stdin
        tree = ET.parse(sourcefile)
    else:
        tree = ET.parse(sys.stdin)
except:
    exit(31) #pokud je nějaka chyba v načitanem xml souboru
root = tree.getroot()

for child in root: 
    if child.tag != "instruction": #kontrola spravneho childtagu
        exit(32)
    try:
        child.attrib['opcode'] #kontrola zda jsou obsaženy atributy
        child.attrib['order']
    except:
        exit(32) 
    try:
        int(child.get('order')) #kontrola že je order číslo
    except ValueError:
        exit(32)
    root[:] = sorted(root, key=lambda child: int(child.get('order'))) #seřadit child podle čísla orderu
    for arg in child:
        child[:] = sorted(child, key=lambda arg: str(arg.tag)) #seřadit arg podle abecedy

#Kontrola xml elemetů apod v rootu
try:
    language = root.attrib['language'] #kontrola zda je atribut language a nasledne že je Ippcode
except:
    exit(32)

if root.tag != "program":
    exit(32)

if (language !='IPPcode21'):
    print("Chybna hlavička\n") #DEBUG
    exit(32)

for child in root: #projiti vsech instrukci a ulozeni do listu all, labely do labels a cisla instrukci do numbers

    if(child.attrib['opcode']=="LABEL"): #pokud label, kontrola zda už nebyl a pak ulozeni do labelu
        try:
            labels.index(child[0].text)
        except:
            pass
        else:
            print("Label podruhe") #DEBUG
            exit(52)
        labels.append(child[0].text)
    if(child.attrib['opcode']=="JUMP" or child.attrib['opcode']=="JUMPIFEQ" or child.attrib['opcode']== "JUMPIFNEQ"  or child.attrib['opcode']== "CALL"):
        all.append("jump_"+child[0].text)
    elif child.attrib['opcode']=="BREAK":
        all.append("instrukceBREAK")
    elif child.attrib['opcode']=="RETURN":
        all.append("instrukceRETURN")
    else:
        try: 
            all.append(child[0].text)
        except:
            exit(32)
    
    try: #pokud se podari najit chyba, už jeden label byl
        numbers.index(child.attrib['order'])
    except:
        pass
    else:
        print("Opakuje se stejne instruction order") # DEBUG
        exit(32)
    if(int(child.attrib['order'])<1):
        exit(32)
    numbers.append(child.attrib['order'])

def prochazej(root):
    for child in root:
        opcode=(child.attrib['opcode'])
        opcode = opcode.upper()
        if (opcode=="WRITE"):
            CheckPocetArgumentuInstukce(child,1)
            if child[0].attrib['type'] == "var":
                index=VarCheckDefinovanaReturnIndex(child[0].text)
                if variablesValues[index][0]=="nil":
                    print("",end='')
                else:
                    print(variablesValues[index][1],end='')#Samotný výpis WRITU == NEMAZAT! 
            else:
                if child[0].attrib['type']=="nil":
                    print("",end='')
                else:
                    print(child[0].text,end='') #Samotný výpis WRITU == NEMAZAT!
        elif (opcode=="DEFVAR"):
            CheckPocetArgumentuInstukce(child,1)
            try:
                variables.index(child[0].text)
            except:  #hodnota nebyla definovana => error a pridame
                variables.append(child[0].text)
                variablesValues.append([None,None])
            else:
                print("Definice proměnne podruhé!")#DEBUG
                exit(52)
        elif (opcode=="MOVE"):
            CheckPocetArgumentuInstukce(child,2)
            if child[0].attrib['type']!="var":
                print("MOVE arg1 neni VAR") #DEBUG
                exit(52)
            index=VarCheckDefinovanaReturnIndex(child[0].text)
            variablesValues[index][0] = child[1].attrib['type']
            variablesValues[index][1] = child[1].text
        elif opcode=="READ":
            CheckPocetArgumentuInstukce(child,2)
            if child[0].attrib['type']!="var" or child[1].attrib['type']!="type":
                 exit(52)
            index=VarCheckDefinovanaReturnIndex(child[0].text)
            datovytyp=child[1].text #v textove casti je datovy typ co se nacita (int,bool,string)
            hodnota=inputfile.readline() #nacteni z input souboru
            hodnota = hodnota.replace('\n','')
            chyba=False
            if datovytyp=="int":
                try:
                    hodnota=int(hodnota)
                except ValueError: #pokud tam neni hodnota co nejde prevest na int => bude nil@nil
                    chyba=True
                else:
                    variablesValues[index][0]="int"
                    variablesValues[index][1]=hodnota
            elif datovytyp=="string":
                if isinstance(hodnota, str):
                    variablesValues[index][0]="string"
                    variablesValues[index][1]=hodnota
                else:
                    chyba=True
            elif datovytyp=="bool": #true na true, vse ostatni na false
                variablesValues[index][0]="bool"
                if hodnota.lower() == "true":
                    variablesValues[index][1]="true"
                else:
                    variablesValues[index][1]="false"
            if (chyba is True):
                variablesValues[index][0]="nil"
                variablesValues[index][1]="nil"

        elif (opcode=="CREATEFRAME"):
            BylCreatframe=True
            TF=None
        elif (opcode=="PUSHFRAME"):
            if BylCreatframe == False:
                print("Pokus o přístup k nedefinovanému rámci") #DEBUG
                exit(55)
            LF=TF
            LFStack.append(TF)
            BylCreatframe=False
        elif (opcode=="POPFRAME"):
            try:
                TF=LFStack.pop()
            except IndexError:
                print("Chyba POPFRAME-prazdny zasobnik")
                exit(55)
            BylCreatframe=True #TODO toto udelat spravně
        elif (opcode=="ADD" or opcode=="SUB" or opcode=="MUL" or opcode=="IDIV"):
            CheckPocetArgumentuInstukce(child,3)
            if child[0].attrib['type'] == "var":
                index=VarCheckDefinovanaReturnIndex(child[0].text)
                variablesValues[index][0]="int"
                try:
                    cislo1=int(VarTypeCheckReturn(child[1],"int"))
                    cislo2=int(VarTypeCheckReturn(child[2],"int"))         
                except:
                    exit(53)      
            if opcode=="ADD": #todo for variables
                variablesValues[index][1] = cislo1 + cislo2
            elif opcode=="SUB":
                variablesValues[index][1] = cislo1 - cislo2
            elif opcode=="MUL":
                variablesValues[index][1] = cislo1 * cislo2
            else: #IDIV
                if cislo2 == 0:
                    print("Dělění nulou")
                    exit(57)
                variablesValues[index][1] = cislo1 // cislo2 #zapsani vysledku do proměnne              
        elif(opcode=="JUMP" or opcode=="LABEL"):
            CheckPocetArgumentuInstukce(child,1)
            if child[0].attrib['type'] != "label":
                exit(32)
            if(opcode=="JUMP"):
                try:
                    labels.index(child[0].text)
                except:
                    print("label neexistuje (JUMP)") #DEBUG
                    exit(52)
                else:
                    root=saveroot
                    prochazej(root[int(all.index(child[0].text)):])

        elif opcode=="JUMPIFEQ" or opcode=="JUMPIFNEQ":
            CheckPocetArgumentuInstukce(child,3)
            if (child[0].attrib['type'] != "label"):
                exit(53)
            if (child[1].attrib['type']=="var"): #zjištění jaké datové typy by se měly v porovnavani objěvit
                index=VarCheckDefinovanaReturnIndex(child[1].text)
                datatype=variablesValues[index][0]
            else:
                datatype=child[1].attrib['type']

            hodnota1=VarTypeCheckReturn(child[1],datatype)
            hodnota2=VarTypeCheckReturn(child[2],datatype)
            isSame=False
            if(datatype=="int"):
                if int(hodnota1)==int(hodnota2):
                    isSame=True
            else:
                if(hodnota1==hodnota2):
                    isSame=True

            if ((opcode=="JUMPIFEQ" and isSame) or (opcode=="JUMPIFNEQ" and isSame==False)):
                try:
                    labels.index(child[0].text)
                except:
                    print("label neexistuje (JUMPIF)") #DEBUG
                    exit(52)
                else:
                    root=saveroot
                    prochazej(root[all.index(child[0].text):])
                
        elif opcode=="STRLEN":
            CheckPocetArgumentuInstukce(child,2)
            str1=VarTypeCheckReturn(child[1],"string")
            if (child[0].attrib['type'] == "var"):
                index=VarCheckDefinovanaReturnIndex(child[0].text)
                variablesValues[index][0]="int"
                variablesValues[index][1]=len(str1)
            else:
                print("chyba STRLEN") #DEBUG
                exit(53)
               
        elif  opcode=="CONCAT":
            CheckPocetArgumentuInstukce(child,3)
            str1=VarTypeCheckReturn(child[1],"string")
            str2=VarTypeCheckReturn(child[2],"string")
            if (child[0].attrib['type'] == "var"):
                index=VarCheckDefinovanaReturnIndex(child[0].text)
                variablesValues[index][0]="string"
                variablesValues[index][1]=str1+str2
            else:
                print("CHYBA CONCAT") #DEBUG
                exit(53)
        elif  opcode=="GETCHAR":
            CheckPocetArgumentuInstukce(child,3)
            str1=VarTypeCheckReturn(child[1],"string")
            pozice=VarTypeCheckReturn(child[2],"int")
            try:
                str1=str1[int(pozice)]
            except:
                exit(58)
            else:
                if (child[0].attrib['type'] == "var"):
                    index=VarCheckDefinovanaReturnIndex(child[0].text)
                    variablesValues[index][0]="string"
                    variablesValues[index][1]=str1
                else:
                    print("CHYBA GETCHAR") #DEBUG
                    exit(53)
        elif opcode=="SETCHAR":
            CheckPocetArgumentuInstukce(child,3)
            pozice=VarTypeCheckReturn(child[1],"int")
            str1=VarTypeCheckReturn(child[2],"string")   
            if (child[0].attrib['type'] == "var"):
                index=VarCheckDefinovanaReturnIndex(child[0].text)
                if(variablesValues[index][0]!="string"):
                    exit(53) #TODO CHECK!
                print(int(pozice),len(variablesValues[index][1]))
                if int(pozice)>(len(variablesValues[index][1])-1):
                     exit(58)
                try:
                   variablesValues[index][1] = variablesValues[index][1][:int(pozice)] + str1[0] + variablesValues[index][1][int(pozice):]
                except:
                    print("Chyba SETCHAR") #DEBUG
                    exit(58)
            else:
                exit(53)
        elif opcode=="TYPE":
            CheckPocetArgumentuInstukce(child,2)
            if (child[0].attrib['type'] == "var"):
                index=VarCheckDefinovanaReturnIndex(child[0].text)
                if child[1].attrib['type'] == "string" or child[1].attrib['type'] == "int" or child[1].attrib['type'] == "nil" or child[1].attrib['type'] == "bool":
                    variablesValues[index][0]="string"
                    variablesValues[index][1]=child[1].attrib['type']
                else:
                    index2=VarCheckDefinovanaReturnIndex(child[1].text)
                    vartyp=variablesValues[index2][0]
                    if vartyp is None:
                        variablesValues[index][1]=""
                    else:
                        variablesValues[index][1]=vartyp

            else:
                exit(53)
        elif opcode=="INT2CHAR":
            CheckPocetArgumentuInstukce(child,2)
            if (child[0].attrib['type'] != "var"):
                exit(53)
            index=VarCheckDefinovanaReturnIndex(child[0].text)
            znak=VarTypeCheckReturn(child[1],"int")
            try:
                znak=chr(int(znak))
            except ValueError:
                print("Chyba int2char") #DEBUG
                exit(58)
            else:
                variablesValues[index][0]="string"
                variablesValues[index][1]=znak
        elif opcode=="STRI2INT":
            CheckPocetArgumentuInstukce(child,3)
            if (child[0].attrib['type'] != "var"):
                exit(53)
            str1=VarTypeCheckReturn(child[1],"string")
            pozice=int(VarTypeCheckReturn(child[2],"int"))
            index=VarCheckDefinovanaReturnIndex(child[0].text)

            try:
                znak=str1[pozice]
                znak=ord(znak)
            except: 
                print("Chyba STRI2INT") #DEBUG
                exit(58)
            else:
                variablesValues[index][0]="int"
                variablesValues[index][1]=znak
        elif opcode=="EXIT":
            CheckPocetArgumentuInstukce(child,1)
            pozice=int(VarTypeCheckReturn(child[0],"int"))
            if pozice < 0 or pozice > 49:
                print("CHyba exit")#DEBUG
                exit(57)
            exit(pozice)
        elif opcode=="AND" or opcode=="OR":
            CheckPocetArgumentuInstukce(child,3)
            if (child[0].attrib['type'] != "var"):
                exit(53)
            index=VarCheckDefinovanaReturnIndex(child[0].text)
            bool1=VarTypeCheckReturn(child[1],"bool")
            bool2=VarTypeCheckReturn(child[2],"bool")
            variablesValues[index][0]="bool"
            if opcode=="AND":
                if bool1=="true" and bool2=="true":
                     variablesValues[index][1]="true" 
                else:
                    variablesValues[index][1]="false"          
            else:
                if bool1=="true" or bool2=="true":
                    variablesValues[index][1]="true"
                else:
                    variablesValues[index][1]="false"
        elif opcode=="NOT":
            CheckPocetArgumentuInstukce(child,2)
            if (child[0].attrib['type'] != "var"):
                exit(53)
            index=VarCheckDefinovanaReturnIndex(child[0].text)
            variablesValues[index][0]="bool"
            bool1=VarTypeCheckReturn(child[1],"bool") #negace, proste nastaveni opacne hodnoty
            if bool1=="true":
                variablesValues[index][1]="false"
            else:
                variablesValues[index][1]="true"

        elif opcode=="GT" or opcode=="LT":
            CheckPocetArgumentuInstukce(child,3)
            if (child[0].attrib['type'] != "var"):
                exit(53)
            if (child[1].attrib['type']=="nil" or child[2].attrib['type']=="nil"): #v GT a LT nemůže být nil
                exit(53)
            if child[1].attrib['type']!="var" and child[2].attrib['type']!="var":
                datovytypeporovnavani=child[1].attrib['type']
            if child[1].attrib['type']=="var":
                index1=VarCheckDefinovanaReturnIndex(child[1].text)
                datovytypeporovnavani=variablesValues[index1][0]
            if child[2].attrib['type']=="var":
                index1=VarCheckDefinovanaReturnIndex(child[2].text)
                datovytypeporovnavani=variablesValues[index1][0]
            hodnota1=VarTypeCheckReturn(child[1],datovytypeporovnavani)
            hodnota2=VarTypeCheckReturn(child[2],datovytypeporovnavani)
            index=VarCheckDefinovanaReturnIndex(child[0].text)
            variablesValues[index][0]="bool"
            if datovytypeporovnavani=="int":
                hodnota1=int(hodnota1)
                hodnota2=int(hodnota2)
            if opcode=="LT":
                if hodnota1<hodnota2:
                    variablesValues[index][1]="true"
                else:
                    variablesValues[index][1]="false"
            else:
                if hodnota1>hodnota2:
                    variablesValues[index][1]="true"
                else:
                    variablesValues[index][1]="false"

        elif opcode=="EQ":
            CheckPocetArgumentuInstukce(child,3)
            if (child[0].attrib['type'] != "var"):
                exit(53)
            index=VarCheckDefinovanaReturnIndex(child[0].text)
            if (child[1].attrib['type']=="nil" or child[2].attrib['type']=="nil"): #pokud konstatny a jedna z nich je nil, rovnou víme výsledek
                if (child[1].attrib['type']=="nil" and child[2].attrib['type']=="nil"):
                    variablesValues[index][0]="true"
                else:
                    if child[1].attrib['type']=="nil":
                        datovytypeporovnavani1="nil"
                    else:
                        datovytypeporovnavani="nil"

            if child[1].attrib['type']!="var" and child[2].attrib['type']!="var":
                datovytypeporovnavani=child[1].attrib['type']
            nilcounter=0
            if child[1].attrib['type']=="var":
                index1=VarCheckDefinovanaReturnIndex(child[1].text)
                datovytypeporovnavani1=variablesValues[index1][0]
                if  datovytypeporovnavani1=="nil":
                    nilcounter=nilcounter+1
            else:
                datovytypeporovnavani1=child[1].attrib['type']
                if  datovytypeporovnavani1=="nil":
                    nilcounter=nilcounter+1
            if child[2].attrib['type']=="var":
                index2=VarCheckDefinovanaReturnIndex(child[2].text)
                datovytypeporovnavani=variablesValues[index2][0]
                if  datovytypeporovnavani=="nil":
                    datovytypeporovnavani=datovytypeporovnavani1
                    nilcounter=nilcounter+1
            else:
                datovytypeporovnavani=child[2].attrib['type']
                if  datovytypeporovnavani=="nil":
                    datovytypeporovnavani=datovytypeporovnavani1
                    nilcounter=nilcounter+1
            if nilcounter!=0: #pokud aspon jedna z promennych je var
                if nilcounter == 1:
                    variablesValues[index][1]="false"
                if nilcounter == 2:
                    variablesValues[index][1]="true"
            else:
                hodnota1=VarTypeCheckReturn(child[1],datovytypeporovnavani)
                hodnota2=VarTypeCheckReturn(child[2],datovytypeporovnavani)
                if datovytypeporovnavani=="int":
                    hodnota1=int(hodnota1)
                    hodnota2=int(hodnota2)
                if hodnota1==hodnota2:
                    variablesValues[index][1]="true"
                else:
                    variablesValues[index][1]="false"
            variablesValues[index][0]="bool"

        elif opcode=="PUSHS":
            CheckPocetArgumentuInstukce(child,1)
            if (child[0].attrib['type'] != "var"):
                stackValues.append([child[0].attrib['type'],child[0].text])
            else:
                index=VarCheckDefinovanaReturnIndex(child[0].text)
                stackValues.append([variablesValues[index][0],variablesValues[index][1]])

           
        elif opcode=="POPS":
            CheckPocetArgumentuInstukce(child,1)
            if (child[0].attrib['type'] != "var"):
                exit(53)
            index=VarCheckDefinovanaReturnIndex(child[0].text)
            try:
                pop=stackValues.pop()
            except:
                print("Prazdny stack") #DEBUG
                exit(56)
            variablesValues[index][0]=pop[0]
            variablesValues[index][1]=pop[1]

        elif opcode=="DPRINT":
            CheckPocetArgumentuInstukce(child,1)
            if (child[0].attrib['type'] == "var"):
                index=VarCheckDefinovanaReturnIndex(child[0].text)
                sys.stderr.write(variablesValues[index][1])
            else:
                sys.stderr.write(child[0].text)
        elif opcode=="BREAK": #TODO
            CheckPocetArgumentuInstukce(child,0)
            sys.stderr.write("BREAK TODO")
        
        elif opcode=="CALL":
            CheckPocetArgumentuInstukce(child,1)
            if (child[0].attrib['type'] != "label"):
                exit(53)
            try:
                labels.index(child[0].text)
            except:
                print("label neexistuje (JUMP)") #DEBUG
                exit(52)
            else:
                root=saveroot
                stackVolani.append("jump_"+child[0].text) #ulozeni takto aby se rozlislo od labelu
                prochazej(root[all.index(child[0].text):]) #skok na label
            
        elif opcode=="RETURN":
            CheckPocetArgumentuInstukce(child,0) #popnuti hodnoty odkud byl call
            try:
                pozice=stackVolani.pop()
            except:
                print("Prazdny stack") #DEBUG
                exit(56)
            else:
                root=saveroot
                prochazej(root[all.index(pozice)+1:]) #skok na ni
        else:
            print("neznama instrukce") #DEBUG
            exit(32)

    exit(0)

                 
saveroot=root
prochazej(root)

exit(0)
