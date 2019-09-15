<?php
/* This script will read a CSV file containing a list of users with first, last name and
 * email address. The names will be formated to lower case and first letter uppercase.
 * The email address will be checked for the correct format and the records will be rejected if
 * the fromat is incorrect. The valid users will be inserted into a database with a 'users' table.
 * The table will be created or rebuild if required.
 * Directives table:
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