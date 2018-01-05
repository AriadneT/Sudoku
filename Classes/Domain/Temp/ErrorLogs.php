<?php
/**
 * Error log currently for database errors only.
 */
class ErrorLog
{
	/**
     * date of error
     *
     * @var string
     */
	private $dateOfError = '';

	/**
     * error message
     *
     * @var string
     */
	private $errorMessage = '';
	
	/**
     * Returns the date of error
     *
     * @return string $dateOfError
     */
    public function getDateOfError()
    {
        return $this->dateOfError;
    }

    /**
     * Sets the date of error
     *
     * @return void
     */
    public function setDateOfError()
    {
        $this->dateOfError = date("d M Y");
    }
	
	/**
     * Returns the error message
     *
     * @return string $errorMessage
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
	
	/**
	 * To instantiate a new error log
     */
    public function __construct()
	{
	}

    /**
     * Sets the error message
     *
     * @param string $date
	 * @param PDOException $connectionException
     * @return void
     */
    public function setErrorMessage($connectionException, $dateOfError)
    {
		// Adding PHP_EOL results in a new line after the message
        $this->errorMessage = 
			'Database error: ' . $connectionException->getMessage() . '. ' . $dateOfError . PHP_EOL;
    }
	
	/**
	 * @param PDOException $connectionException
	 * @return void
	 */
	public function logError($connectionException)
	{
		$this->setDateOfError();
		$this->setErrorMessage($connectionException, $this->dateOfError);
		
		/*
		 * For security reasons, error logs are excluded in production mode, so
		 * the log is placed in a temporary file.
		 */

		// Record error message in temporary file in append mode, then close file
		$openedErrorLog = fopen('Temp/Logs.log', 'a');
		fwrite($openedErrorLog, $this->errorMessage);
		fclose($openedErrorLog);
	}
}

