<?php
namespace App\Helpers;
use Config;
use RuntimeException;
class Hana{
  /**
   * Ejecuta una consulta en SAP HANA por ODBC y devuelve las filas.
   * Si falla la conexión o la consulta lanza una excepción con el motivo: antes
   * devolvía un texto o fallaba en odbc_fetch_array y el error aparecía más
   * adelante con otro mensaje.
   */
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
    if (!$connect) {
        throw new RuntimeException('Falló la conexión a SAP HANA a través de ODBC: ' . odbc_errormsg());
    }

    $result = odbc_exec($connect, utf8_decode($sql));
    if (!$result) {
        $error = odbc_errormsg($connect);
        odbc_close($connect);
        throw new RuntimeException('Falló la consulta a SAP HANA: ' . $error);
    }

    $data = array();
    while ($row = odbc_fetch_array($result)) {
        array_push($data, $row);
    }
    odbc_close($connect);
    return $data;
  }
}
