<?php
namespace App\Helpers;
use Config;
class Hana{
  public static function query($sql){
    $connect = odbc_connect(
        "Driver=".Config::get('database.connections.hana.driver').
        ";ServerNode=".Config::get('database.connections.hana.server').':'.Config::get('database.connections.hana.port').
        ";Database=".Config::get('database.connections.hana.database').
        ";CHAR_AS_UTF8=true",
        Config::get('database.connections.hana.username'),
        Config::get('database.connections.hana.password'),
        SQL_CUR_USE_ODBC
    );
    if (!($connect)){
        return "Falló la conexión a la base de datos a través de ODBC:";
      }else{
          $result = odbc_exec($connect, $sql);
          $data = array();
          while ($row = odbc_fetch_array($result)) {
             array_push($data, self::HandleFixEncoding($row));
           }
          return $data;
      }
  }
  private static function HandleFixEncoding($row){
    return array_map(function ($value) {
        if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
            return mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        }
        return $value;
    }, $row);
  }
}
