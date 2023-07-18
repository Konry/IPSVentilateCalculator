<?php

declare(strict_types=1);
include_once __DIR__ . '/stubs/Validator.php';
class VentilateCalculatorValidationTest extends TestCaseSymconValidation
{
    public function testValidateVentilateCalculator(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }
    public function testValidateVentilateCalculatorModule(): void
    {
        $this->validateModule(__DIR__ . '/../VentilateCalculator');
    }
}