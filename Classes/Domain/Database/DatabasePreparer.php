<?php
class DatabasePreparer
{
    /**
     * Database for the sudoku problems and solutions
     *
     * @var PDO
     */
	private $databaseConnection = null;
    
    /**
     * Returns the database
     *
     * @return PDO $databaseConnection
     */
    public function getDatabaseConnection()
    {
        return $this->databaseConnection;
    }

    /**
     * Sets the database
     *
     * @param PDO $databaseConnection
     * @return void
     */
    public function setDatabaseConnection($databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
    }
    
    /**
	 * To instantiate a new database
	 */
	public function __construct()
	{
	}
    
    /**
     * @param array $configurations
     * @return PDO $databaseConnection
     */
    public function prepareDatabase($configurations)
    {
        // Create and configure initial connection to MySQL
        $databaseConnection = 
            $this->setDatabaseConnection(
                $this->connectToMySQL($configurations)
            );

        $this->createDatabase($this->databaseConnection);

        $this->useDatabase($this->databaseConnection);

        $this->createProblemsTable($this->databaseConnection);

        $this->createSolutionsTable($this->databaseConnection);

        /*
         * Tables prevent for rows, columns and units originally planned but 
         * not implemented because of the amount of effort (on the PHP side) or
         * insertions required for the individual units. In addition the 
         * positionof each unit/square can be calculated from the problem and  
         * solution fields in the above two tables.
         */
    }

    /**
     * @param array $configurations
     * @return PDO $databaseConnection
     */
    public function connectToMySQL($configurations)
    {
        /*
         * User is 'root', and password is non-existent. Normally a non-
         * administrator user would be created in the back-end and a complex 
         * password set to protect the database, but these steps are skipped 
         * so that the test application would be quicker to view.
         */
        $databaseConnection = new PDO(
            'mysql:host=' . $configurations['databaseParameters']['host'] .
            ';dbname=' . $configurations['databaseParameters']['databaseName'],
            $configurations['databaseParameters']['user'],
            $configurations['databaseParameters']['password']
        );
        
        // Allows error messages to be written if they occur
        $databaseConnection->setAttribute(
            PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION
        );
        
        return $databaseConnection;
    }

    /**
     * @param PDO $databaseConnection
     * @return void
     */
    public function createDatabase($databaseConnection)
    {
        $databaseConnection->query('CREATE DATABASE IF NOT EXISTS sudoku;');
    }

    /**
     * @param PDO $databaseConnection
     * @return void
     */
    public function useDatabase($databaseConnection)
    {
        $databaseConnection->query('USE sudoku;');
    }

    /**
     * @param PDO $databaseConnection
     * @return void
     */
    public function createProblemsTable($databaseConnection)
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
    public function createSolutionsTable($databaseConnection)
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
}
