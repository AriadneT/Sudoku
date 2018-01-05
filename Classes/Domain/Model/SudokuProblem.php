<?php
class SudokuProblem
{
	/**
     * Initial sudoku array
     *
     * @var array
     */
	private $sudokuArray = [];
	
	/**
     * File name
     *
     * @var string
     */
	private $fileName = '';
	
	/**
     * Problem id linking the solution to the problem
     *
     * @var int
     */
	private $problemId = -1;
		
	/**
     * Rows, columns and boxes belonging to a sudoku
     *
     * @var array
     */
	private $groupings = [];
			
	/**
     * Returns the initial sudoku array
     *
     * @return array $sudokuArray
     */
    public function getSudokuArray()
    {
        return $this->sudokuArray;
    }

    /**
     * Sets the initial sudoku array
     *
     * @param array $sudokuArray
     * @return void
     */
    public function setSudokuArray($sudokuArray)
    {
        $this->sudokuArray = $sudokuArray;
    }
		
	/**
     * Returns the file name
     *
     * @return string $fileName
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Sets the file name
     *
     * @param string $fileName
     * @return void
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }
	
	/**
     * Returns the problem id
     *
     * @return int $problemId
     */
    public function getProblemId()
    {
        return $this->problemId;
    }

    /**
     * Sets the problem id
     *
     * @param int $problemId
     * @return void
     */
    public function setProblemId($problemId)
    {
		$this->problemId = $problemId;
    }
	
	/**
     * Returns the groupings
     *
     * @return array $groupings
     */
    public function getGroupings()
    {
        return $this->groupings;
    }

    /**
     * Adds a row, column or box to the grouping
     *
     * @param object $group
     * @return void
     */
    public function addGroup($group)
    {
		$this->groupings[] = $group;
    }
	
	/**
	 * To instantiate a new sudoku problem
	 *
	 * @param array $sudokuArray
	 * @param string $fileName
	 * @return object $sudokuProblem
	 */
	public function __construct($sudokuArray, $fileName)
	{
		$this->setSudokuArray($sudokuArray);
		$this->setFileName($fileName);
	}
	
	/**
	 * Save sudoku problem in the database
	 *
	 * @param PDO $databaseConnection
	 * @return int $problemId
	 */
	public function saveSudoku($databaseConnection)
	{
		$insertProblemQuery = 'INSERT INTO problems (name, problem) 
			VALUES (
			"' . $this->fileName . '", "' . implode('', $this->sudokuArray) . '"
			);';
		$databaseConnection->query($insertProblemQuery);
		
		$getProblemIdQuery = 'SELECT problem_id
			FROM problems 
			WHERE problem_id = (
			SELECT MAX(problem_id) FROM problems
			);';
		$getProblemIdStatement = 
            $databaseConnection->query($getProblemIdQuery);
		$problemIdEntry = $getProblemIdStatement->fetchObject();
		$problemId = $problemIdEntry->problem_id;
		
		return $problemId;
	}
	
	/**
	 * Group sudoku values into rows, columns and blocks
	 *
	 * @param array $configurations
	 * @return array $group
	 */
	public function groupValues($configurations)
	{
		$row1 = $row2 = $row3 = $row4 = $row5 = $row6 = $row7 = $row8 = 
			$column1 = $column2 = $column3 = $column4 = $column5 = $column6 = 
			$column7 = $column8 = $column9 = $block1 = $block2 = $block3 = 
			$block4 = $block5 = $block6 = $block7 = $block8 = $block9 = [];			
		$counter = 1;
		
		foreach ($this->getSudokuArray() as $square) {
			// Create arrays of rows
			if ($counter < 10) {
				$row1[] = $square;
			} elseif ($counter > 9 && $counter < 19) {
				$row2[] = $square;
			} elseif ($counter > 18 && $counter < 28) {
				$row3[] = $square;
			} elseif ($counter > 27 && $counter < 37) {
				$row4[] = $square;
			} elseif ($counter > 36 && $counter < 46) {
				$row5[] = $square;
			} elseif ($counter > 45 && $counter < 55) {
				$row6[] = $square;
			} elseif ($counter > 54 && $counter < 64) {
				$row7[] = $square;
			} elseif ($counter > 63 && $counter < 73) {
				$row8[] = $square;
			} else {
				$row9[] = $square;
			}
			
			// Create arrays of columns
			switch($counter % 9) {
				case (1):
					$column1[] = $square;
					break;
				case (2):
					$column2[] = $square;
					break;
				case (3):
					$column3[] = $square;
					break;
				case (4):
					$column4[] = $square;
					break;
				case (5):
					$column5[] = $square;
					break;
				case (6):
					$column6[] = $square;
					break;
				case (7):
					$column7[] = $square;
					break;
				case (8):
					$column8[] = $square;
					break;
				default:
					$column9[] = $square;
			}
			
			// Create arrays of blocks
			if (in_array($counter, $configurations['block1'])) {
				$block1[] = $square;
			} elseif (in_array($counter, $configurations['block2'])) {
				$block2[] = $square;
			} elseif (in_array($counter, $configurations['block3'])) {
				$block3[] = $square;
			} elseif (in_array($counter, $configurations['block4'])) {
				$block4[] = $square;
			} elseif (in_array($counter, $configurations['block5'])) {
				$block5[] = $square;
			} elseif (in_array($counter, $configurations['block6'])) {
				$block6[] = $square;
			} elseif (in_array($counter, $configurations['block7'])) {
				$block7[] = $square;
			} elseif (in_array($counter, $configurations['block8'])) {
				$block8[] = $square;
			} else {
				$block9[] = $square;
			}
			
			$counter++;
			}
			
		$groups = [
			$row1, 
			$row2, 
			$row3, 
			$row4, 
			$row5, 
			$row6, 
			$row7, 
			$row8, 
			$row9, 
			$column1,
			$column2,
			$column3,
			$column4,
			$column5,
			$column6,
			$column7,
			$column8,
			$column9,
			$block1,
			$block2,
			$block3,
			$block4,
			$block5,
			$block6,
			$block7,
			$block8,
			$block9
			];
		
		return $groups;
	}	
	
	/**
	 * Check whether the sudoku has problems or not
	 *
	 * @param array $groups
	 * @return bool $bool
	 */
	public function check($groups)
	{
		$bool = true;
			
		foreach ($groups as $group) {
			$counts = array_count_values($group);
			for ($number = 1; $number < 10; $number++) {
				if (in_array($number, $group)) {
					if ($counts[$number] > 1) {
						$bool = false;
					}
				}
			}
		}
		
		return $bool;
	}
}
