<?php
/* This script will read a CSV file containing a list of users with first, last name and
 * email address. The names will be formated to lower case and first letter uppercase.
 * The email address will be checked for the correct format and the records will be rejected if
 * the fromat is incorrect. The valid users will be inserted into a database with a 'users' table.
 * The table will be created or rebuild if required.
 * Directives table:
 * Note: using the --help opotins will precede all other options.
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
$directivesHelp =  "TBA";
//Create constants for long options for multiple use.
define("OPTIONFILE", "file");
define("OPTIONHELP", "help");
define("OPTIONCREATETABLE", "create_table");
define("OPTIONDRYRUN", "dry_run");
//When processing the directives keep track if the option are used correclty.
$invalidUsage = false;
$issetHelp = false;
$issetCreateTable = false;
$issetDryRun = false;
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
        //echo $dbUser;
        break;
    case 'p':
        $dbPassword = $options['p'];
        //echo $dbPassword;
        break;
    case 'h':
        $dbHost = $options['h'];
        //echo $dbHost;
        break;
    case 'd':
        $dbName = $options['d'];
        //echo $dbName;
        break;
    default:
        //echo "Default switch";
}
//Catch the --help option, will ignore any other option and show the directives help
if(array_key_exists(OPTIONHELP, $options)){
    printHelp();
    exit(1);
}
//Catch the --create_table option, this will ignore any other options apart help and create the table
if(array_key_exists(OPTIONCREATETABLE, $options)){
    $issetCreateTable = true;
    //echo "Create the DB table 'users' and nothing else";
    createUsersTable($dbName, $tableName, $dbUser, $dbPassword, $dbHost);
    if(!array_key_exists(OPTIONFILE, $options))exit(1);
}
//Catch the --dry run option and set the variable switch
if(array_key_exists(OPTIONDRYRUN, $options)){
    $issetDryRun = true;
    //Run script without adding data to DB";
}
//Catch the --dry run option and set the variable switch
if(array_key_exists(OPTIONFILE, $options)){
    $fileName = $options[OPTIONFILE];
}
if(validateUserDetails($dbUser, $dbPassword, $dbHost) && !empty($fileName)){
    $userResults = getData($fileName);
    echo $userResults[0];
    updateUsers($userResults, $dbName, $dbUser, $dbPassword, $dbHost);
}
//--Funtions------------------------------------------------------------------------
//Check if a specific table exits and rebuilt the table if required
function createUsersTable($dbname, $username, $password, $hostname){
    if(validateUserDetails($username, $password, $hostname)){
        createTable($dbname, $username, $password, $hostname);
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
                echo "Table " .$tablename . " created successfully.";
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
                        echo "The table has been rebuild. ";
                        if (mysqli_query($dbConnection, $sql)) {
                            echo "Table " .$tablename . " created successfully.";
                        } else {
                            echo "Error creating table: " . mysqli_error($dbConnection);
                            exit(1);
                        }
                    }else{
                        echo "Something went wrong, could not drop table. ";
                    }
                }
            }
    }
    mysqli_close($dbConnection);
}
//Insert the user records into the data-base
function updateUsers($userRecords, $dbname, $username, $password, $hostname){
    //If the email is already in the system the record will be rejected - email unique value
    $duplicateRedordFound = false;
    $dbConnection = mysqli_connect($hostname, $username, $password, $dbname);
    if (!$dbConnection) {
        die("Connection failed: " . mysqli_connect_error());
        return false;
    }else{
        $duplicateRecords = "Following user(s) could not be added to the Database\nDuplicate email address:\n";
        foreach($userRecords as $record){
            $sql = "INSERT INTO users (firstname, surname, email) VALUES $record";
            if (mysqli_query($dbConnection, $sql)) {
                //New record created successfully;
            } else {
                //Error: was not possible to add this record - dupicate email address;
                $duplicateRecords = $duplicateRecords . $record . "\n";
                $duplicateRedordFound = true;
            }
        }
    }
    //Print out the list of duplicate records
    if($duplicateRedordFound)echo $duplicateRecords;
    mysqli_close($dbConnection);
}
//Validate the db user details input
function validateUserDetails($username, $password, $hostname){
    $validated = true;
    if(empty($username) || empty($password) || empty($hostname)){
        echo "Please provide the correct database user and host information.";
        $validated = false;
    }
    return $validated;
}
//read in the csv file and process the data.
/* The csv format must have header files which will be used to count the columns
 * and are further ignored when processing the user data. 
 * The deliminator must be a coma, ",".
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
                //$dbrecords = $dbrecords . "('" . $firstname . "', '" . $lastname . "', '" . $email . "'),";
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
    //echo "\nEnd of getData.\n\n";
    //echo "Records: " . $dbrecordsarray[1];
    echo "\n\nFollowing records were rejeted - invalid emal format:\n" . $dbrejets;
    //Insert the valid user records into the database
    //$dbrecords = substr($dbrecords, 0, -1);
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