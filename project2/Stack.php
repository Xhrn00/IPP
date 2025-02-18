<?php

namespace IPP\Student;

use IPP\Core\ReturnCode;

class Stack {
    /**
     * Stack content stored in an array.
     * @var array<mixed> The stack can hold elements of any type.
     */
    public $stack;

    /**
     * Constructor initializes an empty stack.
     */
    public function __construct() {
        $this->stack = [];
    }

    /**
     * Pops the top value off the stack.
     * 
     * @return mixed The last value of the stack if not empty.
     */
    public function pop() {
        if (empty($this->stack)) {
            exit(ReturnCode::VALUE_ERROR);
        }

        return array_pop($this->stack);
    }

    /**
     * Pushes a new value onto the stack.
     * 
     * @param mixed $value The value to push onto the stack.
     * @return void
     */
    public function push($value) {
        $this->stack[] = $value;
    }

    
}
