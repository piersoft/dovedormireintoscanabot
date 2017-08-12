<?php
/**
* Telegram Bot example for dovedormireintoscanabot
* @author Francesco Piero Paolicelli @piersoft
*/
//include("settings_t.php");
include("Telegram.php");

class mainloop{
const MAX_LENGTH = 4096;
function start($telegram,$update)
{

	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");

	$text = $update["message"] ["text"];
	$chat_id = $update["message"] ["chat"]["id"];
	$user_id=$update["message"]["from"]["id"];
	$location=$update["message"]["location"];
	$reply_to_msg=$update["message"]["reply_to_message"];

	$this->shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg);
	$db = NULL;

}

 function shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg)
{
	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");
	if (strpos($text,'@dovedormireinsicilia_bot') !== false) $text=str_replace("@dovedormireinsicilia_bot ","",$text);

	if ($text == "/start" || $text == "©️ Informazioni") {
		$img = curl_file_create('logo.png','image/png');
		$contentp = array('chat_id' => $chat_id, 'photo' => $img);
		$telegram->sendPhoto($contentp);
		$reply = "Benvenuto. Per ricercare una struttura ricettiva della Regione Toscana, censita dal Dipartimento regionale del turismo dello sport e dello spettacolo, digita il nome del Comune oppure clicca sulla graffetta (📎) e poi 'posizione' . Puoi anche ricercare per parola chiave nel titolo anteponendo il carattere ?. Verrà interrogato il DataBase openData utilizzabile con licenza CC-BY presente su http://dati.toscana.it/dataset/rt-strutric. In qualsiasi momento scrivendo /start ti ripeterò questo messaggio di benvenuto.\nQuesto bot è stato realizzato da @piersoft e potete migliorare il codice sorgente con licenza MIT che trovate su https://github.com/piersoft/dovedormireinsiciliabot.\nLa propria posizione viene ricercata grazie al geocoder di openStreetMap con Lic. odbl.\n";
		$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$log=$today. ",new chat started," .$chat_id. "\n";
		file_put_contents('/usr/www/piersoft/dovedormireintoscanabot/db/telegram.log', $log, FILE_APPEND | LOCK_EX);
		$this->create_keyboard_temp($telegram,$chat_id);
		exit;
	}elseif ($text == "/location" || $text == "🌐 Posizione") {

			$option = array(array($telegram->buildKeyboardButton("Invia la tua posizione / send your location", false, true)) //this work
			);
		// Create a permanent custom keyboard
		$keyb = $telegram->buildKeyBoard($option, $onetime=false);
		$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "Attiva la localizzazione sul tuo smartphone / Turn on your GPS");
		$log=$today. ",sendpositiondirect," .$chat_id. "\n";
		file_put_contents('/usr/www/piersoft/dovedormireintoscanabot/db/telegram.log', $log, FILE_APPEND | LOCK_EX);

			$this->create_keyboard_temp($telegram,$chat_id);
		exit;
		}
		elseif ($text == "🏛 Città") {
			$reply = "Digita direttamente il nome del Comune dove cerchi una struttura ricettiva.\nEsempio: <b>Cecina</b>";
			$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true,'parse_mode'=>"HTML");
			$telegram->sendMessage($content);
			$log=$today. ",cityinfo," .$chat_id. "\n";
				$this->create_keyboard_temp($telegram,$chat_id);
			exit;
			}
			elseif ($text == "❓ Ricerca") {
				$reply = "Scrivi la parola da cercare anteponendo il carattere ?\nEsempio: <b>?cantino</b>";
				$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true,'parse_mode'=>"HTML");
				$telegram->sendMessage($content);
				$log=$today. ",ricercainfo," .$chat_id. "\n";
				file_put_contents('/usr/www/piersoft/dovedormireintoscanabot/db/telegram.log', $log, FILE_APPEND | LOCK_EX);

					$this->create_keyboard_temp($telegram,$chat_id);
				exit;
			}
			elseif($location!=null)
		{

			$this->location_manager($telegram,$user_id,$chat_id,$location);
			exit;
		}

		elseif(strpos($text,'/') === false){
			$string=0;
$all=0;
$string="";
if(strpos($text,'!') !== false) {
	$all="1";
	$string="<b>TUTTE</b> ";
	$text=str_replace("!","",$text);
}
			if(strpos($text,'?') !== false){
				$text=str_replace("?","",$text);
				$location="Sto cercando ".$string."le strutture aventi nel titolo <b>".$text."</b>";
				$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true,'parse_mode'=>"HTML");
				$telegram->sendMessage($content);
				$string=1;
	//			sleep (1);
			}else{
				$location="Sto cercando ".$string."le strutture ricettive a <b>".ucfirst($text)."</b>";
				$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true,'parse_mode'=>"HTML");
				$telegram->sendMessage($content);
				$string=0;
		//		sleep (1);
			}

			$homepage="";
$json_string=file_get_contents("/usr/www/piersoft/dovedormireintoscanabot/db/ricettive.json");

			$parsed_json = json_decode($json_string);

$ciclo=0;

foreach ($parsed_json->{'features'} as $i => $value) {

	if ($string==0){
		$filter=$parsed_json->{'features'}[$i]->{'attributes'}->{'GeogAreaName'};

	}else{
		$filter=$parsed_json->{'features'}[$i]->{'attributes'}->{'AccomDesc'};

	}



if (strpos(strtoupper($filter),strtoupper($text)) !== false ){
				$ciclo++;

				$homepage = "Nome: <b>".$parsed_json->{'features'}[$i]->{'attributes'}->{'AccomDesc'}."</b>\n";
				if ($string!=0) $homepage .= "Località: <b>".$parsed_json->{'features'}[$i]->{'attributes'}->{'GeogAreaName'}."</b> (".$parsed_json->{'features'}[$i]->{'attributes'}->{'Provincia'}.")\n";
				$homepage .= "Tipologia: <b>".$parsed_json->{'features'}[$i]->{'attributes'}->{'Tipology'}."</b>\n";
				$homepage .= "Clicca per dettagli: /".$parsed_json->{'features'}[$i]->{'attributes'}->{'ESRI_OID'}."\n";
				$homepage .="____________";
				$chunks = str_split($homepage, self::MAX_LENGTH);
				foreach($chunks as $chunk) {
				$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true,'parse_mode'=>"HTML");
				$telegram->sendMessage($content);

				}
				}
				if ($ciclo>=20 && $all==0){
					$location="Troppe strutture per questa ricerca, ti ho mostrato le prime 20.\nSe proprio vuoi averle tutte <b>(potrebbero essere centinaia ATTENZIONE!!)</b>, allora digita la località anteponendo il carattere !.\nEsempio !cecina";
					$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true,'parse_mode'=>"HTML");
					$telegram->sendMessage($content);
				$this->create_keyboard_temp($telegram,$chat_id);
					exit;
				}
				}

				if ($ciclo==0){
					$location="Nessuna struttura trovata";
					$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
				}

		$log=$today. ",".$text.",".$chat_id. "\n";
		file_put_contents('/usr/www/piersoft/dovedormireintoscanabot/db/telegram.log', $log, FILE_APPEND | LOCK_EX);

			$this->create_keyboard_temp($telegram,$chat_id);
exit;
	}else{


		$text=str_replace("/","",$text);
		$location="Sto cercando i dettagli per la struttura ".$text;
		$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$homepage="";
$json_string=file_get_contents("/usr/www/piersoft/dovedormireintoscanabot/db/ricettive.json");

		$parsed_json = json_decode($json_string);

$ciclo=0;

foreach ($parsed_json->{'features'} as $i => $value) {

	$filter=$parsed_json->{'features'}[$i]->{'attributes'}->{'ESRI_OID'};

if ($filter==$text ){
	$ciclo++;
	$homepage = "Nome: <b>".$parsed_json->{'features'}[$i]->{'attributes'}->{'AccomDesc'}."</b>\n";
	$homepage .= "Tipologia: <b>".$parsed_json->{'features'}[$i]->{'attributes'}->{'Tipology'}."</b>\n";
		if ($parsed_json->{'features'}[$i]->{'attributes'}->{'Classification'} !=null) $homepage .= "Classificazione (stelle): ".$parsed_json->{'features'}[$i]->{'attributes'}->{'Classification'}."\n";
		$homepage .= "Località: ".$parsed_json->{'features'}[$i]->{'attributes'}->{'GeogAreaName'}." (".$parsed_json->{'features'}[$i]->{'attributes'}->{'Provincia'}.")\n";
		if ($parsed_json->{'features'}[$i]->{'attributes'}->{'Website'} !=null) $homepage .= "Website: ".$parsed_json->{'features'}[$i]->{'attributes'}->{'Website'}."\n";
		if ($parsed_json->{'features'}[$i]->{'attributes'}->{'Tel'} !=null) $homepage .= "Tel: ".$parsed_json->{'features'}[$i]->{'attributes'}->{'Tel'}."\n";
		if ($parsed_json->{'features'}[$i]->{'attributes'}->{'E_Mail'} !=null) $homepage .= "E_Mail: ".$parsed_json->{'features'}[$i]->{'attributes'}->{'E_Mail'}."\n";
		//$homepage .= "ID: /".$parsed_json->{'features'}[$i]->{'attributes'}->{'ESRI_OID'}."\n";

				if($parsed_json->{'features'}[$i]->{'geometry'}->{'x'} !=NULL){
			//	$homepage .="Mappa:\n";
				$homepagemappa .= "http://www.openstreetmap.org/?mlat=".$parsed_json->{'features'}[$i]->{'geometry'}->{'y'}."&mlon=".$parsed_json->{'features'}[$i]->{'geometry'}->{'x'}."#map=19/".$parsed_json->{'features'}[$i]->{'geometry'}->{'y'}."/".$parsed_json->{'features'}[$i]->{'geometry'}->{'x'};

				$option = array( array( $telegram->buildInlineKeyboardButton("MAPPA", $url=$homepagemappa)));
				$keyb = $telegram->buildInlineKeyBoard($option);
				$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "<b>Vai alla</b>",'parse_mode'=>"HTML");
				$telegram->sendMessage($content);
			}

			$homepage .="\n____________";


}
}
if ($ciclo==0){
	$location="Nessuna struttura trovata";
	$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
	$telegram->sendMessage($content);
}

	$chunks = str_split($homepage, self::MAX_LENGTH);
	foreach($chunks as $chunk) {
	$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true,'parse_mode'=>"HTML");
	$telegram->sendMessage($content);

	}
	$log=$today. ",".$text.",".$chat_id. "\n";
	file_put_contents('/usr/www/piersoft/dovedormireintoscanabot/db/telegram.log', $log, FILE_APPEND | LOCK_EX);

	$this->create_keyboard_temp($telegram,$chat_id);
	exit;
	}

	}

	function create_keyboard_temp($telegram, $chat_id)
	 {
			 $option = array(["🏛 Città","❓ Ricerca"],["©️ Informazioni"]);
			 $keyb = $telegram->buildKeyBoard($option, $onetime=false);
			 $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "[Digita un Comune, fai una Ricerca (?) oppure invia la tua posizione tramite la graffetta (📎)]");
			 $telegram->sendMessage($content);
	 }



function location_manager($telegram,$user_id,$chat_id,$location)
	{

			$lon=$location["longitude"];
			$lat=$location["latitude"];
			$r=1;
			$response=$telegram->getData();
			$response=str_replace(" ","%20",$response);

				$reply="http://nominatim.openstreetmap.org/reverse?email=piersoft2@gmail.com&format=json&lat=".$lat."&lon=".$lon."&zoom=18&addressdetails=1";
				$json_string = file_get_contents($reply);
				$parsed_json = json_decode($json_string);
				//var_dump($parsed_json);
				$comune="";
				$temp_c1 =$parsed_json->{'display_name'};

				if ($parsed_json->{'address'}->{'town'}) {
					$temp_c1 .="\nCittà: ".$parsed_json->{'address'}->{'town'};
					$comune .=$parsed_json->{'address'}->{'town'};
				}else 	$comune .=$parsed_json->{'address'}->{'city'};

				if ($parsed_json->{'address'}->{'village'}) $comune .=$parsed_json->{'address'}->{'village'};
				$location="Sto cercando le strutture ricettive a \"".ucfirst($comune)."\" ";
				// tramite le coordinate che hai inviato: ".$lat.",".$lon;
				$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);

			  $alert="";
				$homepage="";
	$json_string=file_get_contents("/usr/www/piersoft/dovedormireintoscanabot/db/ricettive.json");

				$parsed_json = json_decode($json_string);

	$ciclo=0;

			$result=0;
$csv=[];
			$ciclo=0;
//if ($count >40) $count=40;
foreach ($parsed_json->{'features'} as $i => $value) {
	$filter=$parsed_json->{'features'}[$i]->{'attributes'}->{'GeogAreaName'};


if (strpos(strtoupper($filter),strtoupper($comune)) !== false ){
$ciclo++;
		$lat10=floatval($parsed_json->{'features'}[$i]->{'geometry'}->{'y'});
		$long10=floatval($parsed_json->{'features'}[$i]->{'geometry'}->{'x'});
		$theta = floatval($lon)-floatval($long10);
		$dist =floatval( sin(deg2rad($lat)) * sin(deg2rad($lat10)) +  cos(deg2rad($lat)) * cos(deg2rad($lat10)) * cos(deg2rad($theta)));
		$dist = floatval(acos($dist));
		$dist = floatval(rad2deg($dist));
		$miles = floatval($dist * 60 * 1.1515 * 1.609344);
	//echo $miles;

		if ($miles >=1){
			$data1 =number_format($miles, 2, '.', '');
			$data =number_format($miles, 2, '.', '')." Km";
		} else {
			$data =number_format(($miles*1000), 0, '.', '')." mt";
			$data1 =number_format(($miles*1000), 0, '.', '');
		}
		$csv[$i][100]= array("distance" => "value");

		$csv[$i][100]= $data;
		$csv[$i][101]= array("AccomDesc" => "value");

		$csv[$i][101]= $parsed_json->{'features'}[$i]->{'attributes'}->{'AccomDesc'};

		$csv[$i][102]= array("Tipology" => "value");

		$csv[$i][102]= $parsed_json->{'features'}[$i]->{'attributes'}->{'Tipology'};

		$csv[$i][103]= array("ESRI_OID" => "value");

		$csv[$i][103]= $parsed_json->{'features'}[$i]->{'attributes'}->{'ESRI_OID'};

		$csv[$i][104]= array("lon" => "value");

		$csv[$i][104]= $parsed_json->{'features'}[$i]->{'geometry'}->{'y'};
		$csv[$i][105]= array("lat" => "value");

		$csv[$i][105]= $parsed_json->{'features'}[$i]->{'geometry'}->{'x'};

}
}

sort($csv);

$ciclo2=0;
foreach ($csv as $i => $value) {

$ciclo2++;
		$homepage = "Nome: <b>".$csv[$i][101]."</b>\n";
		$homepage .= "Tipologia: <b>".$csv[$i][102]."</b>\n";
		$homepage .= "Clicca per dettagli: /".$csv[$i][103]."\n";
		$homepage .="Dista: ".$csv[$i][100]."\n";
		//	$homepage .= "http://www.openstreetmap.org/?mlat=".$csv[$i][12]."&mlon=".$csv[$i][13]."#map=19/".$csv[$i][12]."/".$csv[$i][13];
		$location2 ="http://map.project-osrm.org/?z=14&center=40.351025%2C18.184133&loc=".$lat."%2C".$lon."&loc=".$csv[$i][104]."%2C".$csv[$i][105]."&hl=en&ly=&alt=&df=&srv=";
		$homepage .="<a href='".$location2."'>Portami QUI</a>";


		$homepage .="\n____________";
		$chunks = str_split($homepage, self::MAX_LENGTH);
		foreach($chunks as $chunk) {
		$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true,'parse_mode'=>"HTML");
		$telegram->sendMessage($content);

		}
		if ($ciclo2>=30){
			$location="Ti ho mostrato le prime 30 distanti in maniera crescente dalla tua posizione.";
			$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true,'parse_mode'=>"HTML");
			$telegram->sendMessage($content);
		$this->create_keyboard_temp($telegram,$chat_id);
			exit;
		}

						}



						if ($ciclo2==0){
							$location="Nessuna struttura trovata";
							$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
							$telegram->sendMessage($content);
						}

				$log=$today. ",".$comune."," .$chat_id. "\n";
				file_put_contents('/usr/www/piersoft/dovedormireintoscanabot/db/telegram.log', $log, FILE_APPEND | LOCK_EX);

					$this->create_keyboard_temp($telegram,$chat_id);
					exit;
	}


}

?>