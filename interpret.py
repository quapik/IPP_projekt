#Vojtěch Šíma, xsimav01, IPP FIT VUT 2021
import sys
import xml.etree.ElementTree as ET
import re
variables=[]
LFStack=[]
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


    else:
        print("Špatný argtyp")
        exit(32)
    #TODO VAR,LABEL,NIL? 
        


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
        print(sourcefile)

    elif(sys.argv[1][0:8]=="--input="):
        inputfile=sys.argv[1][8:len(sys.argv[1])]
        BylInput=True
        print(inputfile)
    else:
        print("errror - spatny argument")
        exit(10) #TODO

    if(sys.argv[2][0:9]=="--source="):
        if(BylSource is True):
            exit(10) 
        sourcefile=sys.argv[2][9:len(sys.argv[2])]
        BylSource=True
        print(sourcefile)

    elif(sys.argv[2][0:8]=="--input="):
        if(BylInput is True):
            exit(10) #chech exit
        inputfile=sys.argv[2][8:len(sys.argv[2])]
        BylInput=True
        print(inputfile)
    else:
        print("errror - spatny argument") #DEBUG
        exit(10) #TODO

    if BylInput is False and BylSource is False:
        print("nebyl zadan ani input ani source") #DEBUG
        exit(10)
#------------------------------------------------------------------------------------------------------------------------------
try:
    if(BylSource==True):  #pokud je source file jako parametr, jinak stdin
        tree = ET.parse(sourcefile)
    else:
        tree = ET.parse(sys.stdin)
except IOError:
    print("Nejde otevrit vstupni soubor") #DEBUG
    exit(11)
root = tree.getroot()

try:
    language = root.attrib['language'] #kontrola zda je atribut language a nasledne že je Ippcode
except:
    exit(32)

if (language !='IPPcode21'):
    print("Chybna hlavička\n") #DEBUG
    exit(32)

for child in root:
    #print("atribut", child.attrib,"tag",child.tag) 
    opcode=(child.attrib['opcode'])
    
    if (opcode=="WRITE"):
        for arg in child:
            argtype=arg.attrib['type']
            CheckTypes(argtype,arg.text)
            print(arg.text) #Samotný výpis WRITU == NEMAZAT!
    elif (opcode=="DEFVAR"):
        for arg in child:
            argtype=arg.attrib['type']
            CheckTypes(argtype,arg.text)
        try:
            variables.index(arg.text)
        except ValueError:  #hodnota nebyla definovana => error a pridame
            variables.append(arg.text)
        else:
            print("Definice proměnne podruhé!")#DEBUG
            exit(52)
    elif (opcode=="MOVE"):
        if arg not in child:
            argtype=arg.attrib['type']
            print(argtype)
        if arg not in child:
            argtype=arg.attrib['type']
            print(argtype)
        
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
    





print("OK")
exit(0)
