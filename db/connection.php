<?
	function connect() {
		$servername = "localhost";
		$username = "root";
		$password = "";
		$db_name = "fight_test";

		// Create connection
		$connection = new mysqli($servername, $username, $password, $db_name);

		// Check connection
		if ($connection->connect_error) {
			die("Connection failed: " . $connection->connect_error);
		}
		// echo "Connected successfully";
		return $connection;
	}

	function disconnect($connection) {
		$connection->close();
	}
?>