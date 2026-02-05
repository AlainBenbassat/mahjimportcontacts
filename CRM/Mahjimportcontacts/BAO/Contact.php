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

  public static function createIndividual(string $firstName, string $lastName, string $email): int {
    if (empty($firstName) && empty($lastName)) {
      $lastName = $email;
    }

    $result = \Civi\Api4\Contact::create(FALSE)
      ->addValue('contact_type', 'Individual')
      ->addValue('first_name', $firstName)
      ->addValue('last_name', $lastName)
      ->execute();

    $contactId = $result[0]['id'];

    self::createMail($contactId, $email);

    return $contactId;
  }

  public static function createOrganization(string $organizationName, string $email): int {
    $result = \Civi\Api4\Contact::create(FALSE)
      ->addValue('contact_type', 'Organization')
      ->addValue('organization_name', $organizationName)
      ->execute();

    $contactId = $result[0]['id'];

    self::createMail($contactId, $email);

    return $contactId;
  }

  public static function createMail(int $contactId, string $email) {
    \Civi\Api4\Email::create(FALSE)
      ->addValue('contact_id', $contactId)
      ->addValue('email', $email)
      ->addValue('location_type_id', 3) // primary
      ->addValue('is_primary', TRUE)
      ->execute();
  }

  public static function createPhone(int $contactId, string $phoneNumber, bool $forceAdd) {
    if (empty($phoneNumber)) {
      return;
    }

    if (!$forceAdd) {
      if (self::existsPhone($contactId)) {
        return;
      }
    }

    \Civi\Api4\Phone::create(FALSE)
      ->addValue('contact_id', $contactId)
      ->addValue('phone', $phoneNumber)
      ->addValue('phone_type_id', 6)
      ->addValue('location_type_id', 3)
      ->execute();
  }

  public static function createAddress(int $contactId, ?string $streetAddress, ?string $city, ?string $postalCode, ?string $countryName, bool $forceAdd) {
    if (empty($postalCode)) {
      return;
    }

    if (!$forceAdd) {
      if (self::existsAddress($contactId)) {
        return;
      }
    }

    \Civi\Api4\Address::create(FALSE)
      ->addValue('contact_id', $contactId)
      ->addValue('location_type_id', 3) // primary
      ->addValue('street_address', $streetAddress)
      ->addValue('city', $city)
      ->addValue('postal_code', $postalCode)
      ->addValue('country_id', self::getCountryId($countryName))
      ->execute();
  }

  private static function getCountryId(string $countryName): ?int {
    if (empty($countryName)) {
      return 1076; // France
    }

    switch ($countryName) {
      case 'Andorre': return 1005;
      case 'Émirats Arabes Unis': return 1225;
      case 'Afghanistan': return 1001;
      case 'Antigua-et-Barbuda': return 1009;
      case 'Anguilla': return 1007;
      case 'Albanie': return 1002;
      case 'Arménie': return 1011;
      case 'Angola': return 1006;
      case 'Antarctique': return 1008;
      case 'Argentine': return 1010;
      case 'Samoa américaines': return 1004;
      case 'Autriche': return 1014;
      case 'Australie': return 1013;
      case 'Aruba': return 1012;
      case 'Îles Åland': return 1241;
      case 'Azerbaïdjan': return 1015;
      case 'Bosnie-Herzégovine': return 1026;
      case 'Barbade': return 1018;
      case 'Bangladesh': return 1017;
      case 'Belgique': return 1020;
      case 'Burkina Faso': return 1034;
      case 'Bulgarie': return 1033;
      case 'Bahreïn': return 1016;
      case 'Burundi': return 1036;
      case 'Bénin': return 1022;
      case 'Saint-Barthélemy': return 1252;
      case 'Bermudes': return 1023;
      case 'Brunei Darussalam': return 1032;
      case 'Bolivie': return 1025;
      case 'Brésil': return 1029;
      case 'Bahamas': return 1212;
      case 'Bhoutan': return 1024;
      case 'Île Bouvet': return 1028;
      case 'Botswana': return 1027;
      case 'Biélorussie': return 1019;
      case 'Belize': return 1021;
      case 'Canada': return 1039;
      case 'Îles Cocos': return 1047;
      case 'République démocratique du Congo': return 1050;
      case 'République centrafricaine': return 1042;
      case 'République du Congo': return 1051;
      case 'Suisse': return 1205;
      case 'Côte d’Ivoire': return 1054;
      case 'Îles Cook': return 1052;
      case 'Chili': return 1044;
      case 'Cameroun': return 1038;
      case 'Chine': return 1045;
      case 'Colombie': return 1048;
      case 'Costa Rica': return 1053;
      case 'Cuba': return 1056;
      case 'Cap-Vert': return 1040;
      case 'Île Christmas': return 1046;
      case 'Chypre': return 1057;
      case 'République tchèque': return 1058;
      case 'Allemagne': return 1082;
      case 'Djibouti': return 1060;
      case 'Danemark': return 1059;
      case 'Dominique': return 1061;
      case 'République dominicaine': return 1062;
      case 'Algérie': return 1003;
      case 'Équateur': return 1064;
      case 'Estonie': return 1069;
      case 'Égypte': return 1065;
      case 'Sahara occidental': return 1236;
      case 'Érythrée': return 1068;
      case 'Espagne': return 1198;
      case 'Éthiopie': return 1070;
      case 'Finlande': return 1075;
      case 'Fidji': return 1074;
      case 'Îles Falkland': return 1072;
      case 'Micronésie': return 1141;
      case 'Îles Féroé': return 1073;
      case 'France': return 1076;
      case 'Gabon': return 1080;
      case 'Royaume-Uni': return 1226;
      case 'Grenade': return 1087;
      case 'Géorgie': return 1081;
      case 'Guyane française': return 1077;
      case 'Guernesey': return 1245;
      case 'Ghana': return 1083;
      case 'Gibraltar': return 1084;
      case 'Groenland': return 1086;
      case 'Gambie': return 1213;
      case 'Guinée': return 1091;
      case 'Guadeloupe': return 1088;
      case 'Guinée équatoriale': return 1067;
      case 'Grèce': return 1085;
      case 'Géorgie du Sud et les îles Sandwich du Sud': return 1197;
      case 'Guatemala': return 1090;
      case 'Guam': return 1089;
      case 'Guinée-Bissau': return 1092;
      case 'Guyane': return 1093;
      case 'Hong Kong': return 1098;
      case 'Îles Heard-et-MacDonald': return 1095;
      case 'Honduras': return 1097;
      case 'Croatie': return 1055;
      case 'Haïti': return 1094;
      case 'Hongrie': return 1099;
      case 'Indonésie': return 1102;
      case 'Irlande': return 1105;
      case 'Israël': return 1106;
      case 'Ile de Man': return 1246;
      case 'Inde': return 1101;
      case 'Territoire britannique de l’Océan Indien': return 1030;
      case 'Irak': return 1104;
      case 'Iran': return 1103;
      case 'Islande': return 1100;
      case 'Italie': return 1107;
      case 'Jersey': return 1244;
      case 'Jamaïque': return 1108;
      case 'Jordanie': return 1110;
      case 'Japon': return 1109;
      case 'Kenya': return 1112;
      case 'Kirghizistan': return 1117;
      case 'Cambodge': return 1037;
      case 'Kiribati': return 1113;
      case 'Comores': return 1049;
      case 'Saint-Kitts-et-Nevis': return 1181;
      case 'Corée du Nord': return 1114;
      case 'Corée du Sud': return 1115;
      case 'Koweït': return 1116;
      case 'Iles Cayman': return 1041;
      case 'Kazakhstan': return 1111;
      case 'Laos': return 1118;
      case 'Liban': return 1120;
      case 'Sainte-Lucie': return 1182;
      case 'Liechtenstein': return 1124;
      case 'Sri Lanka': return 1199;
      case 'Libéria': return 1122;
      case 'Lesotho': return 1121;
      case 'Lituanie': return 1125;
      case 'Luxembourg': return 1126;
      case 'Lettonie': return 1119;
      case 'Libye': return 1123;
      case 'Maroc': return 1146;
      case 'Monaco': return 1143;
      case 'Moldavie': return 1142;
      case 'Monténégro': return 1243;
      case 'Saint-Martin (partie française)': return 1253;
      case 'Madagascar': return 1129;
      case 'Îles Marshall': return 1135;
      case 'Macédoine': return 1128;
      case 'Mali': return 1133;
      case 'Myanmar': return 1035;
      case 'Mongolie': return 1144;
      case 'Macao': return 1127;
      case 'Îles Mariannes du Nord': return 1160;
      case 'Martinique': return 1136;
      case 'Mauritanie': return 1137;
      case 'Montserrat': return 1145;
      case 'Malte': return 1134;
      case 'Maurice': return 1138;
      case 'Maldives': return 1132;
      case 'Malawi': return 1130;
      case 'Mexique': return 1140;
      case 'Malaisie': return 1131;
      case 'Mozambique': return 1147;
      case 'Namibie': return 1148;
      case 'Nouvelle-Calédonie': return 1153;
      case 'Niger': return 1156;
      case 'Île Norfolk': return 1159;
      case 'Nigeria': return 1157;
      case 'Nicaragua': return 1155;
      case 'Pays-Bas': return 1152;
      case 'Norvège': return 1161;
      case 'Népal': return 1150;
      case 'Nauru': return 1149;
      case 'Niue': return 1158;
      case 'Nouvelle-Zélande': return 1154;
      case 'Oman': return 1162;
      case 'Panama': return 1166;
      case 'Pérou': return 1169;
      case 'Polynésie française': return 1078;
      case 'Papouasie-Nouvelle-Guinée': return 1167;
      case 'Philippines': return 1170;
      case 'Pakistan': return 1163;
      case 'Pologne': return 1172;
      case 'Saint-Pierre-et-Miquelon': return 1183;
      case 'Pitcairn': return 1171;
      case 'Puerto Rico': return 1174;
      case 'Palestine': return 1165;
      case 'Portugal': return 1173;
      case 'Palau': return 1164;
      case 'Paraguay': return 1168;
      case 'Qatar': return 1175;
      case 'Réunion': return 1179;
      case 'Roumanie': return 1176;
      case 'Serbie': return 1242;
      case 'Russie': return 1177;
      case 'Rwanda': return 1178;
      case 'Arabie Saoudite': return 1187;
      case 'Îles Salomon': return 1194;
      case 'Seychelles': return 1189;
      case 'Soudan': return 1200;
      case 'Suède': return 1204;
      case 'Singapour': return 1191;
      case 'Sainte-Hélène': return 1180;
      case 'Slovénie': return 1193;
      case 'Svalbard et Jan Mayen': return 1202;
      case 'Slovaquie': return 1192;
      case 'Sierra Leone': return 1190;
      case 'Saint-Marin': return 1186;
      case 'Sénégal': return 1188;
      case 'Somalie': return 1195;
      case 'Suriname': return 1201;
      case 'Sud-Soudan': return 1247;
      case 'Sao Tomé-et-Principe': return 1207;
      case 'Salvador': return 1066;
      case 'Saint-Martin (partie néerlandaise)': return 1249;
      case 'Syrie': return 1206;
      case 'Eswatini': return 1203;
      case 'Îles Turques-et-Caïques': return 1221;
      case 'Tchad': return 1043;
      case 'Terres australes et antarctiques françaises': return 1079;
      case 'Togo': return 1214;
      case 'Thaïlande': return 1211;
      case 'Tadjikistan': return 1209;
      case 'Tokelau': return 1215;
      case 'Timor-Leste': return 1063;
      case 'Turkménistan': return 1220;
      case 'Tunisie': return 1218;
      case 'Tonga': return 1216;
      case 'Turquie': return 1219;
      case 'Trinité-et-Tobago': return 1217;
      case 'Tuvalu': return 1222;
      case 'Taiwan': return 1208;
      case 'Tanzanie': return 1210;
      case 'Ukraine': return 1224;
      case 'Ouganda': return 1223;
      case 'Îles mineures éloignées des États-Unis': return 1227;
      case 'États-Unis': return 1228;
      case 'Uruguay': return 1229;
      case 'Ouzbékistan': return 1230;
      case 'Saint-Siège (Vatican)': return 1096;
      case 'Saint-Vincent-et-les Grenadines': return 1184;
      case 'Venezuela': return 1232;
      case 'British Virgin Islands': return 1031;
      case 'Îles Vierges américaines': return 1234;
      case 'Viêt Nam': return 1233;
      case 'Vanuatu': return 1231;
      case 'Wallis-et-Futuna': return 1235;
      case 'Samoa': return 1185;
      case 'Yémen': return 1237;
      case 'Mayotte': return 1139;
      case 'Afrique du Sud': return 1196;
      case 'Zambie': return 1239;
      case 'Zimbabwe': return 1240;

      default:
        return NULL;
    }
  }

  private static function existsAddress(int $contactId) {
    $result = \Civi\Api4\Address::get(FALSE)
      ->addWhere('contact_id', '=', $contactId)
      ->execute();

    if ($result) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private static function existsPhone(int $contactId) {
    $result = \Civi\Api4\Phone::get(FALSE)
      ->addWhere('contact_id', '=', $contactId)
      ->execute();

    if ($result) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
