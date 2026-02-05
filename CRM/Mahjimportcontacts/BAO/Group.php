<?php

class CRM_Mahjimportcontacts_BAO_Group {
  public static function getGroupOfTheMonth(): int {
    $groupTitle = 'Import GAT ' . date('Y-m');
    $groupName = 'import_gat_' . date('Y_m');

    $group = \Civi\Api4\Group::get(FALSE)
      ->addSelect('id')
      ->addWhere('name', '=', $groupName)
      ->execute()
      ->first();

    if ($group) {
      return $group['id'];
    }

    $result = \Civi\Api4\Group::create(FALSE)
      ->addValue('name', $groupName)
      ->addValue('title', $groupTitle)
      ->execute();

    return $result[0]['id'];
  }

  public static function addContact(int $contactId, int $groupId) {
    try {
      \Civi\Api4\GroupContact::create(FALSE)
        ->addValue('group_id', $groupId)
        ->addValue('contact_id', $contactId)
        ->addValue('status', 'Added')
        ->execute();
    }
    catch (Exception $e) {
      // ignore, it's probably because the contact is already in the group
    }
  }
}
