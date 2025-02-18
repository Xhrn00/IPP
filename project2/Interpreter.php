<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\ReturnCode;

class Interpreter extends AbstractInterpreter {
    public function execute(): int {
        $stdout = $this->stdout;
        $stdin = $this->input;
        $stderr = $this->stderr;
        $xmlParserClass = "\IPP\Student\XML_Parser";

        $dom = $this->source->getDOMDocument();
        $xmlString = $dom->saveXML();
       
        $instructions = $xmlParserClass::parseXMLString($xmlString);
        
        $parser = new InstructionParser($instructions, $stdin, $stderr, $stdout);
        $parser->parseInstructions();
        
        return ReturnCode::OK;
    }
}
