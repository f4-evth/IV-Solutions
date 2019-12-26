<?php

	/*
	Сделать веб-страничку запроса курса USD. На странице — форма. В форме поле даты и кнопка "запросить"
	После отправки формы скрипт ищет курс на эту дату в локальной БД. Если его нет — идёт в API ЦБ РФ https://www.cbr.ru/development/SXML/ и вытаскивает курс доллара. Потом сохраняет в локальную БД и выводит пользователю.
	Пришлите ссылку на репозиторий с решением 
	*/

	function sanitizeInput()
	{
		// Check the input format
		if( strlen( $_POST['date'] ) == 10 )
		{
			sscanf( $_POST['date'], "%d-%d-%d", $year, $month, $day );
			
			// Check if the values are withing the acceptable ranges
			if( ( $year > 1970 && $year <= 2019 ) &&  ( $month < 13 )
				&& ( $day <= 31 - ( $month % 2 ) ) ) 
			{
				return array($year,$month,$day);
			}
			else return "Sorry, the date you entered is outside of acceptable range";
		}
		
		// Data sent is unformatted
		return false;
	}

	// Singleton-DBConnector
	//  to avoid passing $pdo in every call
	function connectDB()
	{
		static $pdo = NULL;
		if( $pdo === NULL )
		{
			$host = "localhost";
			$user = "wf";
			$pass = "ThisIsThe1";
			$db   = "testing";
			$charset = "utf8";
			$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
			$pdo  = new PDO($dsn,$user,$pass);
		}
		
		return $pdo;
	}
	
	// Create table, so that the following queries won't fail
	function prepareDB()
	{
		$pdo  = connectDB();
		$sql  = "CREATE TABLE IF NOT EXISTS testing.valutes (";
		$sql .= "year INTEGER NOT NULL,";
		$sql .= "month INTEGER NOT NULL,";
		$sql .= "day INTEGER NOT NULL,";
		$sql .= "value TEXT NOT NULL";
		$sql .= ")";
		$sql = $pdo->query( $sql );
	}
	
	function insertDB( $params, $result )
	{
		$pdo = connectDB();
		$sql = "INSERT INTO testing.valutes (year,month,day,value) VALUES(?,?,?,?)";		
		$stmt = $pdo->prepare( $sql );
		$params[3] = $result;
		$stmt->execute( $params );
	}
		
	// Try to get value from DB
	function fetchDB( $yearMonthDay )
	{
		$pdo = connectDB();
		
		if( $pdo )
		{
			prepareDB( $pdo );
			
			$sql  = "SELECT value FROM testing.valutes WHERE year = ? AND month = ? AND day = ?; ";
			$stmt = $pdo->prepare( $sql );
			$stmt->execute( $yearMonthDay );

			// Returns either Int or NULL if not found
			return $stmt->fetch()[0];
		}
		
		// Can't connect to DB - fallback to API
		return false;
	}
	
	function fetchAPI( $yearMonthDay )
	{
		// Prepare an API url
		$api_url = "http://www.cbr.ru/scripts/XML_daily.asp?date_req=%02d/%02d/%02d";
		$api_url = sprintf( $api_url, $yearMonthDay[2], $yearMonthDay[1], $yearMonthDay[0]	);

		// Try to fetch an XML document
		$xml = file_get_contents( $api_url );
		if( $xml )
		{
			// Try to parse XML records to find USD node
			$xml = simplexml_load_string( $xml );
			if( $xml )
			foreach( $xml->children() as $valute )
			{
				// Workaround for locale-specific comma/dot issue
				if( $valute->CharCode == 'USD' )
					return str_replace( ',', '.', $valute->Value );
			}
			return "Error: Unable to parse API XML response.";
		}
		else return "Error: Unable to connect to API.";
	}
	
	function getUSDConversionRate() : string
	{
		$yearMonthDay = sanitizeInput();
		
		if( is_array( $yearMonthDay ) )
		{
			// Try fetching from DB
			$result = fetchDB( $yearMonthDay );
			
			// If not found
			if( $result === NULL )
			{
				// Try fetching from API
				$result = fetchAPI( $yearMonthDay );
			
				// If successful - add to local DB
				if( is_numeric( $result ) )
					insertDB( $yearMonthDay, $result );	
			}
		}
		
		// Error happened during input parsing
		elseif( is_string( $yearMonthDay ) ) 
				$result = $yearMonthDay;
				
		// Nothing has been passed
		else $result = "Please, type in date in yyyy-mm-dd format";

		// Return either the result or the error message
		return $result;
	}
	
	function printRate()
	{
		if( $_POST['date'] )
			echo "At ".$_POST['date']." 1 USD costed ".getUSDConversionRate()." rubles";
	}
?>
<script src="ajax_upload.js"></script>
<form name="da" method="POST" action="some.php">
 <fieldset>
  <legend>Request USD conversion rate:</legend>
  <pre><?=printRate()?></pre>
  <input type="date"
   autofocus 
   autocomplete 
   required 
   name="date" 
   placeholder="YYYY-MM-DD"
   value="<?=$_POST['date'];?>" 
   min="1970-01-01"
   max="<?=date("o")?>-12-12"
   pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}"
   />
  <input type="submit" value="Get" />
 </fieldset>
</form>
