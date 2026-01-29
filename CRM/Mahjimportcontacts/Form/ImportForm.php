<?php

use CRM_Mahjimportcontacts_ExtensionUtil as E;


class CRM_Mahjimportcontacts_Form_ImportForm extends CRM_Core_Form {
  public function buildQuickForm(): void {
    $this->setTitle("Import contacts billetterie");
    $this->add('File', 'uploadFile', 'Fichier Excel', ['size' => 30, 'maxlength' => 255], TRUE);
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ],
    ]);

    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess(): void {
    try {
      $values = $this->exportValues();

      // get the selected file
      $tmpFileName = $this->_submitFiles['uploadFile']['tmp_name'];
      if (!$tmpFileName) {
        CRM_Core_Session::setStatus('Pas pu lire ' . $this->_submitFiles['uploadFile']['name'] . '. Est-ce que le fichier est trop gros ?', 'Error', 'error');
      }
      else {
        $idFinderProcessor = new CRM_Mahjimportcontacts_BAO_Processor($tmpFileName);
        $n = $idFinderProcessor->process();
        CRM_Core_Session::setStatus("Contacts importés : $n");
      }
    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus('Une erreur est survenue lors de l\'importation des contacts : ' . $e->getMessage(), 'Error', 'error');
    }

    parent::postProcess();
  }

  public function getRenderableElementNames(): array {
    $elementNames = [];
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
