import sys
import re


class Parse_symb:
    def __init__(self,argument : str = None):
        self.argument = argument
    
    # Method to separate the symbol into its type and value
    def separete_symbol(self):
        # Check if the argument starts with valid symbol types
        if not self.argument.startswith(('int@', 'bool@', 'string@', 'nil@')):
            sys.exit(23) 
            
        # Split the symbol at the '@' delimiter
        separate_symb = self.argument.split('@', 1)
        
        # Check if the symbol was successfully split into two parts
        if len(separate_symb) != 2:
            sys.exit(23)
        return separate_symb
    
    # Method to parse the symbol based on its type
    def parse_symb(self):
        separate_symb = self.separete_symbol()
        if separate_symb[0] == 'bool':
            # Check if the value is 'true' or 'false'
            if separate_symb[1] == 'true' or separate_symb[1] == 'false':
                return separate_symb
            pass
        elif separate_symb[0] == 'nil':
            if separate_symb[1] == 'nil':
                return separate_symb 
            else:
                sys.exit(23) 
            pass
        elif separate_symb[0] == 'int':
            # Try parsing the value as an integer
            try:
                int_value_str = separate_symb[1]
                if int_value_str.startswith("-"):
                    int_value_str = int_value_str[1:]
                    if int_value_str.startswith("0x"):
                        int_value = -int(int_value_str[2:], 16)
                    elif int_value_str.startswith("0o"):
                        int_value = -int(int_value_str[2:], 8)
                    else:
                        int_value = -int(int_value_str)
                else:
                    if int_value_str.startswith("0x"):
                        int_value = int(int_value_str[2:], 16)
                    elif int_value_str.startswith("0o"):
                        int_value = int(int_value_str[2:], 8)
                    else:
                        int_value = int(int_value_str)
                # Check if the parsed integer value is within the valid range
                if -2**63 <= int_value <= (2**63)-1:
                    return separate_symb
            except ValueError:
                sys.exit(23)
                pass
        elif separate_symb[0] == 'string':
            # Check if the string value matches the valid format
            if re.match(r'^(?:[^\\]|\\(?:(?:[0-9]{3})|[nrtbfav\\\'"]))*$', separate_symb[1]):
                if '<' in separate_symb[1] or '>' in separate_symb[1]:
                    separate_symb[1] = separate_symb[1].replace('<', '&lt;').replace('>', '&gt;')
                else:
                    return separate_symb
                return separate_symb
            else:
                sys.exit(23)
        else:
            sys.exit(23)
        
        
    
#Class line
class Line:
    def __init__(self, instruction_name: str, argument1: str = None, argument2: str = None, argument3: str = None):
        self.instruction_name = instruction_name
        self.argument1 = argument1
        self.argument2 = argument2
        self.argument3 = argument3
    # Instruction
    def process_instruction(self):
        global xml_out
        global order
        if self.argument1 is None and self.argument2 is None and self.argument3 is None:
        # Create XML structure
            xml_out += f'\t<instruction order="{order}" opcode="{self.instruction_name.upper()}">\n'
            xml_out += '\t</instruction>\n'
            order += 1
        else:
            sys.exit(23)


    # Instruction + var
    def process_vararg(self):
        global xml_out
        global order
        if self.argument1 is not None and self.argument2 is None and self.argument3 is None:
            # Checking var argument
            if re.match(r"(LF|GF|TF)@[a-zA-Z_$&%*\-?!][a-zA-Z_$%&*\-?!0-9]*", self.argument1):
                xml_out += f'\t<instruction order="{order}" opcode="{self.instruction_name.upper()}">\n'
                xml_out += f'\t\t<arg1 type="var">{self.argument1}</arg1>\n'
                xml_out += '\t</instruction>\n'
                order += 1
            else:
                sys.exit(23)
        else:
            sys.exit(23)
    
    # Instruction + label
    def process_labelarg(self):
        global xml_out
        global order 
        if self.argument1 is not None and self.argument2 is None and self.argument3 is None:
            # Checking label argument
            if re.match(r'^[A-Za-z_\-&%!*?$][A-Za-z0-9_\-&%!*?$]*$', self.argument1):
                xml_out += f'\t<instruction order="{order}" opcode="{self.instruction_name.upper()}">\n'
                xml_out += f'\t\t<arg1 type="label">{self.argument1}</arg1>\n'
                xml_out += '\t</instruction>\n'
                order += 1
            else:
                sys.exit(23)
        else:
            sys.exit(23)
    
        
    # Instruction + symb
    def process_symbarg(self):
        global xml_out
        global order
        if self.argument1 is not None and self.argument2 is None and self.argument3 is None:
            # Checking symb argument
            if re.match(r"(LF|GF|TF)@[a-zA-Z_$&%*\-?!][a-zA-Z_$%&*\-?!0-9]*", self.argument1):
                xml_out += f'\t<instruction order="{order}" opcode="{self.instruction_name.upper()}">\n'
                xml_out += f'\t\t<arg1 type="var">{self.argument1}</arg1>\n'
                xml_out += '\t</instruction>\n'
                order += 1
            else:
                parser = Parse_symb(self.argument1)
                parsing_symb = parser.parse_symb()
                xml_out += f'\t<instruction order="{order}" opcode="{self.instruction_name.upper()}">\n'
                xml_out += f'\t\t<arg1 type="{parsing_symb[0]}">{parsing_symb[1]}</arg1>\n'
                xml_out += '\t</instruction>\n'
                order += 1
        else:
            sys.exit(23)
        
    # Instruction + var + symb
    def process_var_symb_arg(self):
        global xml_out
        global order
        if self.argument1 is not None and self.argument2 is not None and self.argument3 is None:
            # Checking var argument
            if re.match(r"(LF|GF|TF)@[a-zA-Z_$&%*\-?!][a-zA-Z_$%&*\-?!0-9]*", self.argument1):
                xml_out += f'\t<instruction order="{order}" opcode="{self.instruction_name.upper()}">\n'
                xml_out += f'\t\t<arg1 type="var">{self.argument1}</arg1>\n'
                order += 1
            else:
                sys.exit(23)
            
            # Checking symb argument
            if re.match(r"(LF|GF|TF)@[a-zA-Z_$&%*\-?!][a-zA-Z_$%&*\-?!0-9]*", self.argument2):
                xml_out += f'\t\t<arg2 type="var">{self.argument2}</arg2>\n'
            else:
                parser = Parse_symb(self.argument2)
                parsing_symb = parser.parse_symb()
                xml_out += f'\t\t<arg2 type="{parsing_symb[0]}">{parsing_symb[1]}</arg2>\n'
                
            xml_out += '\t</instruction>\n'
        else:
            sys.exit(23)
            
    # Instruction + var + type
    def process_var_type_arg(self): 
        global xml_out
        global order   
        if self.argument1 is not None and self.argument2 is not None and self.argument3 is None:
            # Checking var argument
            if re.match(r"(LF|GF|TF)@[a-zA-Z_$&%*\-?!][a-zA-Z_$%&*\-?!0-9]*", self.argument1):
                xml_out += f'\t<instruction order="{order}" opcode="{self.instruction_name.upper()}">\n'
                xml_out += f'\t\t<arg1 type="var">{self.argument1}</arg1>\n'
                order += 1
            else:
                sys.exit(23)
            # Checking type argument
            if self.argument2 == 'int' or self.argument2 == 'string' or self.argument2 == 'bool':
                xml_out += f'\t\t<arg2 type="type">{self.argument2}</arg2>\n'
                xml_out += '\t</instruction>\n'
            else:
                sys.exit(23)
        else:
            sys.exit(23)
            
        
    # Instruction + var + symb1 + symb2
    def process_var_symb_symb_arg(self): 
        global xml_out
        global order    
        if self.argument1 is not None and self.argument2 is not None and self.argument3 is not None:
            # Checking var argument
            if re.match(r"(LF|GF|TF)@[a-zA-Z_$&%*\-?!][a-zA-Z_$%&*\-?!0-9]*", self.argument1):
                xml_out += f'\t<instruction order="{order}" opcode="{self.instruction_name.upper()}">\n'
                xml_out += f'\t\t<arg1 type="var">{self.argument1}</arg1>\n'
                order += 1
            else:
                sys.exit(23)
             
            # Checking symb argument   
            if re.match(r"(LF|GF|TF)@[a-zA-Z_$&%*\-?!][a-zA-Z_$%&*\-?!0-9]*", self.argument2):
                xml_out += f'\t\t<arg2 type="var">{self.argument2}</arg2>\n'
            else:
                parser = Parse_symb(self.argument2)
                parsing_symb = parser.parse_symb()
                xml_out += f'\t\t<arg2 type="{parsing_symb[0]}">{parsing_symb[1]}</arg2>\n'
                
            # Checking symb argument    
            if re.match(r"(LF|GF|TF)@[a-zA-Z_$&%*\-?!][a-zA-Z_$%&*\-?!0-9]*", self.argument3):
                xml_out += f'\t\t<arg3 type="var">{self.argument3}</arg3>\n'
            else:
                parser = Parse_symb(self.argument3)
                parsing_symb = parser.parse_symb()
                xml_out += f'\t\t<arg3 type="{parsing_symb[0]}">{parsing_symb[1]}</arg3>\n'    
            

            xml_out += '\t</instruction>\n'
        else:
            sys.exit(23)
    
    
    # Instruction + label + symb1 + symb2        
    def process_label_symb_symb_arg(self):
        global xml_out
        global order
        if self.argument1 is not None and self.argument2 is not None and self.argument3 is not None:
            # Checking label argument
            if re.match(r'^[A-Za-z_\-&%!*?$][A-Za-z0-9_\-&%!*?$]*$', self.argument1):
                xml_out += f'\t<instruction order="{order}" opcode="{self.instruction_name.upper()}">\n'
                xml_out += f'\t\t<arg1 type="label">{self.argument1}</arg1>\n'
                order += 1
            else:
                sys.exit(23)
            
            # Checking symb argument
            if re.match(r"(LF|GF|TF)@[a-zA-Z_$&%*\-?!][a-zA-Z_$%&*\-?!0-9]*", self.argument2):
                xml_out += f'\t\t<arg2 type="var">{self.argument2}</arg2>\n'
            else:
                parser = Parse_symb(self.argument2)
                parsing_symb = parser.parse_symb()
                xml_out += f'\t\t<arg2 type="{parsing_symb[0]}">{parsing_symb[1]}</arg2>\n'
            # Checking symb argument   
            if re.match(r"(LF|GF|TF)@[a-zA-Z_$&%*\-?!][a-zA-Z_$%&*\-?!0-9]*", self.argument3):
                xml_out += f'\t\t<arg3 type="var">{self.argument3}</arg3>\n'
            else:
                parser = Parse_symb(self.argument3)
                parsing_symb = parser.parse_symb()
                xml_out += f'\t\t<arg3 type="{parsing_symb[0]}">{parsing_symb[1]}</arg3>\n'      
            
            xml_out += '\t</instruction>\n'
        else:
            sys.exit(23)
        
 # Function to process input data
def process_input(input_data):
    global xml_out
    global order
    order = 1 
    comment_pattern = rb'#.*?$'  # Use byte string pattern
    # Replace comments with an empty string
    cleaned_data = re.sub(comment_pattern, b'', input_data, flags=re.MULTILINE)
    
    
    input_data_str = cleaned_data.decode('utf-8')
    # Remove comments and empty lines, and split by lines
    lines = filter(None, (re.sub(r'#.*$', '', line).strip() for line in input_data_str.split('\n')))
    
    # Check if the first line contains ".IPPcode24"
    header = next(lines, None)
    if header != ".IPPcode24":
        sys.exit(21)
    
        
    # Process instructions
    instructions = []
    for line in lines:
        #too many headers test
        if line == ".IPPcode24":
            sys.exit(23)
            
        instruction_elements = line.split(maxsplit=3)
        if len(instruction_elements) == 1:
            instructions.append(Line(instruction_elements[0]))
        elif len(instruction_elements) == 2:
            instructions.append(Line(instruction_elements[0], instruction_elements[1]))
        elif len(instruction_elements) == 3:
            instructions.append(Line(instruction_elements[0], instruction_elements[1], instruction_elements[2]))
        elif len(instruction_elements) == 4:
            instructions.append(Line(instruction_elements[0], instruction_elements[1], instruction_elements[2], instruction_elements[3]))
        else:
            sys.exit(23)      
    
    return instructions

# Function to parse instructions
def parse(instructions):
    global xml_out
    
    # Instruction types
    noargs_instructionNames =             {"CREATEFRAME", "PUSHFRAME", "POPFRAME", "RETURN", "BREAK"}
    vararg_instructionNames =             {"DEFVAR", "POPS"}
    labelarg_instructionNames =           {"CALL", "LABEL", "JUMP"}
    symbarg_instructionNames =            {"PUSHS", "WRITE", "EXIT", "DPRINT"}
    VarSymbArg_instructionNames =         {"MOVE", "INT2CHAR", "STRLEN", "TYPE","NOT"}
    VarTypeArg_instructionNames =         {"READ"}
    VarSymb1Symb2Arg_instructionNames =   {"ADD", "SUB", "MUL", "IDIV","LT", "GT", "EQ", "AND", "OR", "STRI2INT", "CONCAT", "GETCHAR", "SETCHAR"}
    LabelSymb1Symb2Arg_instructionNames = {"JUMPIFEQ", "JUMPIFNEQ"}
    # Create XML header
    xml_out = '<?xml version="1.0" encoding="UTF-8"?>\n<program language="IPPcode24">\n'
    
    
    instruction_sets = [
    noargs_instructionNames,
    vararg_instructionNames,
    labelarg_instructionNames,
    symbarg_instructionNames,
    VarSymbArg_instructionNames,
    VarTypeArg_instructionNames,
    VarSymb1Symb2Arg_instructionNames,
    LabelSymb1Symb2Arg_instructionNames
    ]
    
    
    
# Combine all instruction names into a single set for case-insensitive matching
    all_instruction_names = set()
    for instruction_set in instruction_sets:
        all_instruction_names.update(instruction_set)

    for instruction in instructions:
        for instruction_set in instruction_sets:
            if instruction.instruction_name.upper() in (name.upper() for name in instruction_set):
                # Process instruction based on its type
                if instruction.instruction_name.upper() in (name.upper() for name in noargs_instructionNames):
                    instruction.process_instruction()
                elif instruction.instruction_name.upper() in (name.upper() for name in vararg_instructionNames):
                    instruction.process_vararg()
                elif instruction.instruction_name.upper() in (name.upper() for name in labelarg_instructionNames):
                    instruction.process_labelarg()
                elif instruction.instruction_name.upper() in (name.upper() for name in symbarg_instructionNames):
                    instruction.process_symbarg()
                elif instruction.instruction_name.upper() in (name.upper() for name in VarSymbArg_instructionNames):
                    instruction.process_var_symb_arg()
                elif instruction.instruction_name.upper() in (name.upper() for name in VarTypeArg_instructionNames):
                    instruction.process_var_type_arg()
                elif instruction.instruction_name.upper() in (name.upper() for name in VarSymb1Symb2Arg_instructionNames):
                    instruction.process_var_symb_symb_arg()
                elif instruction.instruction_name.upper() in (name.upper() for name in LabelSymb1Symb2Arg_instructionNames):
                    instruction.process_label_symb_symb_arg()
                else:
                    sys.exit(23) 
    
    
    xml_out += '</program>\n'
    print(xml_out)       
    sys.exit(0)             


def main():
    # Check number of arguments
    if len(sys.argv) > 2:
        sys.exit(10)

    # If only one argument is provided
    if len(sys.argv) == 2:
        # Check if the argument is '--help'
        if sys.argv[1] == "--help":
            print("You can run program in this way :\n")
            print("\tpython3 parse.py < [input_file.src] - if you want to get output to stdout\n")
            print("\tpython3 parse.py < [input_file.src] > [output_file.out] - if you want to get output to xml file\n")
            print("[input_file.src] - path to input file, [output_file.out] - path to output file")
            sys.exit(0)
        else:
            # If an argument is provided without --help, it's an error
           sys.exit(10)

    # If no arguments are provided, check if input is redirected
    if not sys.stdin.isatty():
        input_data = sys.stdin.buffer.read()
        # Process input data
        Instructions = process_input(input_data)
        parse(Instructions)
    else:
        # If no arguments and no redirection, it's an error
        sys.exit(10)


if __name__ == "__main__":
    main()
