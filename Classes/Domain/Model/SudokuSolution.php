<?php
class SudokuSolution extends SudokuProblem
{
	/**
	 * To instantiate a new sudoku solution
	 *
	 * @param array $sudokuArray
	 * @param int $problemId
	 * @param string $fileName
	 * @return object $sudokuProblem
	 */
	public function __construct($sudokuArray, $problemId, $fileName)
	{
		$this->setSudokuArray($sudokuArray);
		$this->setFileName($fileName);
		$this->setProblemId($problemId);
	}
	
	/**
	 * Save sudoku solution in the database
	 *
	 * @param PDO $databaseConnection
	 * @param int $problemId
	 * @param array $sudokuArray
	 * @return void
	 */
	public function saveSudokuSolution($databaseConnection, $problemId, $sudokuArray)
	{
		$insertSolutionQuery = 'INSERT INTO solutions (problem_id, solution) 
			VALUES (
			' . $problemId . ', "' . implode('', $sudokuArray) . '"
			);';
		$databaseConnection->query($insertSolutionQuery);
	}
}