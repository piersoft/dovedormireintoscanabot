<?php
$homepage="";
$json_string = file_get_contents("http://osservatorioturistico.sicilia.it/arcgis/rest/services/accommodations/accommodations_VISIT_sicily/MapServer/0/query?where=1%3D1&text=&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=*&returnGeometry=true&maxAllowableOffset=&geometryPrecision=&outSR=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&f=pjson");

$parsed_json = json_decode($json_string);
$homepage = "Nome: ".$parsed_json->{'features'}[0]->{'attributes'}->{'AccomDesc'}."\n";
$homepage .= "Tipologia: ".$parsed_json->{'features'}[0]->{'attributes'}->{'Tipology'}."\n";
$homepage .= "Classificazione: ".$parsed_json->{'features'}[0]->{'attributes'}->{'Classification'}."\n";
$homepage .= "Località: ".$parsed_json->{'features'}[0]->{'attributes'}->{'GeogAreaName'}."\n";
$homepage .= "Website: ".$parsed_json->{'features'}[0]->{'attributes'}->{'Website'}."\n";
$homepage .= "Tel: ".$parsed_json->{'features'}[0]->{'attributes'}->{'Tel'}."\n";
$homepage .= "E_Mail: ".$parsed_json->{'features'}[0]->{'attributes'}->{'E_Mail'}."\n";
$homepage .= "Codice: /".$parsed_json->{'features'}[0]->{'attributes'}->{'EntityCod'}."\n";
echo $homepage;
 ?>
