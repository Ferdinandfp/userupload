<?php
/* This script will read a CSV file containing a list of users with first, last name and
 * email address. The names will be formated to lower case and first letter uppercase.
 * The email address will be checked for the correct format and the records will be rejected if
 * the format is incorrect. The valid users will be inserted into a database into a 'users' table.
 * The table will be created or rebuild if required.
 * Directives --create_table: The table with the name 'users' will be created, if the table exists
 * an option will let you rebuild the table loosing all data or abort the table creation. If the --create_table
 * is used together with the --file directive and the table creation/rebult is aborted no data will be inserted
 * into the database.
 * Directive --file: If the 'users' table does not exist and the --create_table directive is not used the user
 * will be informend that the table does not exist and the option is provided to create it. If no, the table will
 * not be created and no users are inserted into the database.
 * Note: using the --help optoin will precede all other options.
 * Help/directives for user_upload.ph
 * --file [csv name] - the name of the csv file to be parsed
 * --create_table - this will cause the MuSQL users table to be build (and no further action will be taken)
 * --dry_run - this will be used with the --file directive... test run no data will be added to the database
 * --help - output the directives with details
 * -u - MySQL username
 * -p - MySQL password
 * -h - MySQL host name
 * -d - MySQL database name - optional, default is 'dbusers'
*/
//Create constants for long options for possible, multiple use.
define("OPTIONFILE", "file");
define("OPTIONHELP", "help");
define("OPTIONCREATETABLE", "create_table");
define("OPTIONDRYRUN", "dry_run");
$issetDryRun = 0;
$fileName = "";
$dbUser = "";
$dbPassword = "";
$dbHost = "";
$dbName = "dbusers";
$tableName = "users";
$dbConnection;
$dbRecords = "";
$dbRejets = "";
$scvData = "";
//Create variable/array for the directives/options where ':' donates a required value with the option
$shortoptions = "";
$shortoptions .= "u:";
$shortoptions .= "p:";
$shortoptions .= "h:";
$shortoptions .= "d:";
//
$longoptions  = array(
    "file:",
    "create_table",
    "dry_run",
    "help"
);
$options = getopt($shortoptions, $longoptions);
//Process the short options
foreach(array_keys($options) as $option) switch ($option){
    case 'u':
        $dbUser = $options['u'];
        break;
    case 'p':
        $dbPassword = $options['p'];
        break;
    case 'h':
        $dbHost = $options['h'];
        break;
    case 'd':
        $dbName = $options['d'];
        break;
    default:
        //Default switch
}
//Catch the --help option, will ignore any other option and show the help information
if(array_key_exists(OPTIONHELP, $options)){
    printHelp();
    exit(1);
}
//Catch the --dry run option and set the variable switch
if(array_key_exists(OPTIONDRYRUN, $options)){
    $issetDryRun = 1;
    //Run script without adding data to DB";
}
//Catch the --create_table option, this will ignore any other options apart help and create the table
if(array_key_exists(OPTIONCREATETABLE, $options)){
    createUsersTable($dbName, $tableName, $dbUser, $dbPassword, $dbHost);
    if(!array_key_exists(OPTIONFILE, $options))exit(1);
}
//Catch the --file option and set the variable filename
if(array_key_exists(OPTIONFILE, $options)){
    $fileName = $options[OPTIONFILE];
}
//if the database user details and file name are given
if(validateUserDetails($dbUser, $dbPassword, $dbHost) && !empty($fileName)){
    $userResults = getData($fileName);
    updateUsers($userResults, $dbName, $dbUser, $dbPassword, $dbHost, $tableName, $issetDryRun);
}else{
    echo "Please provide the correct user, host and/or input file information.";
}
//--Funtions------------------------------------------------------------------------
//Check if a specific table exits and rebuilt the table if required
function createUsersTable($dbname, $tablename, $username, $password, $hostname){
    if(validateUserDetails($username, $password, $hostname)){
        createTable($dbname, $tablename, $username, $password, $hostname);
    }else{
        echo "Please provide the correct database user and host information.";
    }
}
//Create the users tabel if the Db connection is successful
function createTable($dbname, $tablename, $username, $password, $hostname){
    $dbConnection = mysqli_connect($hostname, $username, $password, $dbname);
    if (!$dbConnection) {
        die("Connection failed: " . mysqli_connect_error());
        return false;
    }else{
        $sql = "CREATE TABLE " . $tablename . " (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            firstname VARCHAR(30) NOT NULL,
            surname VARCHAR(30) NOT NULL,
            email VARCHAR(50) UNIQUE,
            reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            if (mysqli_query($dbConnection, $sql)) {
                echo "\nTable " .$tablename . " created successfully.\n";
            } else {
                echo "Error creating table: " . mysqli_error($dbConnection);
                if(mysqli_errno($dbConnection) == 1050){
                    echo "\nWould you like to rebuild the table?\n";
                    echo "You will loose all data:  Type 'y' to rebuild: ";
                    $handle = fopen ("php://stdin","r");
                    $line = fgets($handle);
                    if(trim($line) != 'y'){
                        echo "Aborting creating the table!\n";
                        exit(1);
                    }
                    //Drop the table and create a new table
                    $sql_drop_table = "DROP TABLE IF EXISTS " . $tablename;
                    if (mysqli_query($dbConnection, $sql_drop_table)) {
                        //success
                        if (mysqli_query($dbConnection, $sql)) {
                            echo "Table " .$tablename . " created successfully.";
                        } else {
                            echo "Error creating table: " . mysqli_error($dbConnection);
                            exit(1);
                        }
                    }else{
                        echo "Something went wrong, could not drop the table. ";
                    }
                }
            }
    }
    mysqli_close($dbConnection);
}
//Insert the user records into the data-base
function updateUsers($userRecords, $dbname, $username, $password, $hostname, $tablename, $issetdryrun){
    //If the email is already in the system the record will be rejected - email unique value
    $duplicateRedordFound = false;
    $successRecordCount = 0;
    $successMessage = "record has";
    $dbConnection = mysqli_connect($hostname, $username, $password, $dbname);
    if($issetdryrun == 1){
        //Setting up for a test run only
        mysqli_autocommit($dbConnection,FALSE);
    }
    if (!$dbConnection) {
        die("Connection failed: " . mysqli_connect_error());
        return false;
    }else{
        //Check if a table exists
        $exists = mysqli_query($dbConnection, "SELECT 1 from users");
        if(!$exists){
            echo("\nThe table 'users' doesn't exist.");
            echo "\nWould you like to create the table?\n";
            echo "Type 'y' to create the table: \n";
            $handle = fopen ("php://stdin","r");
            $line = fgets($handle);
            if(trim($line) != 'y'){
                echo "\nAborting creating the table!\nNo data has been added to the database.";
                exit(1);
            }else{
                createTable($dbname, $tablename, $username, $password, $hostname);
            }
        }
        $duplicateRecords = "\n\nFollowing users could not be added to the Database\nDuplicate email address:\n";
        foreach($userRecords as $record){
            $sql = "INSERT INTO users (firstname, surname, email) VALUES $record";
            if (mysqli_query($dbConnection, $sql)) {
                //New record created successfully;
                $successRecordCount++;
            } else {
                //Error: was not possible to add this record - dupicate email address;
                $duplicateRecords = $duplicateRecords . $record . "\n";
                $duplicateRedordFound = true;
            }
        }
    }
    //Print out the list of records addeed to the database and duplicate records not added.
    if($successRecordCount > 1 )$successMessage = "records have";
    if($successRecordCount > 0)echo "\n" . $successRecordCount . " " . $successMessage . " been added to the database";
    if($duplicateRedordFound)echo $duplicateRecords;
    if($issetdryrun == 1){
        // Rollback transaction
        mysqli_rollback($dbConnection);
        echo "Dry run was set: This was a successful test run and no data has been added to the database.";
    }
    mysqli_close($dbConnection);
}
//Validate the db user details input
function validateUserDetails($username, $password, $hostname){
    $validated = true;
    if(empty($username) || empty($password) || empty($hostname)){
        $validated = false;
    }
    return $validated;
}
/* Read in the csv file and process the data.
 * The csv format must have header files which will be used to count the columns
 * and are further ignored when processing the user data. 
 * The deliminator must be a comma, ",".
 */
function getData($filename){
    if (file_exists($filename) && ($scvData = file($filename))!==false ) {
        $scvData = processCsv('users.csv');
        $scvData[0] = chop($scvData[0]);
        $countRow = count($scvData);
        $countCol = count($scvData[0]);
        $dbrecordsarray = array();
        for($a=1; $a<$countRow; $a++) {
            $firstname = addslashes(trim(ucfirst(strtolower($scvData[$a][0]))));
            $lastname = addslashes(trim(ucfirst(strtolower($scvData[$a][1]))));
            $email = addslashes(trim(strtolower($scvData[$a][2])));
            $email = addslashes(filter_var($email, FILTER_SANITIZE_EMAIL));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $record = "('" . $firstname . "', '" . $lastname . "', '" . $email . "')";
                array_push($dbrecordsarray, $record);
            } else {
                $dbrejets = $dbrejets . $firstname . "," . $lastname . "," . $email . "\r\n";
            }
        }
    }else{
        echo "Please provide a valid file name and path. \nThe file could not be found or could not open the file.";
    }
    fclose($scvData);
    echo "\n\nFollowing records were rejeted - invalid emal format:\n" . $dbrejets;
    return $dbrecordsarray;
}
//initial process of the user data to put all data into arrays.
function processCsv($url) {
    $deliminator = ",";
    $csvdata = file($url);
    $csvdata[0] = chop($csvdata[0]);
    $headerdata = explode($deliminator,$csvdata[0]);
    $headercount = count($headerdata);
    $i=0;
    foreach($csvdata as $item) {
        $item = chop($item);
            $csv_data = explode($deliminator,$item);
            for ($y=0; $y<$headercount; $y++) {
                $result[$i][$y] = $csv_data[$y];
            }
        $i++;
    }
    return $result;
 }
function printHelp(){
    echo "\n";
    $mask = "%-6s %-40s\n";
    echo sprintf($mask, "Usage:", "user_upload.php --help");
    echo sprintf($mask, "", "user_upload.php --create_table -u [user] -p [password] -h [host]");
    echo sprintf($mask, "", "user_upload.php --file [csv name] -u [user] -p [password] -h [host]");
    echo sprintf($mask, "", "user_upload.php --file [csv name] -u [user] -p [password] -h [host] --dry_run");
    $mask = "%1s %-17s %-40s\n";
    echo "\n";
    echo sprintf($mask, "", "--file [csv name]", "The name of the csv file to be parsed");
    echo sprintf($mask, "", "--create_table", "This will cause the MySQL users table to be build");
    echo sprintf($mask, "", "", "(and no further action will be taken)");
    echo sprintf($mask, "", "--dry_run", "this will be used with the --file directive");
    echo sprintf($mask, "", "", "test run and no data will be added to the database");
    echo sprintf($mask, "", "--help", "Shows this help file");
    echo sprintf($mask, "", "-u", "MySQL user name");
    echo sprintf($mask, "", "-p", "MySQL user password");
    echo sprintf($mask, "", "-h", "MySQL host name");
    echo sprintf($mask, "", "-d", "MySQL database name - optional, default is 'dbusers'");
}