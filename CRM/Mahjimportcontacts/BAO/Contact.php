<?php

class CRM_Mahjimportcontacts_BAO_Contact {
  public static function findContactByEmail(string $email): int {
    if (empty($email)) {
      return 0;
    }

    $sql = "
      SELECT
        c.id
      FROM
        civicrm_contact c
      inner join
        civicrm_email e on e.contact_id = c.id
      WHERE
        e.email = %1
      and
        c.is_deleted = 0";
    $sqlParams = [
      1 => [$email, 'String']
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    if ($dao->fetch()) {
      return $dao->id;
    }
    else {
      return 0;
    }
  }



}
