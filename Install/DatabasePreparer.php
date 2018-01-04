<?php
/**
 * @param array $configurations
 * @return PDO $databaseConnection
 */
function prepareDatabase($configurations)
{
    // Create and configure initial connection to MySQL
    $databaseConnection = connectToMySQL($configurations);

    createDatabase($databaseConnection);

    useDatabase($databaseConnection);

    createProblemsTable($databaseConnection);

    createSolutionsTable($databaseConnection);

	/*
	 * Tables prevent for rows, columns and units originally planned but not  
	 * implemented because of the amount of effort (on the PHP side) or
	 * insertions required for the individual units. In addition the position 
	 * of each unit/square can be calculated from the problem and solution 
	 * fields in the above two tables.
	 */

    return $databaseConnection;
}

/**
 * @param array $configurations
 * @return PDO $databaseConnection
 */
function connectToMySQL($configurations)
{
    /*
	 * User is 'root', and password is non-existent. Normally a user as non-
	 * administrator would be created in the back-end and a complex password 
	 * would be set to protect the database, but these steps are skipped here 
	 * so that the test application would be quicker to view.
	 */
	$databaseConnection = new PDO('mysql:host=' . $configurations['databaseParameters']['host'] .
        ';dbname=' . $configurations['databaseParameters']['databaseName'],
        $configurations['databaseParameters']['user'],
        $configurations['databaseParameters']['password']);
    // Allows error messages to be written if they occur
    $databaseConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
    return $databaseConnection;
}

/**
 * @param PDO $databaseConnection
 * @return void
 */
function createDatabase($databaseConnection)
{
    $databaseConnection->query('CREATE DATABASE IF NOT EXISTS sudoku;');
}

/**
 * @param PDO $databaseConnection
 * @return void
 */
function useDatabase($databaseConnection)
{
    $databaseConnection->query('USE sudoku;');
}

/**
 * @param PDO $databaseConnection
 * @return void
 */
function createProblemsTable($databaseConnection)
{
    $createProblemsTableQuery = 'CREATE TABLE IF NOT EXISTS problems 
            (
            problem_id INTEGER(7) AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(60),
			problem CHAR(81) NOT NULL
            );';
    $databaseConnection->query($createProblemsTableQuery);
}

/**
 * @param PDO $databaseConnection
 * @return void
 */
function createSolutionsTable($databaseConnection)
{
    $createSolutionsTableQuery = 'CREATE TABLE IF NOT EXISTS solutions
            (
            solution_id INTEGER(7) AUTO_INCREMENT PRIMARY KEY, 
			problem_id INTEGER(7) NOT NULL,
			solution CHAR(81) NOT NULL,
            FOREIGN KEY (problem_id) REFERENCES problems(problem_id)
            );';
    $databaseConnection->query($createSolutionsTableQuery);
}
