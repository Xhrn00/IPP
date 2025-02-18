<?php

namespace IPP\Student;

use IPP\Student\Exception\SOURCE_STRUCT_Exception;
use IPP\Core\ReturnCode;

class XML_Parser {
 /**
 * Parse the given XML string and return structured instruction data.
 * 
 * @param string $xmlString The XML string to parse.
 * @return array<int, array{
 *  order: int,
 *  opcode: string,
 *  args: array<int, array{
 *      type: string,
 *      value: string,
 *      frame: string,
 *      dataType: string
 *  }>
 * }> Structured array of instructions.
 */
    public static function parseXMLString(string $xmlString): array {
        $dom = new \DOMDocument();
        if (!$dom->loadXML($xmlString)) {
            //throw new SOURCE_STRUCT_Exception("Invalid XML format.");
            exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
        }
        
        self::checking_header_XML($dom);

        $instructions = [];
        $orderSeen = [];
        foreach ($dom->getElementsByTagName('instruction') as $instructionElement) {
            $order = trim($instructionElement->getAttribute('order'));
            $opcode = trim($instructionElement->getAttribute('opcode'));
            
            if (empty($opcode) || empty($order)) {
                //throw new SOURCE_STRUCT_Exception("Invalid XML format.");
                exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
            }

            $orderValue = intval($order);
            if ($orderValue <= 0 || isset($orderSeen[$orderValue])) {
                exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
            }
            $orderSeen[$orderValue] = true;

            $args = [];
            $argTypesSeen = [];
            foreach ($instructionElement->childNodes as $arg) {
                if ($arg instanceof \DOMElement) {
                    $type = $arg->nodeName;
                    $rawValue = trim($arg->nodeValue);
                    $dataType = $arg->getAttribute('type');

                    if (!preg_match('/^arg[1-3]\d*$/', $type)) {
                        exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
                    }

                    if (empty($dataType)) {
                        exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
                    }

                    if (isset($argTypesSeen[$type])) {
                        exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
                    }
                    $argTypesSeen[$type] = true;

                    $matches = [];
                    $frame = "";
                    $value = $rawValue;
                    if (preg_match('/^([GLT]F)@(.+)$/', $rawValue, $matches)) {
                        $frame = $matches[1];
                        $value = $matches[2];
                    }

                    $args[] = [
                        'type' => $type,
                        'value' => $value,
                        'frame' => $frame,
                        'dataType' => $dataType
                    ];
                }
            }

            usort($args, function ($a, $b) {
                return substr($a['type'], 3) <=> substr($b['type'], 3);
            });

            $instructions[] = [
                'order' => $orderValue,
                'opcode' => $opcode,
                'args' => $args
            ];
        }
        // Sort instructions by order
        usort($instructions, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        
        return $instructions;
    }

    /**
     * Perform XML validation checks.
     * 
     * @param \DOMDocument $dom The DOMDocument instance.
     */
    private static function checking_header_XML(\DOMDocument $dom): void {
        if ($dom->xmlVersion !== '1.0' || $dom->encoding !== 'UTF-8') {
            exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
        }
        if ($dom->documentElement->nodeName !== 'program') {
            exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
        }
        
        $programLanguage = $dom->documentElement->getAttribute('language');
        if ($programLanguage !== 'IPPcode24') {
            
            exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
        }

        // Ensure all child elements of 'program' are 'instruction'
        foreach ($dom->documentElement->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName !== 'instruction') {
                exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
            }
        }
    }
}