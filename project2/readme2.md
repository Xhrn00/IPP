# Implementation Documentation for Task 2 in IPP 2023/2024

Name: Hryn Yaroslav  
Login: xhryny00

# Table of Contents
1. [Introduction](#introduction)
2. [User Guide](#2user-guide)
   - 2.1 [Usage](#usage)
3. [UML Diagram](#uml-diagram)
4. [System Overview](#system-overview)
5. [Implementation Details](#implementation-details)
   - 5.1 [Interpreter Class](#interpreter-class)
   - 5.2 [XML_Parser Class](#xml_parser-class)
   - 5.3 [InstructionParser Class](#instructionparser-class)
   - 5.4 [Stack Class](#stack-class)
   - 5.5 [Label Class](#label-class)
   - 5.6 [Frame Class](#frame-class)
6. [Conclusion](#conclusion)

## Introduction

This document outlines the implementation details for the second task assigned in the IPP course for the academic year 2023/2024. The task involves developing various functionalities for managing a software interpreter designed in PHP.

## User Guide

This guide provides detailed instructions on how to use the interpreter developed for task 2 of the IPP course in the academic year 2023/2024. The interpreter is designed to process and execute instructions from an XML formatted source file and handle user inputs effectively.

### Usage

Run the interpreter with the following command:

```bash
php interpret.php [options...]
Options
--help : Displays this help message and exits.
--source=<file> : Specifies the source code file in XML format.
--input=<file> : Specifies the file for user inputs.
Note: At least one of the options --source and --input must be specified. If one is omitted, STDIN is used as the default for that option.

Return Codes
0-9: Correct execution with varying outcomes based on operation specifics.
10: Invalid parameters.
11: Input file error.
12: Output file error.
31: Invalid source XML format.
32: Invalid source structure.
52: Semantic error.
53: Runtime error - incorrect operand types.
54: Runtime error - non-existent variable.
55: Runtime error - non-existent frame.
56: Runtime error - missing value.
57: Runtime error - incorrect operand value.
58: Runtime error - improper string operation.
59: Integration error.
99: Internal error.
```
## UML Diagram

![Class Diagram](## UML Diagram

![Class Diagram](https://www.mermaidchart.com/raw/1a0095ac-c5fc-449e-83c8-7f13e3118809?theme=dark&version=v0.1&format=svg))

## System Overview

The system is structured into several classes  that interact to parse, execute, and manage different operations. The main components include:

- **Interpreter**: Orchestrates the reading, parsing, and executing phases.
- **XML Parser**: Handles parsing of XML formatted instructions.
- **Instruction Parser**: Processes and executes the instructions(creates 2 times Stack object for label and for instructions pops,pushs).
- **Stack**: Using for pushing and poping variables.
- **Label**: Update position based on label.
- **Frame**: Using for working with memory frames.

## Implementation Details

### Interpreter Class
- Overview: The Interpreter class is the core of the system. It orchestrates the entire process of reading the XML source, parsing the instructions, and managing the execution flow based on parsed data.
- Main Methods:
execute(): This method controls the flow of the program execution. It integrates all components (XML parser, instruction parser).
### XML_Parser Class
- Overview: The XML_Parser class is responsible for parsing XML-formatted instruction files into a structured format that can be interpreted by the system.
- Main Methods:
parseXMLString(xmlString: String): Converts XML data into a usable structure by the rest of the application, checking for correctness and compliance with expected formats.
checking_header_XML(dom: DOMDocument): Validates the structure and headers of the XML document to ensure it meets the specific requirements.
- Benefits: Ensures data integrity and error handling at the initial stage of input processing. It provides a robust foundation for further processing steps, reducing errors in downstream components.
### InstructionParser Class
- Overview: Processes and executes the instructions that have been parsed from the XML. It interacts with the Stack, Label, and Frame classes to manipulate data and execute the program logic.
- Main Methods:
parseInstructions(): Iterates over the set of parsed instructions and executes them according to their operational logic. checkArguments(instruction, expectedDataTypes): These method ensure that the operations are performed on valid data and that the instructions adhere to expected formats.
- Benefits: Centralizes instruction management, allowing for easy additions or modifications to the instruction set. Provides a clear separation between the parsing of instructions and their execution, which enhances testability and maintenance.
### Stack Class
- Overview: Implements a generic stack data structure to support various operations that require last-in-first-out (LIFO) data handling.
- Main Methods:
push(value: mixed): Adds an item to the top of the stack.
pop(): Removes and returns the top item from the stack.

### Label Class
- Overview: Manages jumping within the instruction flow based on labels defined in the program.
- Main Methods:
jump(name: mixed): Modifies the instruction pointer based on the label provided, allowing the interpreter to change the flow of execution.
### Frame Class
- Overview: Manages scopes and variable storage in different frames (global, local, temporary), crucial for function calls and modular programming.
- Main Methods:
setValue(frame: String, name: String, value: mixed, type: String): Sets a variable in a specified frame.
getValue(name: String, frame: String): Retrieves a variable's value from a specified frame.
- Benefits: Supports scoping, which is essential for creating isolated execution contexts and managing variable lifetimes efficiently. This isolation helps prevent side effects between different parts of the program.

## Conclusion

These classes collectively provide a robust framework for a script interpreter capable of handling complex programming tasks. By maintaining clear separation of concerns, the architecture ensures that each component can be independently developed, tested, and maintained(speaking in the context of OOP).
