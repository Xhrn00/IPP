# Implementation Documentation for Task 1. of IPP 2023/2024

## Name and Surname:
Hryn Yaroslav

## Login:
xhryny00

## User guide:
You can run program in this way :
    python3 parse.py < [input_file.src] - if you want to get output to stdout
    python3 parse.py < [input_file.src] > [output_file.out] - if you want to get output to xml file
[input_file.src] - path to input file, [output_file.out] - path to output file

## Introduction
During the implementation of the IPP Part 1 project, I became familiar with the use of object-oriented programming in practice. Particularly with working and using classes in practice.

## Implementation:

### Function main:
This function serves as the entry point of the program. It checks the number and correctness of passed arguments and proceeds to process the input data of the IPP code.

### Function process_input:
This function is used to process the input data of the IPP code. It was chosen to clean the input data from comments and empty lines and then split individual lines into instructions. It also checks whether the first line is correctly formatted and contains the ".IPPcode24" header.

### Function parse:
This function processes individual instructions of the IPP code and generates the corresponding XML output. It was chosen to assign the correct processing of the instruction based on its type and name. The function also ensures that the output XML structure complies with the requirements of the IPP specification.

### Class Line:
This class represents individual lines of instructions in the IPP code. It was chosen for its ability to process various types of arguments and create the corresponding XML structure. The methods of this class process different types of arguments depending on the instruction they represent.

### Class Parse_symb:
This class is designed to parse symbols in the IPP code. It was chosen because it allows separating the type and value of the symbol and verifying their correctness. The separate_symbol method separates the symbol into its type and value. The parse_symb method then analyzes the symbol based on its type and checks its validity according to the rules of the IPP language.

## Conclusion
Using classes allowed me to more easily process and validate instructions with their arguments