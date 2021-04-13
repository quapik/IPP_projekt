#Vojtěch Šíma, xsimav01, IPP FIT VUT 2021
import sys
import xml.etree.ElementTree as ET
import re
BylSource=False
BylInput=False
TFnames=[]
TFValues=[]
variables=[]
variablesValues=[]
LFStackValues=[]
LFStackNames=[]
all=[]
labels=[]
numbers=[]
stackValues = []
stackVolani = []
SymbolTypeList=["int","var","nil","string","bool"]
BylCreatframe=False

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
        inputfile=sys.argv[1][8:len(sys.argv[1])]
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
        inputfile=sys.argv[2][8:len(sys.argv[2])]
        BylInput=True
    else:
        print("errror - spatny argument") #DEBUG
        exit(10) #TODO

    if BylInput is False and BylSource is False:
        print("nebyl zadan ani input ani source") #DEBUG
        exit(10)
#------------------------------------------------------------------------------------------------------------------------------¨
def CheckTypes(argtype,data):
    if argtype=="int":
        if not (re.search('^[-+]?[0-9]+$',data)):
            print("Chyba - type int, ale text not int")#DEBUG
            exit(32)
    elif argtype=="string":
       # if not(re.search(r'\S*',data)): #TODO!
        #    exit(32)
        pass
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
        if child.text[0:2]=="GF":
            try:
                index=variables.index(child.text)
            except ValueError:  #hodnota nebyla definovana => error a pridame
                print("promenna",child.text,"nebyla definovana" ) #DEBUG
                exit(54)
            else:
                if variablesValues[index][0] == datovytyp:
                    return variablesValues[index][1]
                elif variablesValues[index][0] is None:
                    exit(56) 
                else:
                    print("Chyba")
                    exit(53)
        elif child.text[0:2]=="TF":
            if BylCreatframe==True:     
                try:
                    index=TFnames.index(child.text[3:len(child.text)])
                except:
                    print("error TF VARCHECK")
                    exit(54)
                else:
                    if TFValues[index][0]== datovytyp:
                        return TFValues[index][1]
                    elif TFValues[index][0] is None:
                        exit(56) 
                    else:
                        exit(53)
            else:
                exit(55)
        else:
            try:
                names_tmp=LFStackNames.pop()
                values_tmp=LFStackValues.pop()
            except IndexError:
                print("Chyba POPFRAME-prazdny zasobnik")
                exit(55)
            else:
                try:
                    index=names_tmp.index(child.text[3:len(child.text)])
                except:
                    exit(54)
                else:
                    LFStackNames.append(list(names_tmp))
                    LFStackValues.append(list(values_tmp))
                    if values_tmp[index][0]== datovytyp:
                        return values_tmp[index][1]
                    elif values_tmp[index][0] is None:
                        exit(56)
                    else:
                        exit(53)
    else:
        print("Špatné typy operandů")
        exit(53)
def VarCheckDefinovanaReturnIndex(variable):
    #global BylCreatframe
    if variable[0:2]=="GF":
        try:
            index=variables.index(variable)
        except ValueError:  #hodnota nebyla definovana => error a pridame
            print("promenna",variable,"nebyla definovana" ) #DEBUG
            exit(54)
        else:
            return int(index)
    elif variable[0:2]=="TF":
        if BylCreatframe==True:     
            try:
                index=TFnames.index(variable[3:len(variable)])
            except:
                print("error TF VARCHECK")
                exit(54)
            else:
                return list(["TF",TFValues[index][0],TFValues[index][1]])
        else:
            exit(55)
    else:
        try:
            names_tmp=LFStackNames.pop()
            values_tmp=LFStackValues.pop()
        except IndexError:
            print("Chyba POPFRAME-prazdny zasobnik")
            exit(55)
        else:
            try:
                index=names_tmp.index(variable[3:len(variable)])
            except:
                exit(54)
            else:
                LFStackNames.append(list(names_tmp))
                LFStackValues.append(list(values_tmp))
                return list(["LF", values_tmp[index][0], values_tmp[index][1]])
def AddValueLFTF(variable,datatype,hodnota):
    if variable[0:2]=="TF":
        TFValues[TFnames.index((variable[3:len(variable)]))]=[datatype,hodnota]
    else:
        names_tmp=LFStackNames.pop()
        values_tmp=LFStackValues.pop()
        index=names_tmp.index(variable[3:len(variable)])
        values_tmp[index]=[datatype,hodnota]
        LFStackNames.append(list(names_tmp))
        LFStackValues.append(list(values_tmp))



               
def stringreplace(text): #funkce na nahrazeni escape sekvenci pro string - vyhledani sekvece, vyjmuti cisla a nahrazeni 
    rg=re.compile(r"(\\\d{3})") 
    for i in re.findall(rg,text):
        code=i[1:4]
        nahrada=chr(int(code))
        text=text.replace(i,nahrada)
    return text

def prochazej(root):
    global BylCreatframe
    global TFnames
    global TFValues
    global LFStackNames
    global LFStackValues
    for child in root:
        opcode=(child.attrib['opcode'])
        opcode=opcode.upper()
        if (opcode=="WRITE"):
            CheckPocetArgumentuInstukce(child,1)
            if child[0].attrib['type'] == "var": 
                index=VarCheckDefinovanaReturnIndex(child[0].text)
                if isinstance(index,int):
                    if (variablesValues[index][0] is None):
                        exit(56)
                    if variablesValues[index][0]=="nil":
                        print("",end='')
                    elif variablesValues[index][0]=="string":
                        text=stringreplace(variablesValues[index][1])
                        print(text,end='')#Samotný výpis WRITU == NEMAZAT! 
                    else:
                        print(variablesValues[index][1],end='')#Samotný výpis WRITU == NEMAZAT! 
                else:
                    #print(child[0].text)
                    if (index[1] is None):
                        exit(56)
                    if index[1]=="nil":
                        print("",end='')
                    elif index[1]=="string":
                        text=stringreplace(index[2])
                        print(text,end='')
                    else:
                        print(index[2],end='')
            else:
                if child[0].attrib['type']=="nil":
                    print("",end='')
                elif child[0].attrib['type']=="string":
                    text=stringreplace(child[0].text)
                    print(text,end='')#Samotný výpis WRITU == NEMAZAT! 
                else:
                    print(child[0].text,end='') #Samotný výpis WRITU == NEMAZAT!
        elif (opcode=="DEFVAR"):
            CheckPocetArgumentuInstukce(child,1)
            if child[0].text[0:2]=="TF":
                if BylCreatframe==False:
                    exit(55) #defvar pro nedefinovany TF
                try:
                    TFnames.index(child[0].text[3:len(child[0].text)])
                except:
                    TFnames.append(child[0].text[3:len(child[0].text)])
                    TFValues.append([None,None])
                else:
                    exit(52) #redefinice TF variable
            elif child[0].text[0:2]=="LF":
                try:
                    names_tmp=LFStackNames.pop()
                    values_tmp=LFStackValues.pop()
                except IndexError:
                    print("Chyba POPFRAME-prazdny zasobnik")
                    exit(55)
                else:
                    try:
                        names_tmp.index(child[0].text[3:len(child[0].text)])
                    except:
                        names_tmp.append(child[0].text[3:len(child[0].text)])
                        values_tmp.append([None,None])
                        LFStackNames.append(list(names_tmp))
                        LFStackValues.append(list(values_tmp))
                    else:
                        exit(52)
                         
            else:
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
            if isinstance(index,int):
                if  child[1].attrib['type']=="var":
                    index2=VarCheckDefinovanaReturnIndex(child[1].text)
                    if isinstance(index2,int):
                        if variablesValues[index2][0]==None:
                            exit(56)
                        variablesValues[index][0] = variablesValues[index2][0]
                        variablesValues[index][1] = variablesValues[index2][1]
                    else:
                        variablesValues[index][0] = index2[1]
                        variablesValues[index][1] = index2[2]
                else:
                    variablesValues[index][0] = child[1].attrib['type']
                    variablesValues[index][1] = child[1].text
            else: #vkládáme do LF nebo TF
                if  child[1].attrib['type']=="var":
                    index2=VarCheckDefinovanaReturnIndex(child[1].text)
                    if index[0]=="TF":
                            if isinstance(index2,int):
                                TFValues[TFnames.index((child[0].text[3:len(child[0].text)]))]=[variablesValues[index2][0],variablesValues[index2][1]]
                            else:
                                TFValues[TFnames.index((child[0].text[3:len(child[0].text)]))]=[index2[1],index[2]]
                        
                    else:
                        try:
                            names_tmp=LFStackNames.pop()
                            values_tmp=LFStackValues.pop()
                        except IndexError:
                            print("Chyba POPFRAME-prazdny zasobnik")
                            exit(55)

                        try:
                            indexLF=names_tmp.index(child[0].text[3:len(child[0].text)])
                        except IndexError:
                            exit(54)

                        if isinstance(index2,int):
                           values_tmp[indexLF]=[variablesValues[index2][0],variablesValues[index2][1]]
                        else:
                            values_tmp[indexLF]==[index2[1],index[2]]
                        LFStackNames.append(names_tmp)
                        LFStackValues.append(values_tmp)

                else: #pokud je vkladana konstatnta
                    if index[0]=="TF":
                        TFValues[TFnames.index((child[0].text[3:len(child[0].text)]))]=[child[1].attrib['type'],child[1].text]
                    else:
                        try:
                            names_tmp=LFStackNames.pop()
                            values_tmp=LFStackValues.pop()
                        except IndexError:
                            print("Chyba POPFRAME-prazdny zasobnik")
                            exit(55)
                        try:
                            indexLF=names_tmp.index(child[0].text[3:len(child[0].text)])
                        except IndexError:
                            exit(54)
                        values_tmp[indexLF]=[child[1].attrib['type'],child[1].text]
                        LFStackNames.append(names_tmp)
                        LFStackValues.append(values_tmp)
        elif opcode=="READ":
            CheckPocetArgumentuInstukce(child,2)
            if child[0].attrib['type']!="var" or child[1].attrib['type']!="type":
                 exit(52)
            index=VarCheckDefinovanaReturnIndex(child[0].text)
            datovytyp=child[1].text #v textove casti je datovy typ co se nacita (int,bool,string)
            hodnota=inputfile.readline() #nacteni z input souboru
            hodnota = hodnota.replace('\n','')
            if hodnota=="":
                    chyba = True
            chyba=False
            if datovytyp=="int":
                try:
                    hodnota=int(hodnota)
                except ValueError: #pokud tam neni hodnota co nejde prevest na int => bude nil@nil
                    chyba=True
                else:
                    if isinstance(index,int):
                        variablesValues[index][0]=datovytyp
                        variablesValues[index][1]=hodnota
                    else:
                        AddValueLFTF(child[0].text,datovytyp,hodnota)
            elif datovytyp=="string":
                if isinstance(hodnota, str) and hodnota != "":
                    if isinstance(index,int):
                        variablesValues[index][0]=datovytyp
                        variablesValues[index][1]=hodnota
                    else:
                        AddValueLFTF(child[0].text,datovytyp,hodnota)
                else:
                    chyba=True
            elif datovytyp=="bool": #true na true, vse ostatni na false
                if isinstance(index,int):
                    variablesValues[index][0]=datovytyp
                    if hodnota.lower() == "true":
                        variablesValues[index][1]="true"
                    else:
                        variablesValues[index][1]="false"
                else:
                    if hodnota.lower() == "true":
                        AddValueLFTF(child[0].text,datovytyp,"true")
                    else:
                        AddValueLFTF(child[0].text,datovytyp,"false")
            if (chyba is True):
                if isinstance(index,int):
                    variablesValues[index][0]="nil"
                    variablesValues[index][1]="nil"
                else:
                    AddValueLFTF(child[0].text,"nil","nil")

        elif (opcode=="CREATEFRAME"):
            BylCreatframe=True
            TFnames.clear()
            TFValues.clear()

        elif (opcode=="PUSHFRAME"):
            if BylCreatframe == False:
                print("Pokus o přístup k nedefinovanému rámci") #DEBUG
                exit(55)
            LFStackNames.append(list(TFnames))
            LFStackValues.append(list(TFValues))
            TFnames.clear()
            TFValues.clear()
            BylCreatframe=False
        elif (opcode=="POPFRAME"):
            try:
                TFnames=LFStackNames.pop()
                TFValues=LFStackValues.pop()
            except IndexError:
                print("Chyba POPFRAME-prazdny zasobnik")
                exit(55)
            BylCreatframe=True
        elif (opcode=="ADD" or opcode=="SUB" or opcode=="MUL" or opcode=="IDIV"):
            CheckPocetArgumentuInstukce(child,3)
            if child[0].attrib['type'] == "var":
                index=VarCheckDefinovanaReturnIndex(child[0].text)
                variablesValues[index][0]="int"
                try:
                    cislo1=int(VarTypeCheckReturn(child[1],"int"))
                    cislo2=int(VarTypeCheckReturn(child[2],"int"))         
                except ValueError:
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
                if isinstance(index,int):
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
            #try:
            #    str1=VarTypeCheckReturn(child[1],"string")
            #except:
            #    exit(53)
            
            str1=VarTypeCheckReturn(child[1],"string")
            if str1 is None:
                str1=""

            
            if (child[0].attrib['type'] == "var"):
                index=VarCheckDefinovanaReturnIndex(child[0].text)
                if isinstance(index,int):
                    variablesValues[index][0]="int"
                    variablesValues[index][1]=len(str1)
                else:
                    AddValueLFTF(index[0],"int",len(str1))

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
                    if isinstance(index,int):
                        variablesValues[index][0]="string"
                        variablesValues[index][1]=child[1].attrib['type']
                    else:
                        AddValueLFTF(child[0].text,"string",child[1].attrib['type'])
                else:
                    index2=VarCheckDefinovanaReturnIndex(child[1].text)
                    if isinstance(index,int):
                        if isinstance(index2,int):  
                            vartyp=variablesValues[index2][0]
                        else:
                            vartyp=index2[1]

                        if vartyp is None:
                            variablesValues[index][1]=""
                            variablesValues[index][0]="string"
                        else:
                            variablesValues[index][1]=vartyp
                            variablesValues[index][0]="string"
                    else:
                        if isinstance(index2,int):  
                            vartyp=variablesValues[index2][0]
                        else:
                            vartyp=index2[1]

                        if vartyp is None:
                            AddValueLFTF(child[0].text,"string","")
                        else:
                            AddValueLFTF(child[0].text,"string",vartyp)


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
                if isinstance(index,int):
                    if variablesValues[index][0] is None:
                        exit(56)
                    stackValues.append([variablesValues[index][0],variablesValues[index][1]])
                else:
                    if index[1] is None:
                        exit(56)
                    else:
                        stackValues.append(index[1],index[2])


           
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

       
#-----------------------------------------------------------------------------------------------------
try:
    if(BylInput==True): #pokud je input file jako parametr, jinak stdin
        inputfile = open(inputfile, 'r')
    else:
        inputfile=sys.stdin
except:
    print("Nejde otevrit vstupni soubors") #DEBUG
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
    try:
        root[:] = sorted(root, key=lambda child: int(child.get('order'))) #seřadit child podle čísla orderu
        for arg in child:
            child[:] = sorted(child, key=lambda arg: str(arg.tag)) #seřadit arg podle abecedy
    except:
        exit(32)

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
    opcode=child.attrib['opcode']
    opcode=opcode.upper()
    if(opcode=="LABEL"): #pokud label, kontrola zda už nebyl a pak ulozeni do labelu
        try:
            labels.index(child[0].text)
        except:
            pass
        else:
            print("Label podruhe") #DEBUG
            exit(52)
        labels.append(child[0].text)
      
    if(opcode=="JUMP" or opcode=="JUMPIFEQ" or opcode== "JUMPIFNEQ"  or opcode== "CALL"):
        all.append("jump_"+child[0].text)
    elif opcode=="BREAK":
        all.append("instrukceBREAK")
    elif opcode=="RETURN":
        all.append("instrukceRETURN")
    elif opcode=="CREATEFRAME":
        all.append("instrukceCREATEFRAME")
    elif opcode=="POPFRAME":
        all.append("instrukcePOPFRAME")
    elif opcode=="PUSHFRAME":
        all.append("instrukcePUSHFRAME")
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
            
saveroot=root
prochazej(root)

exit(0)
