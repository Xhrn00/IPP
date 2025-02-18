<?php

namespace IPP\Student;

use IPP\Core\ReturnCode;

class Frame {
    private static ?Frame $instance = null; // Assuming $instance should only contain an instance of Frame or null
    /** @var array<string> */
    public array $GF = []; // Array of strings
    /** @var array<string>|null */
    public ?array $LF = null; // Array of strings or null
    /** @var array<string>|null */
    public ?array $TF = null; // Array of strings or null


    private function __construct() {}

    public static function getInstance(): Frame {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function initializeTF(): void {
        $this->TF = [];
    }

    public function pushFrame(): void {
        if ($this->LF === null) {
            $this->LF = [];
        }
        if ($this->TF === null) {
            exit(ReturnCode::FRAME_ACCESS_ERROR);
        }
        $this->LF = $this->TF;  // Set LF to the TF just pushed
        $this->TF = null;       // Clear TF after pushing
    }

    public function popFrame(): void {
        
        if (empty($this->LF)) {
            exit(ReturnCode::FRAME_ACCESS_ERROR);
        }
        // Get the key of the last element
        $lastKey = array_key_last($this->LF);
        $TfPart = array_pop($this->LF);
        $this->TF = [$lastKey => $TfPart];
        
        if (empty($this->LF)){
                $this->LF = [];
        }
    }

    private function &findFrame(string $frame): mixed {
        switch ($frame) {
            case 'GF':
                return $this->GF;
            case 'LF':
                if ($this->LF === null) {
                    exit(ReturnCode::FRAME_ACCESS_ERROR);
                }
                return $this->LF;
            case 'TF':
                if ($this->TF === null) {
                    exit(ReturnCode::FRAME_ACCESS_ERROR);
                }
                return $this->TF;
            default:
                exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
        }
    }

    public function addToFrame(string $frame, string $name, ?string $type = null): void {
        $currentFrame = &$this->findFrame($frame);
        if (!array_key_exists($name, $currentFrame)) {
            $currentFrame[$name] = ['value' => $name, 'type' => $type];
        } else {
            exit(ReturnCode::SEMANTIC_ERROR);
        }
    }

    public function setValue(string $frame, string $name, mixed $value, mixed $type): void {
        $currentFrame = &$this->findFrame($frame);
        if (array_key_exists($name, $currentFrame)) {
            $currentFrame[$name] = ['value' => $value, 'type' => $type];
        } else {
            exit(ReturnCode::VARIABLE_ACCESS_ERROR);
        }
    }

    public function getValue(string $name, string $frame): mixed {
        // Identify the frame based on the variable name prefix
        $currentFrame = &$this->findFrame($frame);
        
        // Check if the variable exists in the identified frame
        if (!array_key_exists($name, $currentFrame)) {
            //echo "Error: Variable '{$name}' does not exist.\n";
            exit(ReturnCode::VARIABLE_ACCESS_ERROR);  // Assuming you have defined error codes appropriately
        }
        
        // Get the value from the frame
        $result = $currentFrame[$name];
        // Check if the value is initialized (not null in PHP's case)
        if ($result === null) {
            exit(ReturnCode::VALUE_ERROR);  // Assuming VALUE_ERROR is defined in ReturnCode
        }
        return $result;
    }
}