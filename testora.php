<html>
	<head>
		<title>Test de connexion PHP/Oracle</title>
	</head>
	<body style="FONT-FAMILY: Arial" >
		<span style="FONT-WEIGHT: bold">Page de test de connectivit&eacute; PHP / Oracle</span>
		<HR width="100%" SIZE="2" />
		<DIV style="FONT-SIZE: smaller;">
		V2.0 : Possibilit&eacute; PDO et OCI<BR>
		Exemple : testora?s=tns_oracle&u=login_oracle&pw=password&m=1<BR>
		</DIV>
		<HR width="100%" SIZE="2" />
		<form action="/testora.php" method="post">
			<DIV style="BACKGROUND-COLOR: #ccccff">
			<TABLE id="TableInput" cellSpacing="1" cellPadding="1" width="420" border="0"  >
				<TR>
					<TD>Entr&eacute;e Oracle (s)</TD>
					<TD><INPUT id="inputSrv" type="text" value="<?php if (count($_POST)!= 0 ){echo $_POST['inputSrv'];} else {echo '(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=' . getenv('OPENSHIFT_ORACLE_DB_HOST') . ')(PORT=' . getenv('OPENSHIFT_ORACLE_DB_PORT') . ')))(CONNECT_DATA=(SERVICE_NAME=' . getenv('OPENSHIFT_ORACLE_DB_SERVICE') . ')))' ;} ?>" name="inputSrv"></TD>
				</TR>
				<TR>
					<TD></TD>
					<TD>de la forme entree dans tnsnames.ora ou chaine au format tnsnames.ora</TD>
				</TR>
				<TR>
					<TD>Utilisateur (u)</TD>
					<TD><INPUT id="inputUser" type="text" value="<?php if (count($_POST)!= 0 ){echo $_POST['inputUser'];} else {echo getenv('OPENSHIFT_ORACLE_DB_USERNAME') ;} ?>" name="inputUser"></TD>
				</TR>
				<TR>
					<TD>Mot de passe (pw)</TD>
					<TD><INPUT id="inputPwd" type="password" name="inputPwd" value="<?php if (count($_POST)!= 0 ){echo $_POST['inputPwd'];} else {echo getenv('OPENSHIFT_ORACLE_DB_PASSWORD') ;} ?>"></TD>
				</TR>
				<TR>
					<TD>M&eacute;thode de connexion</TD>
					<TD>
					<SELECT id="selectMethod" style="WIDTH: 128px" name="selectMethod">
							<OPTION <?php if (count($_POST)== 0 || $_POST['selectMethod'] == 0){echo 'selected' ;} ?> value="0">PDO (0)</OPTION>
							<OPTION <?php if (count($_POST)!= 0 && $_POST['selectMethod'] == 1){echo 'selected' ;} ?> value="1">OCI (1)</OPTION>
						</SELECT>
					</TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT id="Submit1" type="submit" value="Tester" name="Submit1"></TD>
				</TR>
			</TABLE>
			</DIV>
		</form>
		
<?php
	
	class testCnxOracle
	{
		//const SQL_QUERY = 'select * from v$version';
		const SQL_QUERY = 'select banner, instance_name , host_name from  v$version ,  v$instance';
		const METHOD_PDO = 0;
		const METHOD_OCI = 1;
		
		private function lireVersionViaPDO($dbname,$utilisateur,$motdepasse,& $strRetour)
		{
			try {
				// Connexion
				$time_start = microtime(true);

				$db_conn = new PDO("oci:dbname=" . $dbname , $utilisateur, $motdepasse );

				$time_cnx = microtime(true);
				$delay_cnx = $time_cnx - $time_start;

				// Preparation de le requete
				$pdo_stmt = $db_conn->prepare(self::SQL_QUERY);
				// Execution de la requete
				$pdo_stmt->execute();
				// Traitement du retour (1ere ligne)
				$row = $pdo_stmt->fetch(PDO::FETCH_NUM);

				$time_exec = microtime(true);
				$delay_exec = $time_exec - $time_cnx;
				$delay_ora = $time_exec - $time_start;
				
				// Fermeture de connexion
				$db_conn = null;
				
				// Affectation du retour
				$strRetour=$row[0] . '<BR> Instance=  '  .  $row[1] . ' Host=  '  .  $row[2] . ' <BR> Temps : ' . $delay_ora . 's ( ' . $delay_cnx .' / ' . $delay_exec . ' )';
				return true;
			}
			catch (PDOException $e) {
				// Gestion des exceptions
				$strRetour=$e->getMessage();
				if (!$db_conn) {
					$db_conn = null;
				}
				return false;
			}
		}
		
		private function lireVersionViaOCI($dbname,$utilisateur,$motdepasse,& $strRetour)
		{
			try {
				// Connexion
				$time_start = microtime(true);

				$conn = oci_connect($utilisateur, $motdepasse, $dbname);
				if (!$conn) {
					$e = oci_error();
					$strRetour = 'Erreur de connexion : ' . $e['message'];
					return false;
				}
				$time_cnx = microtime(true);
				$delay_cnx = $time_cnx - $time_start;

				// Preparation de le requete
				$stid = oci_parse($conn, self::SQL_QUERY);
				// Execution de la requete
				oci_execute($stid);
				// Traitement du retour (1ere ligne)
				$row = oci_fetch_row($stid);
				
				// Libération de l'identifiant de requête lors de la fermeture de la connexion
				oci_free_statement($stid);
				oci_close($conn);

				$time_exec = microtime(true);
				$delay_exec = $time_exec - $time_cnx;
				$delay_ora = $time_exec - $time_start;

				// Affectation du retour
				$strRetour=$row[0] . '<BR> Instance=  '  .  $row[1] . ' Host=  '  .  $row[2] . ' <BR> Temps : ' . $delay_ora . 's ( ' . $delay_cnx .' / ' . $delay_exec . ' )';
				return true;
			}
			catch (Exception $e) {
				// Gestion des exceptions
				$strRetour=$e->getMessage();
				return false;
			}
		}
		
		function lireVersion($db,$utilisateur,$motdepasse,$methode,& $strRetour)
		{
			if ($methode == self::METHOD_PDO ) {
				return $this->lireVersionViaPDO($db,$utilisateur,$motdepasse,$strRetour);
				}
			else {
				return $this->lireVersionViaOCI($db,$utilisateur,$motdepasse,$strRetour);
			}
		}
	}
	
	// Initialisation des variables
	$db_host = NULL;
	$db_user = NULL;
	$db_pwd = NULL;
	$method =0;
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
		$db_user = $_POST['inputUser'];
		$db_pwd = $_POST['inputPwd'];
		$method = $_POST['selectMethod'];
		
		$url_get = "testora?s=".$db_host."&u=".$db_user."&pw=*****&m=".$method;
		
	} else {
	/* Recuperation des variables via GET */
		if(isset($_GET['s'])) {
			$db_host = $_GET['s'];
		} else {
			$db_host = "tns_oracle";
      $db_host = '(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=' . getenv('OPENSHIFT_ORACLE_DB_HOST') . ')(PORT=' . getenv('OPENSHIFT_ORACLE_DB_PORT') . ')))(CONNECT_DATA=(SERVICE_NAME=' . getenv('OPENSHIFT_ORACLE_DB_SERVICE') . ')))';
		}
		if(isset($_GET['u'])) {
			$db_user = $_GET['u'];
		} else {
			$db_user ="login_oracle";
      $db_user =getenv('OPENSHIFT_ORACLE_DB_USERNAME');
		}
		if(isset($_GET['pw'])) {
			$db_pwd = $_GET['pw'];
		} else {
			$db_pwd ="";
      $db_pwd =getenv('OPENSHIFT_ORACLE_DB_PASSWORD');
		}
		if(isset($_GET['m'])) {
			$method = $_GET['m'];
		} else {
			$method = 1;
		}
	}
	$ch_retour='';
	
	if($method ==0) {
			$lib_method="PDO";
		} else {
			$lib_method="OCI";
		}
	
	
	echo 'Param&egrave;tres : Serveur ' . $db_host . ' - Utilisateur ' . $db_user . ' - M&eacute;thode ' . $lib_method . '<BR>';
	if(isset($url_get)) {	
		echo '<DIV style="FONT-SIZE: smaller;">';
		echo "URL GET correspondante :" . $url_get;
		echo '</BR></DIV>';
	}
	$oTest = new testCnxOracle();
	if ( $oTest->lireVersion($db_host,$db_user,$db_pwd,$method,$ch_retour) )
	{
		echo '<span style="FONT-WEIGHT: bold; COLOR: green">[OK] Test de connexion '. $lib_method .' r&eacute;ussi : version retourn&eacute;e ' . $ch_retour . '</span>';
	}
	else {
		echo '<span style="FONT-WEIGHT: bold; COLOR: red">[KO] Test de connexion &eacute;chou&eacute; : ' . $ch_retour . '</span>';
	}
?>
	</body>
</html>
