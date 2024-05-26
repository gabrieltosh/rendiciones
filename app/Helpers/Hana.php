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
          $result = odbc_exec($connect, utf8_decode($sql));
          $data = array();
          while ($row = odbc_fetch_array($result)) {
             array_push($data, $row);
           }
          return $data;
      }
  }
}
