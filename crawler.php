<?php

//config
$mailto = ""; //An wen sollen die Mails geschickt werden?

$username = ""; //LogIn von MG
$passwd = ""; //Passwort von MG

//Datenbankverbindungsdaten
$db_nutzer = "";
$db_db = "";
$db_pw = "";
$db_host = "";

// Konfiguration Ende

$db = mysqli_connect($db_host, $db_nutzer, $db_pw, $db_db);

$maillinks = '';

function execute($ho, $state, $array, $newsess) {
	$log_hoster = $array;
	$ch = curl_init();

	$url = $log_hoster[$ho][0];
	$postdata = $log_hoster[$ho][1];
	$ref = $log_hoster[$ho][2];

	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_COOKIESESSION, $newsess);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
	curl_setopt($ch, CURLOPT_REFERER, $ref);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
	$retu = curl_exec($ch);
	sleep(1);
	return $retu;
}

function getsite($zielurl, $postdata = "", $ref = "", $newsess = true) {
	$log_hoster = array(
		array($zielurl, $postdata, $ref),
	);
	$temp = execute(0, true, $log_hoster, $newsess);
	return $temp;
}

function crawlit() {
	global $seite, $db, $maillinks;

	preg_match_all('/href=\"\/clip\/[a-z0-9\-]*\"/', $seite, $matches, PREG_OFFSET_CAPTURE);

	foreach ($matches[0] as $value) {
		$link = "https://massengeschmack.tv" . trim(str_replace(array('href="', '"'), '', $value[0]));

		$sql = "select * from tbl_links where link='" . $link . "'";
		$result = mysqli_query($db, $sql);
		if (mysqli_num_rows($result) == 0) {
			$sql = "insert into tbl_links (link) values ('" . $link . "')";
			mysqli_query($db, $sql);

			$maillinks .= $link . "\n";
		}
	}
}

//einlogen
$URL = "https://massengeschmack.tv/index_login.php";
$seite = getsite($URL, "email=" . urlencode($username) . "&password=" . urlencode($passwd));
crawlit();
preg_match_all("/\"\/mag\/[0-9a-zA-Z?=-]*\"/", $seite, $cats, PREG_OFFSET_CAPTURE);
$cats = array_unique($cats);

foreach ($cats[0] as $value) {
	$seite = getsite("https://massengeschmack.tv" . str_replace("\"", "", $value[0]));
	crawlit();
}

if ($maillinks != '') {
	mail($mailto, "neue MG Links", $maillinks);
}