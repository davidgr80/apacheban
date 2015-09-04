#!/usr/bin/php5
<?php

$serverStatus = "http://localhost/server-status";
//$serverStatus = "server-status.html";
$limiteConexiones =  array(
	'R' => 150,
	'W' => 150,
);

$nombreConexiones = array(
	'_' => "Waiting for Connection",
	'S' => "Starting up",
	'R' => "Reading Request",
	'W' => "Sending Reply",
	'K' => "Keepalive (read)",
	'D' => "DNS Lookup",
	'C' => "Closing connection",
	'L' => "Logging",
	'G' => "Gracefully finishing",
	'I' => "Idle cleanup of worker",
	'.' => "Open slot with no current process",
);

$delay = 0;
while (true) {

$status = file($serverStatus);
foreach($limiteConexiones as $tipo => $limite) {
  $ips = array();
  foreach($status as $numLinea => $conLinea)
    if(preg_match("/^[<][t][r][>].*[>][" . $tipo . "][<].*$/",$conLinea)) {   // Busca las lineas que sean del tipo R o W
      preg_match("/(([0-9]{3}|[0-9]{2}|[0-9])\.){3}([0-9]{3}|[0-9]{2}|[0-9])/",$status[$numLinea+2], $ip);   // Extrae la IP de la conexion
      preg_match_all("#<td>(.*)<\/td>#sU",$status[$numLinea], $pid);   // Extrae el PID de la conexion
      preg_match_all("#<td>(.*)<\/td>#sU",$status[$numLinea+1], $tiempo);   // Extrae el tiempo de la conexion
      $datos[] = array($ip[0],$pid[1][1],$tiempo[1][1]);   // Creo un array de arrays q contiene IP,PID,tiempo de cada conexion
      $ips[] = $ip[0];  // Creo un array que contiene solo las IPs
    }
  $resumenIp = array_count_values($ips);   // Genera un array con la cantidad de conexiones por IP
  arsort($resumenIp);    // Ordena por cantidad de conexiones (de mayor a menor)
  print_r($resumenIp);   // Imprime el resumen de IPs y cantidad de conexiones
  echo "Total de conexiones en estado \"" . $nombreConexiones[$tipo] . ": " . count($ips) . "\n";
  $cmdkill = "/bin/kill -9 ";

  if(count($resumenIp))   // Continua solo si hay conexiones activas
    if($resumenIp[key($resumenIp)] > $limite) {
      foreach($datos as $key => $dato) {
	      if($dato[0] == key($resumenIp) AND $dato[2] > 120) {
	        $cmdkill = $cmdkill . $dato[1] . " ";
	      }
      }
      $delay++;
      echo "La IP: " . key($resumenIp) . " tiene " . $resumenIp[key($resumenIp)] . " conexiones de tipo " . $nombreConexiones[$tipo] . "\n";
      exec("/usr/bin/bloquearip.sh " . key($resumenIp));
      if($delay > 10) {
	exec($cmdkill);
        $delay = 0;
      }
//      exec ("echo `date` - La IP: " . key($resumenIp) . " tiene " . $resumenIp[key($resumenIp)] . " conexiones de tipo " . $nombreConexiones[$tipo] . ". >> /tmp/baneoslog");
//      exec($cmdkill);
    }
  unset($ips);
  unset($datos);
  unset($pids);
}
echo "======================================\n";
sleep(2);
}
?>

