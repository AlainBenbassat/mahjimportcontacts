<?php

use CRM_Mahjimportcontacts_ExtensionUtil as E;
require_once  E::path('vendor/autoload.php');

class CRM_Mahjimportcontacts_BAO_Processor {
  private PhpOffice\PhpSpreadsheet\Spreadsheet $inputFile;
  private PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet;
  private $columnHeading = [];

  public function __construct(string $inputFile = '/tmp/test.xlsx') {
    $this->inputFile = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFile);
    $this->worksheet = $this->inputFile->getActiveSheet();
  }

  public function process(): int {
    $this->findColumnHeading('employer_name', 'Employeur');
    $this->findColumnHeading('street_address', 'Rue');
    $this->findColumnHeading('postal_code','Code postal');
    $this->findColumnHeading('phone', 'Téléphone');
    $this->findColumnHeading('email', 'Courriel');
    $this->findColumnHeading('last_name', 'Nom');
    $this->findColumnHeading('first_name', 'Prénom');
    $this->findColumnHeading('city', 'Ville');
    $this->findColumnHeading('country_name', 'Pays');

    $lineNumber = 2;
    while (!$this->isEmptyLine($lineNumber)) {
      $this->processLine($lineNumber);
      $lineNumber++;
    }

    return ($lineNumber - 1);
  }

  private function findColumnHeading(string $key, string $columnName): void {
    for ($i = 1; $i <= 255; $i++) {
      $colLetter = PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
      $cell = $this->worksheet->getCell($colLetter . '1');
      $cellValue = $cell->getValue();
      if (empty($cellValue)) {
        break;
      }

      if (str_starts_with(trim($cellValue), $columnName)) {
        $this->columnHeading[$key] = $colLetter;
        return;
      }
    }

    throw new \Exception("Colonne '$columnName' non trouvée");
  }

  private function isEmptyLine(int $lineNumber): bool {
    $isEmpty = TRUE;

    foreach ($this->columnHeading as $colLetter) {
      $val = $this->worksheet->getCell($colLetter . $lineNumber)->getValue();
      if (!is_null($val)) {
        // note: trim expects a string, null is not permitted, hence is_null above
        if (!empty(trim($val))) {
          $isEmpty = FALSE;
          break;
        }
      }
    }

    return $isEmpty;
  }

  private function processLine(int $lineNumber): void {
    if ($this->isOrganization($lineNumber)) {
      $this->processLineOrganization($lineNumber);
    }
    else {
      $this->processLineIndividual($lineNumber);
    }
  }

  private function isOrganization(int $lineNumber): bool {
    if (empty($this->getCellValue('employer_name', $lineNumber))) {
      // no organization name
      return FALSE;
    }

    if (empty($this->getCellValue('first_name', $lineNumber)) && empty($this->getCellValue('last_name', $lineNumber))) {
      // we have an organization name but no contact name = GOOD
      return TRUE;
    }

    return FALSE;
  }

  private function processLineOrganization(int $lineNumber) {
    $email = $this->getCellValue('email', $lineNumber);
    $contactId = CRM_Mahjimportcontacts_BAO_Contact::findContactByEmail($email);
    if ($contactId > 0) {
      echo "$lineNumber. EXISTING ORG: " . $this->getCellValue('employer_name', $lineNumber) . "\n";
      return; // skip, already exists
    }

    echo "$lineNumber. NEW ORG: " . $this->getCellValue('employer_name', $lineNumber) . "\n";
  }

  private function processLineIndividual(int $lineNumber) {
    $email = $this->getCellValue('email', $lineNumber);
    $contactId = CRM_Mahjimportcontacts_BAO_Contact::findContactByEmail($email);
    if ($contactId > 0) {
      echo "$lineNumber. EXISTING INDIVIDUAL: " . $this->getCellValue('first_name', $lineNumber) . " " . $this->getCellValue('last_name', $lineNumber) . "\n";
      return; // skip, already exists
    }
    echo "$lineNumber. NEW INDIVIDUAL: " . $this->getCellValue('first_name', $lineNumber) . " " . $this->getCellValue('last_name', $lineNumber) . "\n";
  }

  private function getCellValue($fieldName, $lineNumber) {
    $val = $this->worksheet->getCell($this->columnHeading[$fieldName] . $lineNumber)->getValue();
    if (is_null($val)) {
      return '';
    }
    else {
      // note: trim expects a string, null is not permitted
      return trim($val);
    }
  }

}
