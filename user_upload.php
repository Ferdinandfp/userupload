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
 * --dry_run - this will be used with the --file directive... not data will be added to the database
 * --help - output the directives with details
 * -u - MySQL username
 * -p - MySQL password
 * -h - MySQL host name
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
$db_user = "";
$db_password = "";
$db_host = "";
//Create variable/array for the directives/options where ':' donates a required value with the option
$shortoptions = "";
$shortoptions .= "u:";
$shortoptions .= "p:";
$shortoptions .= "h:";
//
$longoptions  = array(
    "file:",
    "create_table",
    "dry_run",
    "help"
);
$options = getopt($shortoptions, $longoptions);
//Catch the --help option, will ignore any other option and show the directives help
if(array_key_exists(OPTIONHELP, $options)){
    //TODO: Implement the Help output
    echo "Print help";
    exit(1);
}
//Catch the --create_table option, this will ignore any other options apart help and create the table
if(array_key_exists(OPTIONCREATETABLE, $options)){
    echo "Create the DB table 'users' and nothing else";
    //createTable/createUsersTable();
    exit(1);
}
//Catch the --dry run option and set the variable switch
if(array_key_exists(OPTIONDRYRUN, $options)){
    $issetDryRun = true;
    echo "Run script without adding data to DB";
}
//Catch the --dry run option and set the variable switch
if(array_key_exists(OPTIONFILE, $options)){
    $fileName = $options[OPTIONFILE];
    echo "Read the file: " . $fileName;
}
//Process the short options
foreach(array_keys($options) as $option) switch ($option){
    case 'u':
        $db_user = $options['u'];
        echo $db_user;
        break;
    case 'p':
        $db_password = $options['p'];
        echo $db_password;
        break;
    case 'h':
        $db_host = $options['h'];
        echo $db_host;
        break;
    default:
        echo "Default switch";
}
echo "\r\n=======End options=============\r\n";
echo "Dry Run: " . $issetDryRun;
//TODO:: Implement the validation of the imput options
function validate_option($opt){
    //Can't do the validation here, value is required and will not 
    //fall through here as the option is not picked up.
    //Validation will need to be done after all options have been read in.
    if(is_string($opt) && strlen($opt) > 0){
        echo $opt . ": validated";
    }else{
        echo "Value not supplied: Exit";
        //exit(1);
    }
}