#Vojtěch Šíma, xsimav01, IPP FIT VUT 2021
import sys
import xml.etree.ElementTree as ET
BylSource=False
BylInput=False

if (len(sys.argv)>3) or len(sys.argv)==1 :
    print("Špatný počet argumentů")
    exit(21)

if (sys.argv[1]=="--help"):
    print("Vypsat nápovědu")
    exit(0)

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
    exit(1) #TODO

if(sys.argv[2][0:9]=="--source="):
    if(BylSource is True):
        print("Source podruhy")
        exit(10) #chech exit
    sourcefile=sys.argv[2][9:len(sys.argv[1])]
    BylSource=True
    print(sourcefile)

elif(sys.argv[2][0:8]=="--input="):
    if(BylInput is True):
        print("Input podruhy")
        exit(10) #chech exit
    inputfile=sys.argv[2][8:len(sys.argv[1])]
    BylInput=True
    print(inputfile)
else:
    print("errror - spatny argument")
    exit(1) #TODO

if BylInput is False and BylSource is False:
    print("nebyl zadan ani input ani source")
    exit(10)

tree = ET.parse('sourcezadani.txt')
root = tree.getroot()
print("root tag", root.tag,"\nroot atrib", root.attrib)
print(root[1][0].text)
#for child in root:
    #print(child.attrib,child.tag) 

exit(0)
