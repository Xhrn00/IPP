<?php

namespace IPP\Student;

use IPP\Core\ReturnCode;

class Label {
    /**
     * Maps label names to positions.
     * @var array<mixed>
     */
    public array $label;

    public function __construct() {
       $this->label = [];
    }

    /**
     * Jumps interpret reading to a certain label.
     *
     * @param string|int $name The name of the label.
     * @param int $i The current position to update.
     * @return int Updated position based on label.
     */
    public function jump($name, int $i): int {
        // Check for existence
        if (!array_key_exists($name, $this->label)) {
            exit(ReturnCode::SEMANTIC_ERROR);
        }

        // Jump interpret reading to label
        $i = $this->label[$name];
        return $i;
    }
}
