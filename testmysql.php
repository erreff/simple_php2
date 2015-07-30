<html>
	<head>
		<title>Test de connexion PHP/MySQL</title>
	</head>
	<body style="FONT-FAMILY: Arial" >
		<span style="FONT-WEIGHT: bold">Page de test de connectivit&eacute; PHP / MySQL</span>
		<HR width="100%" SIZE="2" />
		<DIV style="FONT-SIZE: smaller;">
		V1.2 : Possibilit&eacute; d'appel de la page via GET<BR>
		Exemple : testmysql?s=127.0.0.1&p=3306&u=test&pw=password&m=2<BR>
		</DIV>
		<HR width="100%" SIZE="2" />
		<form action="/testmysql.php" method="post">
			<DIV style="BACKGROUND-COLOR: #ccccff">
			<TABLE id="TableInput" cellSpacing="1" cellPadding="1" width="380" border="0"  >
				<TR>
					<TD>Serveur MySQL (s)</TD>
					<TD><INPUT id="inputSrv" type="text" value="<?php if (count($_POST)!= 0 ){echo $_POST['inputSrv'];} else {echo getenv('DATABASE_SERVICE_HOST') ;} ?>" name="inputSrv"></TD>
				</TR>
				<TR>
					<TD>Port (p)</TD>
					<TD><INPUT id="inputPort" type="text" value="<?php if (count($_POST)!= 0 ){echo $_POST['inputPort'];} else {echo getenv('DATABASE_SERVICE_PORT') ;} ?>" name="inputPort"></TD>
				</TR>
				<TR>
					<TD>Utilisateur (u)</TD>
					<TD><INPUT id="inputUser" type="text" value="<?php if (count($_POST)!= 0 ){echo $_POST['inputUser'];} else {echo getenv('MYSQL_USER') ;} ?>" name="inputUser"></TD>
				</TR>
				<TR>
					<TD>Mot de passe (pw)</TD>
					<TD><INPUT id="inputPwd" type="password" name="inputPwd" value="<?php if (count($_POST)!= 0 ){echo $_POST['inputPwd'];} else {echo getenv('MYSQL_PASSWORD') ;} ?>" ></TD>
				</TR>
				<TR>
					<TD>M&eacute;thode de connexion (m)</TD>
					<TD><SELECT id="selectMethod" style="WIDTH: 128px" name="selectMethod">
							<OPTION <?php if (count($_POST)== 0 || $_POST['selectMethod'] == 0){echo 'selected' ;} ?> value="0">MySQL (0)</OPTION>
							<OPTION <?php if (count($_POST)!= 0 && $_POST['selectMethod'] == 1){echo 'selected' ;} ?> value="1">MySQLi (1)</OPTION>
							<OPTION <?php if (count($_POST)!= 0 && $_POST['selectMethod'] == 2){echo 'selected' ;} ?> value="2">PDO (2)</OPTION>
						</SELECT></TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT id="Submit1" type="submit" value="Tester" name="Submit1"></TD>
				</TR>
			</TABLE>
			</DIV>
		</form>
		
<?php
	
	class testCnxMySQL
	{
		const SQL_QUERY = 'SELECT @@version';
		const METHOD_MYSQL = 0;
		const METHOD_MYSQLI = 1;
		const METHOD_PDO = 2;
		
		
		private function lireVersionViaMySQL($serveur,$port,$utilisateur,$motdepasse,$base,& $strRetour)
		{
			$dsn= $serveur . ':' . $port ;
			// Connexion
			$link = mysql_connect($dsn, $utilisateur, $motdepasse);
			if (!$link) {
				$strRetour = mysql_error();
				return false;
			}
			// Changement de base
			if (!is_null($base)) {
				if (!mysql_select_db($base)) {
					$strRetour = mysql_error($link);
					return false;
				}
			}
			// Execution
			$result = mysql_query(self::SQL_QUERY);
			if (!$result) {
				$strRetour = mysql_error($link);
				return false;
			}
			$row = mysql_fetch_row($result);
			$strRetour=$row[0];
			
			mysql_free_result($result);
			return true;
		}
		
		private function lireVersionViaMySQLi($serveur,$port,$utilisateur,$motdepasse,$base,& $strRetour)
		{
			
			// Connexion
			$mysqli = new mysqli($serveur, $utilisateur,$motdepasse,$base,$port);
			if (mysqli_connect_error()) {
				$strRetour=mysqli_connect_error();
				return false;
			}
			/* Requête "Select" retourne un jeu de résultats */
			if ($result = $mysqli->query(self::SQL_QUERY)) {
			
				$row = $result->fetch_row();
				// Affectation du retour
				$strRetour=$row[0];
				
				/* Libération du jeu de résultats */
				$result->free();
			}
			// Fermeture connexion
			$mysqli->close();
			return true;
		}
		
		private function lireVersionViaPDO($serveur,$port,$utilisateur,$motdepasse,$base,& $strRetour)
		{
			try {
				// Connexion
				$str_cnx = "mysql:host=" . $serveur . ";port=" . $port ;
				if (!is_null($base)) {
					$str_cnx = $str_cnx . ";dbname=" . $base;
				}
				$db_conn = new PDO($str_cnx, $utilisateur, $motdepasse );
				// Preparation de le requete
				$pdo_stmt = $db_conn->prepare(self::SQL_QUERY);
				// Execution de la requete
				$pdo_stmt->execute();
				// Traitement du retour
				$row = $pdo_stmt->fetch(PDO::FETCH_NUM);
				// Affectation du retour
				$strRetour=$row[0];
				return true;
			}
			catch (PDOException $e) {
				// Gestion des exceptions
				$strRetour=$e->getMessage();
				return false;
			}
		}
		
		function lireVersion($serveur,$port,$utilisateur,$motdepasse,$base,$methode,& $strRetour)
		{
			switch ($methode) {
				case self::METHOD_MYSQL:
					return $this->lireVersionViaMySQL($serveur,$port,$utilisateur,$motdepasse,$base,$strRetour);
					break;
				case self::METHOD_MYSQLI:
					return $this->lireVersionViaMySQLi($serveur,$port,$utilisateur,$motdepasse,$base,$strRetour);
					break;
				case self::METHOD_PDO:
					return $this->lireVersionViaPDO($serveur,$port,$utilisateur,$motdepasse,$base,$strRetour);
					break;
			}
		}
	}
	
	// Initialisation des variables
	$db_host = NULL;
	$db_port = NULL;
	$db_user = NULL;
	$db_pwd = NULL;
	
	$url_get = NULL;
	
	$count = count($_POST) + count($_GET);
	if ($count == 0 ) 
	{
		die();
	}
	
	if (count($_POST) != 0 ) 
	{
	/* Recuperation des variables via POST */
		$db_host = $_POST['inputSrv'];
		$db_port = $_POST['inputPort'];
		$db_user = $_POST['inputUser'];
		$db_pwd = $_POST['inputPwd'];
		$method = $_POST['selectMethod'];
		
		$url_get = "testmysql?s=".$db_host."&p=".$db_port."&u=".$db_user."&pw=*****&m=".$method;
		
	} else {
	/* Recuperation des variables via GET */
		if(isset($_GET['s'])) {
			$db_host = $_GET['s'];
		} else {
			$db_host = "127.0.0.1";
		}
		if(isset($_GET['p'])) {
			$db_port = $_GET['p'];
		} else {
			$db_port ="3306";
		}
		if(isset($_GET['u'])) {
			$db_user = $_GET['u'];
		} else {
			$db_user ="root";
		}
		if(isset($_GET['pw'])) {
			$db_pwd = $_GET['pw'];
		} else {
			$db_pwd ="";
		}
		if(isset($_GET['m'])) {
			$method = $_GET['m'];
		} else {
			$method = 0;
		}
	}
	/* $database = 'mysql'; */
	$database = NULL ;
	$ch_retour='';
	
	switch ($method) {
		case 0:
			$lib_method="MySQL";
			break;
		case 1:
			$lib_method="MySQLi";
			break;
		case 2:
			$lib_method="PDO";
			break;
	}
	
	echo 'Param&egrave;tres : Serveur ' . $db_host . ' - Port ' . $db_port . ' - Utilisateur ' . $db_user . ' - M&eacute;thode ' . $lib_method . '<BR>';
	if(isset($url_get)) {	
		echo '<DIV style="FONT-SIZE: smaller;">';
		echo "URL GET correspondante :" . $url_get;
		echo '</BR></DIV>';
	}
	$oTest = new testCnxMySQL();
	if ( $oTest->lireVersion($db_host,$db_port,$db_user,$db_pwd,$database,$method,$ch_retour) )
	{
		echo '<span style="FONT-WEIGHT: bold; COLOR: green">[OK] Test de connexion r&eacute;ussi : version retourn&eacute;e ' . $ch_retour . '</span>';
	}
	else {
		echo '<span style="FONT-WEIGHT: bold; COLOR: red">[KO] Test de connexion &eacute;chou&eacute; : ' . $ch_retour . '</span>';
	}
?>
	</body>
</html>