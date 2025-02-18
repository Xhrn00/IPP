<?php


namespace IPP\Student;



use IPP\Core\ReturnCode;
use IPP\Core\Interface\InputReader;
use IPP\Core\Interface\OutputWriter;

class InstructionParser {
     /**
     * An array holding instructions, each represented as an associative array.
     * @var array<array<mixed>>
     */
    private array $instructions;
    private InputReader $stdin;
    private OutputWriter $stderr;
    private OutputWriter $stdout;
    private string $opCode;
    public Stack $stack;
    public int $Instrcount = 0;
    public Label $labelll;
    public Stack $labelStack;

    /**
     * Constructs a new instance of InstructionParser.
     *
     * @param array<mixed> $instructions Array of instructions.
     * @param InputReader $stdin Standard input handler.
     * @param OutputWriter $stderr Standard error handler.
     * @param OutputWriter $stdout Standard output handler.
     */
    public function __construct(array $instructions, InputReader $stdin, OutputWriter $stderr, OutputWriter $stdout) {
        $this->instructions = $instructions;
        $this->stdin = $stdin;
        $this->stderr = $stderr;
        $this->stdout = $stdout;
        $this->stack = new Stack();
        $this->labelll = new Label();
        $this->labelStack = new Stack();
    }



    /**
      * Converts the provided value to a specified type.
      *
      * @param mixed $value The value to be converted.
      * @param string $type The type to convert the value to ('bool', 'int', 'string', 'nil').
      * @return array<mixed> Returns an associative array with the converted value and its type.
    */
    private function checkType($value, $type) {
        switch ($type) {
            case 'bool':
                // Explicitly convert string 'true' and 'false' to boolean values
                if ($value === 'true') {
                    $convertedValue = true;
                } elseif ($value === 'false') {
                    $convertedValue = false;
                } else {
                    // If the value is neither 'true' nor 'false', exit with an error
                    exit(ReturnCode::SEMANTIC_ERROR);
                }
                break;
            case 'int':
                $convertedValue = (int)$value; // Convert to integer
                break;
            case 'string':
                $convertedValue = $value; // Convert to string
                break;
            case 'nil':
                $convertedValue = ""; // Set to null for 'nil'
                break;
            default:
                exit(ReturnCode::SEMANTIC_ERROR); // Exit for unsupported type
        }
    
        // Return the converted value and its type as an associative array
        return ['value' => $convertedValue, 'type' => $type];
    }
    

    private function findLabel() : void {
        $i = 1;
        foreach ($this->instructions as $instruction) {
            $this->opCode = strtoupper($instruction['opcode']);
            if ($this->opCode === 'LABEL') {
                $targetArg = $instruction['args'][0];
                if (array_key_exists($targetArg['value'], $this->labelll->label)) {
                    exit(ReturnCode::SEMANTIC_ERROR);
                }
                $this->labelll->label[$targetArg['value']] = $i -1;
                
            }
            $i++;
        }
    }


     /**
      * Checks if the arguments in an instruction match the expected data types.
      * Exits the script if the number of arguments or their types are incorrect.
      *
      * @param array<mixed> $instruction Associative array containing 'args' and 'opcode'.
      * @param array<array<string>> $expectedDataTypes Array of arrays, each containing allowed data types for corresponding argument.
      * @return void
      */
    private function checkArguments(array $instruction, array $expectedDataTypes): void {
        if (!isset($instruction['args']) || count($instruction['args']) != count($expectedDataTypes)) {
            exit(ReturnCode::SEMANTIC_ERROR);
        }
    
        foreach ($instruction['args'] as $index => $arg) {
            // Check if the dataType of the current argument is allowed
            $allowedDataTypes = $expectedDataTypes[$index];
            if (!in_array($arg['dataType'], $allowedDataTypes)) {
                $allowedTypesStr = implode(', ', $allowedDataTypes);
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
        }
    }
    
    private function processEscapeSequences(string $str) : string {
        $str = preg_replace_callback('/\\\\(\d{3})/', function ($matches) {
            $octal = $matches[1];
            $decimal = octdec($octal);
    
            switch ($decimal) {
                case 8:    // \010 -> newline
                    return "\n";
                case 26:   // \032 -> space
                    return " ";
                case 29:   // \035 -> hash
                    return "#";
                case 2:   // \092 -> backslash
                    return "\\";
                default:
                    // For octal values < 32 that aren't explicitly handled, ignore them
                    if ($decimal < 32) {
                        return "";
                    }
                    // Convert printable ASCII characters directly
                    return chr($decimal);
            }
        }, $str);
        return $str;
    }


    /**
     * Prints the contents of a frame or stack.
     *
     * @param string $name Name of the frame or stack.
     * @param mixed $frame Array representation of the frame or stack content.
     * @return void
     */
    private function printFrame(string $name, $frame): void {
        $this->stderr->writeString("\n$name:\n");
        foreach ($frame as $key => $value) {
            $this->stderr->writeString("  $key: " . json_encode($value) . "\n");
        }
    }

    

    public function parseInstructions() : void {
        $this->findLabel();
        $frame = Frame::getInstance();
        $count = 0;
        foreach ($this->instructions as $instruction) {
            $count++;
        }
        for ($this->Instrcount = 0; $this->Instrcount < $count; $this->Instrcount++) {
            $instruction = $this->instructions[$this->Instrcount];
            $this->opCode = strtoupper($instruction['opcode']); // Ensure opcode is in uppercase for comparison
            $methodName = $this->opCode;
            if (method_exists($this, $methodName)) {
                $this->$methodName($instruction);
            } elseif ($methodName === 'LABEL') {
                continue;
            }else {
                // Handle unknown opcode
                exit(ReturnCode::SEMANTIC_ERROR);
            }
        }
    }

    /**
    * Defines a variable in a specific frame.
    *
    * @param array<mixed> $instruction Instruction details including args for variable definition.
    * @return void
    */
    private function DEFVAR($instruction): void {
        $this->checkArguments($instruction, [['var']]);
        
        $varName = $instruction['args'][0]['value']; // Get variable name
        $frameType = $instruction['args'][0]['frame']; // Get frame type (GF, LF, TF)
        $dataType = isset($instruction['args'][0]['dataType']) ? $instruction['args'][0]['dataType'] : 'var'; // Default to 'var' if not set
        
        $frame = Frame::getInstance();
        $frame->addToFrame($frameType, $varName, $dataType); // Add variable to the specified frame with type
    }

    /**
    * Creates a new temporary frame.
    *
    * @param array<mixed> $instruction Instruction details (expected to be empty for CREATEFRAME).
    * @return void
    */
    private function CREATEFRAME($instruction): void {
        $this->checkArguments($instruction,[]);
        $frame = Frame::getInstance(); // Get the singleton instance of the Frame class
        $frame->initializeTF(); // Initialize the Temporary Frame
    }

    /**
     * Performs the MOVE operation by transferring values between variables or from a literal to a variable.
     *
     * @param array<mixed> $instruction Details of the MOVE operation.
     * @return void
    */
    private function MOVE($instruction) {
        // Check the format and types of the provided arguments
        $this->checkArguments($instruction, [['var'], ['var', 'bool', 'int', 'string', 'nil']]);
        // Extract arguments
        $sourceArg = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set
        // Retrieve frames and variable names
        $frame = Frame::getInstance();
        // Set the value from source to target
        if ($sourceArg['dataType'] === 'var') {
            $fetchedValue = $frame->getValue($sourceArg['value'], $sourceArg['frame']);
            $ArgValue = $fetchedValue['value'];
            $ArgType = $fetchedValue['type'];
        } else {
            $fetchedValue = $this->checkType($sourceArg['value'], $sourceArg['dataType']);
            $ArgValue = $fetchedValue['value'];
            $ArgType = $fetchedValue['type'];
        }

        $frame->setValue($targetArg['frame'], $targetArg['value'], $ArgValue, $ArgType);
    }

    /**
     * Initializes a new temporary frame in the virtual machine.
     *
     * @param array<mixed> $instruction Details of the PUSHFRAME operation.
     * @return void
    */
    private function PUSHFRAME($instruction){
        $this->checkArguments($instruction,[]);
        $frame = Frame::getInstance(); // Get the singleton instance of the Frame class
        $frame->pushFrame();
    }
    /**
     * Removes the topmost frame from the stack of frames.
     *
     * @param array<mixed> $instruction Details of the POPFRAME operation.
     * @return void
    */
    private function POPFRAME($instruction){
        $this->checkArguments($instruction,[]);
        $frame = Frame::getInstance(); // Get the singleton instance of the Frame class
        $frame->popFrame();
    }

    /**
     * Outputs the value of the specified variable or literal.
     *
     * @param array<mixed> $instruction Details of the WRITE operation.
     * @return void
    */
    private function WRITE($instruction) {
        $this->checkArguments($instruction, [['var', 'bool', 'int', 'string', 'nil']]);
        $Arg = $instruction['args'][0];
        $ArgType = $Arg['dataType'];
        $ArgValue = $Arg['value'];
        $frame = Frame::getInstance(); // Get the singleton instance of the Frame class
       
        if ($ArgType === 'var') {
            // Fetch the variable value from the frame using the variable name and the specified frame.
            $fetchedValue = $frame->getValue($ArgValue, $Arg['frame']);
            $ArgValue = $fetchedValue['value'];
            $ArgType = $fetchedValue['type']; // Optionally adjust type if stored within fetched array
        }else {
            $fetchedValue = $this->checkType($ArgValue, $ArgType);
            $ArgValue = $fetchedValue['value'];
            $ArgType = $fetchedValue['type'];
        }
        
       
        if ($ArgType === 'string') {
            $processedString = $this->processEscapeSequences($ArgValue);
            $this->stdout->writeString($processedString);
        } elseif ($ArgType === 'bool') {
            $this->stdout->writeBool($ArgValue);
        } elseif ($ArgType === 'int') {
            $this->stdout->writeInt($ArgValue);
        } elseif ($ArgType === 'nil') {
            $this->stdout->writeString("");
        }
    }
    
    /**
     * Adds two values.
     *
     * @param array<mixed> $instruction Instruction details.
    * @return void
    */
    private function ADD($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'int'], ['var', 'int']]);

        $sourceArg_2 = $instruction['args'][2];  // Source variable or value
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set

        $frame = Frame::getInstance();

        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            if ($fetchedValue1['type'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_1 = $fetchedValue1['value'];
        }elseif ($sourceArg_1['dataType'] === 'int') {
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);
            $ArgValue_1 = $fetchedValue1['value'];
        }else {
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            if ($fetchedValue2['type'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_2 = $fetchedValue2['value'];
        }elseif ($sourceArg_2['dataType'] === 'int') {
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);
            $ArgValue_2 = $fetchedValue2['value'];
        }else {
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $addValue = $ArgValue_1 + $ArgValue_2;
        
        $frame->setValue($targetArg['frame'], $targetArg['value'], $addValue, 'int');
    }

    /**
     * Subtracts one value from another.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function SUB($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'int'], ['var', 'int']]);

        $sourceArg_2 = $instruction['args'][2];  // Source variable or value
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set

        $frame = Frame::getInstance();

        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            //print_r($fetchedValue1);
            if ($fetchedValue1['type'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_1 = $fetchedValue1['value'];
        }elseif ($sourceArg_1['dataType'] === 'int') {
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);
            $ArgValue_1 = $fetchedValue1['value'];
        }else {
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            if ($fetchedValue2['type'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_2 = $fetchedValue2['value'];
        }elseif ($sourceArg_2['dataType'] === 'int') {
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);
            $ArgValue_2 = $fetchedValue2['value'];
        }else {
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $subValue = $ArgValue_1 - $ArgValue_2;
        
        $frame->setValue($targetArg['frame'], $targetArg['value'], $subValue, 'int');
    }

    /**
     * Multiplies two values.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function MUL($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'int'], ['var', 'int']]);

        $sourceArg_2 = $instruction['args'][2];  // Source variable or value
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set

        $frame = Frame::getInstance();

        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            if ($fetchedValue1['type'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_1 = $fetchedValue1['value'];
        }elseif ($sourceArg_1['dataType'] === 'int') {
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);
            $ArgValue_1 = $fetchedValue1['value'];
        }else {
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            if ($fetchedValue2['type'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_2 = $fetchedValue2['value'];
        }elseif ($sourceArg_2['dataType'] === 'int') {
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);
            $ArgValue_2 = $fetchedValue2['value'];
        }else {
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $mulValue = $ArgValue_1 * $ArgValue_2;
        
        $frame->setValue($targetArg['frame'], $targetArg['value'], $mulValue, 'int');
    }

    /**
     * Divides one value by another using integer division.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function IDIV($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'int'], ['var', 'int']]);

        $sourceArg_2 = $instruction['args'][2];  // Source variable or value
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set

        $frame = Frame::getInstance();

        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            if ($fetchedValue1['type'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_1 = $fetchedValue1['value'];
        }elseif ($sourceArg_1['dataType'] === 'int') {
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);
            $ArgValue_1 = $fetchedValue1['value'];
        }else {
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            if ($fetchedValue2['type'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_2 = $fetchedValue2['value'];
        }elseif ($sourceArg_2['dataType'] === 'int') {
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);
            $ArgValue_2 = $fetchedValue2['value'];
        }else {
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        if ($ArgValue_2 === 0) {
            exit(ReturnCode::OPERAND_VALUE_ERROR);
        }

        $idivValue = $ArgValue_1 / $ArgValue_2;
        
        $frame->setValue($targetArg['frame'], $targetArg['value'], $idivValue, 'int');
    }


    /**
     * Compares if one value is less than another.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function LT($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'bool', 'int', 'string'], ['var', 'bool', 'int', 'string']]);
       
        $sourceArg_2 = $instruction['args'][2];  // Source variable or value
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set

        $frame = Frame::getInstance();

        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }else {
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }

        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }else {
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }

        if (($ArgType_1 !== $ArgType_2) || ($ArgType_1 === 'nil'|| $ArgType_2 === 'nil' )){
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }
        
        $LTresult = $ArgValue_1 <  $ArgValue_2;

        $frame->setValue($targetArg['frame'], $targetArg['value'], $LTresult, 'bool');


    }

    /**
     * Compares if one value is greater than another.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function GT($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'bool', 'int', 'string'], ['var', 'bool', 'int', 'string']]);
       
        $sourceArg_2 = $instruction['args'][2];  // Source variable or value
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set

        $frame = Frame::getInstance();

        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            //print_r($fetchedValue1);
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }else {
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }

        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            //print_r($fetchedValue1);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }else {
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }

        if (($ArgType_1 !== $ArgType_2) || ($ArgType_1 === 'nil'|| $ArgType_2 === 'nil' )){
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $GTresult = $ArgValue_1 >  $ArgValue_2;
        


        $frame->setValue($targetArg['frame'], $targetArg['value'], $GTresult, 'bool');


    }

    /**
     * Checks equality between two values.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function EQ($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'bool', 'int', 'string', 'nil'], ['var', 'bool', 'int', 'string', 'nil']]);
       
        $sourceArg_2 = $instruction['args'][2];  // Source variable or value
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set

        $frame = Frame::getInstance();
        
        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }else {
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }

        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }else {
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }

        if ($ArgType_1 !== $ArgType_2){
            if ($ArgType_1 !== 'nil' && $ArgType_2 !== 'nil'){
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
        }
        
        $EQresult = $ArgValue_1 ===  $ArgValue_2;


        $frame->setValue($targetArg['frame'], $targetArg['value'], $EQresult, 'bool');


    }

    
    /**
     * Performs a logical AND operation.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function AND($instruction) { 
        $this->checkArguments($instruction, [['var'], ['var','bool'], ['var', 'bool']]);

        $sourceArg_2 = $instruction['args'][2];  // Source variable or value
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set

        $frame = Frame::getInstance();

        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }else {
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);;
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }

        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }else {
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }

        if ($ArgType_1 !== 'bool' || $ArgType_2 !== 'bool') {
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $ANDresult = $ArgValue_1 && $ArgValue_2;

        $frame->setValue($targetArg['frame'], $targetArg['value'], $ANDresult, 'bool');
    }

    /**
     * Performs a logical OR operation.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function OR($instruction) { 
         $this->checkArguments($instruction, [['var'], ['var','bool'], ['var', 'bool']]);

        $sourceArg_2 = $instruction['args'][2];  // Source variable or value
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set

        $frame = Frame::getInstance();

        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }else {
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);;
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }

        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }else {
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }

        if ($ArgType_1 !== 'bool' || $ArgType_2 !== 'bool') {
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $ORresult = $ArgValue_1 || $ArgValue_2;

        $frame->setValue($targetArg['frame'], $targetArg['value'], $ORresult, 'bool');
    }
    /**
     * Performs a logical NOT operation.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function NOT($instruction) { 
        $this->checkArguments($instruction, [['var'], ['var','bool']]);

        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set

        $frame = Frame::getInstance();

        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }else {
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);;
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }


        if ($ArgType_1 !== 'bool') {
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $NOTresult = !$ArgValue_1;
        $frame->setValue($targetArg['frame'], $targetArg['value'], $NOTresult, 'bool');
    }

    /**
     * Converts an integer to a character based on its ASCII value.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function INT2CHAR($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'int']]);
    
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set
    
        $frame = Frame::getInstance();
    
        // Fetch the source value, handling whether it's a direct int or a variable holding an int
        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            if ($fetchedValue1['type'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_1 = $fetchedValue1['value'];
        } else {
            if ($sourceArg_1['dataType'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);;
            $ArgValue_1 = $fetchedValue1['value'];
        }
    
        // Convert the integer to a Unicode character
        if ($ArgValue_1 < 0 || $ArgValue_1 > 0x10FFFF) {  // Check if the code point is within the valid Unicode range
            exit(ReturnCode::STRING_OPERATION_ERROR);  // Error code for invalid character code
        }
    
        $char = chr($ArgValue_1);
        
        // Set the result in the target variable
        $frame->setValue($targetArg['frame'], $targetArg['value'], $char, 'string');
    }

    /**
     * Converts a character in a string to its ASCII integer value.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function STRI2INT($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'string'], ['var', 'int']]);
        $sourceArg_2 = $instruction['args'][2];
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set
        $frame = Frame::getInstance();
    
        // Fetch the source value, handling whether it's a direct int or a variable holding an int
        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            if ($fetchedValue1['type'] !== 'string') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_1 = $fetchedValue1['value'];
        } else {
            if ($sourceArg_1['dataType'] !== 'string') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);;
            $ArgValue_1 = $fetchedValue1['value'];
        }
        
        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            if ($fetchedValue2['type'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_2 = $fetchedValue2['value'];
        } else {
            if ($sourceArg_2['dataType'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);;
            $ArgValue_2 = $fetchedValue2['value'];
        }

        if ($ArgValue_2 < 0 || $ArgValue_2 >= strlen($ArgValue_1)) {
            exit(ReturnCode::STRING_OPERATION_ERROR);
        }

        $val = ord($ArgValue_1[$ArgValue_2]);
        
    
        // Set the result in the target variable
        $frame->setValue($targetArg['frame'], $targetArg['value'], $val, 'int');
    }
    /**
     * Calculates the length of a string.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function STRLEN($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'string']]);
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set
    
        $frame = Frame::getInstance();
    
        // Fetch the source value, handling whether it's a direct int or a variable holding an int
        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            if ($fetchedValue1['type'] !== 'string') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_1 = $fetchedValue1['value'];
        } else {
            if ($sourceArg_1['dataType'] !== 'string') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);;
            $ArgValue_1 = $fetchedValue1['value'];
        }
        

        $STRLENresult = strlen($ArgValue_1);
        
    
        // Set the result in the target variable
        $frame->setValue($targetArg['frame'], $targetArg['value'], $STRLENresult, 'int');
    }

    /**
     * Concatenates two strings.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function CONCAT($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'string'], ['var', 'string']]);
        $sourceArg_2 = $instruction['args'][2];
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set
    
        $frame = Frame::getInstance();
    
        // Fetch the source value, handling whether it's a direct int or a variable holding an int
        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            if ($fetchedValue1['type'] !== 'string') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_1 = $fetchedValue1['value'];
        } else {
            if ($sourceArg_1['dataType'] !== 'string') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);;
            $ArgValue_1 = $fetchedValue1['value'];
        }
        
        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            if ($fetchedValue2['type'] !== 'string') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_2 = $fetchedValue2['value'];
        } else {
            if ($sourceArg_2['dataType'] !== 'string') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);;
            $ArgValue_2 = $fetchedValue2['value'];
        }

        $CONCresult = $ArgValue_1 . $ArgValue_2;
        
    
        // Set the result in the target variable
        $frame->setValue($targetArg['frame'], $targetArg['value'], $CONCresult, 'string');
    }

    /**
     * Retrieves a character from a string at a specific index.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function GETCHAR($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'string'], ['var', 'int']]);
        $sourceArg_2 = $instruction['args'][2];
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set
    
        $frame = Frame::getInstance();
    
        // Fetch the source value, handling whether it's a direct int or a variable holding an int
        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            if ($fetchedValue1['type'] !== 'string') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_1 = $fetchedValue1['value'];
        } else {
            if ($sourceArg_1['dataType'] !== 'string') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);;
            $ArgValue_1 = $fetchedValue1['value'];
        }
        
        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            if ($fetchedValue2['type'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_2 = $fetchedValue2['value'];
        } else {
            if ($sourceArg_2['dataType'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);;
            $ArgValue_2 = $fetchedValue2['value'];
        }

        if ($ArgValue_2 < 0 || $ArgValue_2 >= strlen($ArgValue_1)) {
            exit(ReturnCode::STRING_OPERATION_ERROR);
        }

        $GETCHresult = $ArgValue_1[$ArgValue_2];
        
    
        // Set the result in the target variable
        $frame->setValue($targetArg['frame'], $targetArg['value'],  $GETCHresult, 'string');
    }

    /**
     * Replaces a character in a string at a specific index with another character.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function SETCHAR($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'int'], ['var', 'string']]);
        $sourceArg_2 = $instruction['args'][2];
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set
    
        $frame = Frame::getInstance();
    
        if ($targetArg['dataType'] === 'var') {
            $fetchedValue = $frame->getValue($targetArg['value'], $targetArg['frame']);
            if ($fetchedValue['type'] !== 'string') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue = $fetchedValue['value'];
        }else {
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        // Fetch the source value, handling whether it's a direct int or a variable holding an int
        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            if ($fetchedValue1['type'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_1 = $fetchedValue1['value'];
        } else {
            if ($sourceArg_1['dataType'] !== 'int') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);;
            $ArgValue_1 = $fetchedValue1['value'];
        }
        
        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            if ($fetchedValue2['type'] !== 'string') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $ArgValue_2 = $fetchedValue2['value'];
        } else {
            if ($sourceArg_2['dataType'] !== 'string') {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);
            $ArgValue_2 = $fetchedValue2['value'];
        }

        if ($ArgValue_1 < 0 || $ArgValue_1 >= strlen($ArgValue)) {
            exit(ReturnCode::STRING_OPERATION_ERROR);
        }

        $ArgValue[$ArgValue_1] = $ArgValue_2;
        
        // Set the result in the target variable
        $frame->setValue($targetArg['frame'], $targetArg['value'],  $ArgValue, 'string');
    }

    /**
     * Reads the string from input.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function READ($instruction) {
        
        $inputs = (string)$this->stdin->readString();
        
        $sourceArg = $instruction['args'][1];
        $targetArg = $instruction['args'][0];
        if ($sourceArg['dataType'] !== 'type' ) {
            exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
        }
        $frame = Frame::getInstance();
        $this->checkArguments($instruction, [['var'], ['type']]);
        $Type = $sourceArg['value'];

        if($Type === "int") {
            $inputs = ($inputs > 1114112) ? null : (int)$inputs;
            $Type = ($inputs === null) ? "nil" : $Type;
        }
        elseif($Type === "bool") {
            $inputs = ($inputs === "true") ? true : (($inputs === "false") ? false : null);
            $Type = ($inputs === null) ? "nil" : $Type;
        }
        elseif ($Type === "nil")
            $inputs = null;

        $frame->setValue($targetArg['frame'], $targetArg['value'],  $inputs, $Type);
    }


    /**
     * Detects the type dynamically.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function TYPE($instruction) {
        $this->checkArguments($instruction, [['var'], ['var', 'int', 'bool', 'string', 'nil']]);
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set
    
        $frame = Frame::getInstance();
        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            $ValueType = $fetchedValue['type'];
            if ($ValueType === 'var') {
                $ValueType = "";
            }
        }else {
            $ValueType = $sourceArg_1['dataType'];
        }
        
        // Set the result in the target variable
        $frame->setValue($targetArg['frame'], $targetArg['value'],  $ValueType, 'string');
    }

    /**
     * Pushes a value onto the stack.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function PUSHS($instruction) {
        $this->checkArguments($instruction, [['var', 'int', 'bool', 'string', 'nil']]);
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set
        
        $frame = Frame::getInstance();
        
        if ($targetArg['dataType'] === 'var') {
            $fetchedValue = $frame->getValue($targetArg['value'], $targetArg['frame']);
        }else {
            $fetchedValue = $this->checkType($targetArg['value'], $targetArg['dataType']);
        }
    
        $this->stack->push($fetchedValue);
    }

    /**
     * Pops a value from the stack.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function POPS($instruction) {
        $this->checkArguments($instruction, [['var']]);
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set
        
        $frame = Frame::getInstance();
        
        $value = $this->stack->pop();
    
        $frame->setValue($targetArg['frame'], $targetArg['value'],  $value['value'], $value['type']);
    }

    /**
     * Writes a string to stderr.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function DPRINT($instruction) {
        $this->checkArguments($instruction, [['var', 'int', 'bool', 'string', 'nil']]);
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set
        
        $frame = Frame::getInstance();
        
        if ($targetArg['dataType'] === 'var') {
            $fetchedValue = $frame->getValue($targetArg['value'], $targetArg['frame']);
            $VAL = $fetchedValue['value'];
        }else {
            $VAL = $targetArg['value'];
        }
       
        $this->stderr->writeString($VAL);
    }

    /**
     * Writes information about the state of the program.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */   
    private function BREAK($instruction) {
        $this->checkArguments($instruction, []);
        $order = $instruction['order'];
        $frame = Frame::getInstance();
        
        $this->stderr->writeString("\nInstruction number: $order\n");
        $this->stderr->writeString("Number of processed instructions: $this->Instrcount\n");
    
        // Print Global Frame
        $this->printFrame("Global Frame", $frame->GF);
    
        // Print Local Frame
        if (!is_null($frame->LF)) {
            $this->printFrame("Local Frame", $frame->LF);
        }
    
        // Print Temporary Frame
        if (!is_null($frame->TF)) {
            $this->printFrame("Temporary Frame", $frame->TF);
        }
    }

    /**
     * Calls the label.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function CALL($instruction) {
        $this->checkArguments($instruction, [['label']]);
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set
        $this->labelStack->push($this->Instrcount);
        $this->Instrcount = $this->labelll->jump($targetArg['value'], $this->Instrcount);
    }

    /**
     * Returns from a label.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function RETURN($instruction) {
        $this->checkArguments($instruction, []);
        $this->Instrcount = $this->labelStack->pop();
    }

    /**
     * Jump to the label.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function JUMP($instruction) {
        $this->checkArguments($instruction, [['label']]);
        $targetArg = $instruction['args'][0];  // Target variable where the value will be set
        $this->Instrcount = $this->labelll->jump($targetArg['value'], $this->Instrcount);
    }

    /**
     * Performs a conditional jump if equal.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function JUMPIFEQ($instruction) {
        $this->checkArguments($instruction, [['label'], ['var', 'bool', 'int', 'string', 'nil'], ['var', 'bool', 'int', 'string', 'nil']]);
        $sourceArg_2 = $instruction['args'][2];
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];

        $frame = Frame::getInstance();
        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }else {
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }

        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }else {
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }

        if (($ArgType_1 !== $ArgType_2)) {
            if ($ArgType_1 !== 'nil' && $ArgType_2 !== 'nil' ) {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
        }
        if ($ArgValue_1 === $ArgValue_2) {
            $this->Instrcount = $this->labelll->jump($targetArg['value'], $this->Instrcount);
        }
    }

    /**
     * Performs a conditional jump if not equal.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function JUMPIFNEQ($instruction) {
        $this->checkArguments($instruction, [['label'], ['var', 'bool', 'int', 'string', 'nil'], ['var', 'bool', 'int', 'string', 'nil']]);
        $sourceArg_2 = $instruction['args'][2];
        $sourceArg_1 = $instruction['args'][1];  // Source variable or value
        $targetArg = $instruction['args'][0];

        $frame = Frame::getInstance();

        if ($sourceArg_1['dataType'] === 'var') {
            $fetchedValue1 = $frame->getValue($sourceArg_1['value'], $sourceArg_1['frame']);
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }else {
            $fetchedValue1 = $this->checkType($sourceArg_1['value'], $sourceArg_1['dataType']);
            $ArgValue_1 = $fetchedValue1['value'];
            $ArgType_1 = $fetchedValue1['type'];
        }

        if ($sourceArg_2['dataType'] === 'var') {
            $fetchedValue2 = $frame->getValue($sourceArg_2['value'], $sourceArg_2['frame']);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }else {
            $fetchedValue2 = $this->checkType($sourceArg_2['value'], $sourceArg_2['dataType']);
            $ArgValue_2 = $fetchedValue2['value'];
            $ArgType_2 = $fetchedValue2['type'];
        }
        
       

        if (($ArgType_1 !== $ArgType_2)) {
            if ($ArgType_1 !== 'nil' && $ArgType_2 !== 'nil' ) {
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            }
        }

        if ($ArgValue_1 !== $ArgValue_2) {
            $this->Instrcount = $this->labelll->jump($targetArg['value'], $this->Instrcount);
        }
    }

    /**
     * Exits the program with an integer.
     *
     * @param array<mixed> $instruction Instruction details.
     * @return void
     */
    private function EXIT($instruction) {
        $this->checkArguments($instruction, [['var', 'bool', 'int', 'string', 'nil']]);
        $targetArg = $instruction['args'][0];

        $frame = Frame::getInstance();

        if ($targetArg['dataType'] === 'var') {
            $fetchedValue = $frame->getValue($targetArg['value'], $targetArg['frame']);
            $ArgValue = $fetchedValue['value'];
            $ArgType = $fetchedValue['type'];
        }else {
            $fetchedValue = $this->checkType($targetArg['value'], $targetArg['dataType']);;
            $ArgValue = $fetchedValue['value'];
            $ArgType = $fetchedValue['type'];
        }

        if ($ArgType !== 'int') {
            exit(ReturnCode::OPERAND_TYPE_ERROR);
        }

        if ($ArgValue < 0 || $ArgValue > 49) {
            exit(ReturnCode::OPERAND_VALUE_ERROR);
        }

        exit($ArgValue);
    }
}