<?php

use CRM_Mahjimportcontacts_ExtensionUtil as E;
require_once  E::path('vendor/autoload.php');

class CRM_Mahjimportcontacts_BAO_Processor {
  private PhpOffice\PhpSpreadsheet\Spreadsheet $inputFile;
  private PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet;
  private $columnHeading = [];
  private $importGroupId;

  public function __construct(string $inputFile) {
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

    $this->importGroupId = CRM_Mahjimportcontacts_BAO_Group::getGroupOfTheMonth();

    $lineNumber = 2;
    while (!$this->isEmptyLine($lineNumber)) {
      $this->processLine($lineNumber);
      $lineNumber++;
    }

    return $lineNumber - 2;
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
    $forceAdd = FALSE;

    $email = $this->getCellValue('email', $lineNumber);
    $contactId = CRM_Mahjimportcontacts_BAO_Contact::findContactByEmail($email);
    if ($contactId == 0) {
      $contactId = CRM_Mahjimportcontacts_BAO_Contact::createOrganization($this->getCellValue('employer_name', $lineNumber), $email);
      $forceAdd = TRUE; // do not check if the contact has an address of phone
    }

    CRM_Mahjimportcontacts_BAO_Contact::createAddress(
      $contactId,
      $this->getCellValue('street_address', $lineNumber),
      $this->getCellValue('city', $lineNumber),
      $this->getCellValue('postal_code', $lineNumber),
      $this->getCellValue('country_name', $lineNumber),
      $forceAdd
    );
    CRM_Mahjimportcontacts_BAO_Contact::createPhone($contactId, $this->getCellValue('phone', $lineNumber), $forceAdd);

    CRM_Mahjimportcontacts_BAO_Group::addContact($contactId, $this->importGroupId);
  }

  private function processLineIndividual(int $lineNumber) {
    $forceAdd = FALSE;

    $email = $this->getCellValue('email', $lineNumber);
    $contactId = CRM_Mahjimportcontacts_BAO_Contact::findContactByEmail($email);
    if ($contactId == 0) {
      $contactId = CRM_Mahjimportcontacts_BAO_Contact::createIndividual($this->getCellValue('first_name', $lineNumber), $this->getCellValue('last_name', $lineNumber), $email);
      $forceAdd = TRUE;  // do not check if the contact has an address of phone
    }

    // update address and phone
    CRM_Mahjimportcontacts_BAO_Contact::createAddress(
      $contactId,
      $this->getCellValue('street_address', $lineNumber),
      $this->getCellValue('city', $lineNumber),
      $this->getCellValue('postal_code', $lineNumber),
      $this->getCellValue('country_name', $lineNumber),
      $forceAdd
    );
    CRM_Mahjimportcontacts_BAO_Contact::createPhone($contactId, $this->getCellValue('phone', $lineNumber), $forceAdd);

    CRM_Mahjimportcontacts_BAO_Group::addContact($contactId, $this->importGroupId);
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
