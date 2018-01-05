<?php
class SudokuProblemSolver
{
	/**
     * Solution
     *
     * @var string
     */
	private $solution = '';
    
    /**
     * Array used for more complicated methods comparing two to four cells with 
     * at least two possible values
     *
     * @var string
     */
    private $analysisArray = [];
	
	/**
     * Returns the solution
     *
     * @return string $solution
     */
    public function getSolution()
    {
        return $this->solution;
    }

    /**
     * Sets the solution
     *
	 * @param string $solution
     * @return void
     */
    public function setSolution($solution)
    {
        $this->solution = $solution;
    }
    
    /**
     * Returns the analysis array
     *
     * @return array $analysisArray
     */
    public function getAnalysisArray()
    {
        return $this->analysisArray;
    }

    /**
     * Sets the analysis array
     *
	 * @param array $analysisArray
     * @return void
     */
    public function setAnalysisArray($analysisArray)
    {
        $this->analysisArray = $analysisArray;
    }
	
	/**
     * Adds the unit number and possible values to the analysis array
     *
	 * @param int $unitNumber
	 * @param array $possibleValues
     * @return void
     */
    public function addToAnalysisArray($unitNumber, $possibleValues)
    {
        array_push(
			$this->analysisArray, 
			['position' => $unitNumber, 'possibilities' => $possibleValues]
		);
    }
	
	/**
	 * To instantiate a new sudoku problem solver
	 *
	 * @return object $sudokuProblemSolver
	 */
	public function __construct()
	{
	}
	
	/**
	 * Takes the sudoku files, solves and saves them and shows the answers
	 *
	 * @return string $solution
	 */
	public function showSolutions()
	{
		// Initial setup
		$configurations = require_once('Configuration/Config.php');
		$phpFiles = $configurations['phpFiles'];
		foreach ($phpFiles as $phpFile) {
			require($phpFile);
		}
		$sudokuFiles = $configurations['sudokuFiles'];
		$sudokuSolutions = $sudokuProblems = [];
		
		try {
            /*
			 * Create connection to MySQL. Set up database and tables if they 
             * are not yet available.
             */
            $database = new DatabasePreparer;
			$database->prepareDatabase($configurations);
            $databaseConnection = $database->getDatabaseConnection();
			
			foreach ($sudokuFiles as $sudokuFile) {
				// Prepare sudoku problems for analysis and saving in database
				$problem = file_get_contents($sudokuFile);
				$name = substr_replace(basename($sudokuFile), '', -7);
				$array = [];
				for ($position = 0; $position < 100; $position++) {
					$component = substr($problem, $position, 1);
					// Avoid recording newlines
					if (in_array(
                        $component, $configurations['validUnitValues']
                    )) {
						$array[] = $component;
					}
				}
				$sudokuProblem = new SudokuProblem($array, $name);
				
				/*
				 * Only save and try to solve the sudoku if it does not have at  
				 * least two of a specific number per row, column and/or block
				 */
				$groups = $sudokuProblem->groupValues($configurations);
				if ($sudokuProblem->check($groups)) {
					$problemId = $sudokuProblem->saveSudoku($databaseConnection);
					$sudokuProblem->setProblemId($problemId);
					$sudokuProblems[] = $sudokuProblem;
					
					// Sudoku solution initially identical to the relevant problem
					$sudokuSolution = new SudokuSolution($array, $problemId, $name);
					$sudokuSolutions[] = $sudokuSolution;
				} else {
					echo $sudokuProblem->getFileName() . ' was incorrectly written.<br>';
				}				
			}
			/*
			 * Sort sudoku problem into rows, columns, boxes and units 
			 * (as objects)
			 */
			$this->sortProblems($sudokuProblems, $configurations);
					
			// Solve the sudoku as much as possible through logic
			$this->solveSudokus($sudokuProblems);
            
            // Test if any unfilled squares still exist? (May be required for harder problems)
            
            // Go systematically through remaining possibilities until sudoku is solved? (May be required for harder problems, especially if some logical methods prove too difficult to program)
					
			// Replace missing values in the sudoku solution's array
			$this->recordSolutions($sudokuProblems, $sudokuSolutions);
			
			// Save the sudoku solutions
			$this->saveSolutions($sudokuSolutions, $databaseConnection);
		} catch (PDOException $connectionException) {
			// Log database error
			$log = new ErrorLog;
			$log->logError($connectionException);
		}
		
		// Show solution(s) (or at least progress towards it/them)
		$this->setSolution($this->writeSolutions($sudokuSolutions));
		
		return $this->solution;
	}
	
	/**
	 * @param array $sudokuSolutions
	 * @return string $solution
	 */
	public function writeSolutions($sudokuSolutions)
	{
		$this->setSolution('<!DOCTYPE html><html><body>');
		foreach ($sudokuSolutions as $sudokuSolution) {
			$this->setSolution(
                $this->getSolution() . '<table><thead>' . 
                $sudokuSolution->getFileName() . '</thead>'
            );
			$counter = 1;
			foreach ($sudokuSolution->getSudokuArray() as $square) {
				if ($counter % 9 == 1) {
					$this->setSolution($this->getSolution() . '<tr>');
				}
				$this->setSolution(
                    $this->getSolution() . '<td>' . $square . '</td>'
                );
				if ($counter % 9 == 0) {
					$this->setSolution($this->getSolution() . '</tr>');
				}
				$counter++;
			}
			
			$this->setSolution($this->getSolution() . '</table><br>');
		}
		$this->setSolution($this->getSolution() . '</body></html>');
		
		return $this->solution;
	}
	
	/**
	 * @param array $sudokuSolutions
	 * @param PDO $databaseConnection
	 * @return void
	 */
	public function saveSolutions($sudokuSolutions, $databaseConnection)
	{
		foreach ($sudokuSolutions as $sudokuSolution) {
			$sudokuSolution->saveSudokuSolution(
				$databaseConnection, 
				$sudokuSolution->getProblemId(), 
				$sudokuSolution->getSudokuArray()
				);
		}
	}
	
	/**
	 * @param array $sudokuProblems
	 * @param array $configurations
	 * @return void
	 */
	public function sortProblems($sudokuProblems, $configurations)
	{
		foreach ($sudokuProblems as $sudokuProblem) {
			for ($index = 1; $index < 10; $index++) {
				$row = new Row($index, []);
				$column = new Column($index, []);
				$block = new Block($index, []);
				$sudokuProblem->addGroup($row);
				$sudokuProblem->addGroup($column);
				$sudokuProblem->addGroup($block);
			}
			
			$counter = 1;
			$groupings = $sudokuProblem->getGroupings();
            
			foreach ($sudokuProblem->getSudokuArray() as $square) {
				$unit = new Unit($counter, $square);
				
				// Fill the rows with the appropriate units
				if ($counter < 10) {
                    $this->includeInGroup($groupings, $unit, 'row', 1);
				} elseif ($counter > 9 && $counter < 19) {
                    $this->includeInGroup($groupings, $unit, 'row', 2);
				} elseif ($counter > 18 && $counter < 28) {
                    $this->includeInGroup($groupings, $unit, 'row', 3);
				} elseif ($counter > 27 && $counter < 37) {
                    $this->includeInGroup($groupings, $unit, 'row', 4);
				} elseif ($counter > 36 && $counter < 46) {
                    $this->includeInGroup($groupings, $unit, 'row', 5);
				} elseif ($counter > 45 && $counter < 55) {
                    $this->includeInGroup($groupings, $unit, 'row', 6);
				} elseif ($counter > 54 && $counter < 64) {
                    $this->includeInGroup($groupings, $unit, 'row', 7);
				} elseif ($counter > 63 && $counter < 73) {
                    $this->includeInGroup($groupings, $unit, 'row', 8);
				} else {
                    $this->includeInGroup($groupings, $unit, 'row', 9);
				}
		
				// Fill the columns with the appropriate units
				switch($counter % 9) {
					case (1):
                        $this->includeInGroup($groupings, $unit, 'column', 1);
						break;
					case (2):
                        $this->includeInGroup($groupings, $unit, 'column', 2);
						break;
					case (3):
                        $this->includeInGroup($groupings, $unit, 'column', 3);
						break;
					case (4):
                        $this->includeInGroup($groupings, $unit, 'column', 4);
						break;
					case (5):
                        $this->includeInGroup($groupings, $unit, 'column', 5);
						break;
					case (6):
                        $this->includeInGroup($groupings, $unit, 'column', 6);
						break;
					case (7):
                        $this->includeInGroup($groupings, $unit, 'column', 7);
						break;
					case (8):
                        $this->includeInGroup($groupings, $unit, 'column', 8);
						break;
					default:
                        $this->includeInGroup($groupings, $unit, 'column', 9);
				}
				
				// Fill the blocks with the appropriate units
				if (in_array($counter, $configurations['block1'])) {
                    $this->includeInGroup($groupings, $unit, 'block', 1);
				} elseif (in_array($counter, $configurations['block2'])) {
                    $this->includeInGroup($groupings, $unit, 'block', 2);
				} elseif (in_array($counter, $configurations['block3'])) {
					$this->includeInGroup($groupings, $unit, 'block', 3);
				} elseif (in_array($counter, $configurations['block4'])) {
					$this->includeInGroup($groupings, $unit, 'block', 4);
				} elseif (in_array($counter, $configurations['block5'])) {
					$this->includeInGroup($groupings, $unit, 'block', 5);
				} elseif (in_array($counter, $configurations['block6'])) {
					$this->includeInGroup($groupings, $unit, 'block', 6);
				} elseif (in_array($counter, $configurations['block7'])) {
					$this->includeInGroup($groupings, $unit, 'block', 7);
				} elseif (in_array($counter, $configurations['block8'])) {
					$this->includeInGroup($groupings, $unit, 'block', 8);
				} else {
                    $this->includeInGroup($groupings, $unit, 'block', 9);
				}
				
				$counter++;
			}
		}
	}
    
	/**
	 * @param array $groupings
	 * @param object $unit
	 * @param string $groupType
	 * @param array $number
	 * @return void
	 */
     public function includeInGroup($groupings, $unit, $groupType, $number)
    {
        foreach ($groupings as $group) {
            if ($group->getGroupType() == $groupType && $group->getNumber() == $number) {
                $group->addMember($unit);
                // Units should contain their row, column and block number
                $unit->setBoxNumber($number);
                break;
            }
        }
    }
	
	/**
	 * @param array $sudokuProblems
	 * @return void
	 */
	public function solveSudokus($sudokuProblems)
	{
		foreach ($sudokuProblems as $sudokuProblem) {
			$progress = true;
			
			/*
			 * If number of possibilities is reduced, progress is made and it 
			 * is worthwhile to repeat the process. Otherwise stop trying to 
			 * reduce possibilities.
			 */
			while ($progress == true) {
				$progress = false;
				$groupings = $sudokuProblem->getGroupings();
				
				foreach ($groupings as $group) {
					$subgroup = $group->getMembers();
					foreach ($subgroup as $unit) {
						/*
						 * If square has a number, remove this number as a 
						 * possibility from other squares in the box/row/
						 * column. This is part of the "Last Possible Number" 
						 * method.
						 */
						if ($unit->getValue() != ' ') {
							$value = $unit->getValue();
							foreach ($subgroup as $unit) {
								$possibleValues = $unit->getPossibleValues();
								if ($unit->getValue() == ' ' && in_array($value, $possibleValues)) {
									$key = 
                                        array_search($value, $possibleValues);
									if ($key != false) {
                                        // Remove value from possible values
                                        $unit->removeValue(
                                            $possibleValues, 
                                            $key
                                        );
										$progress = true;
									}
								}
							}
						} else {
							/*
                             * Check if square has a possible number that is 
                             * unique in its row, column or block. If so, all 
                             * other possibilities are removed. This is the 
							 * "Last Remaining Cell in a Box/Row/Column" method.
                             */
                            $possibleValues = $unit->getPossibleValues();
							$this->setAnalysisArray([]);
                             
                            foreach ($possibleValues as $possibleValue) {
                                $numberWithValue = 0;
                                 
                                foreach ($subgroup as $unitForArrayExamination) {
									$possibilities = $unitForArrayExamination->getPossibleValues();
                                    if (in_array($possibleValue, $possibilities)) {
                                        $numberWithValue++;
										$numberOfPossibilities = 
											count($possibilities);
										if ($numberOfPossibilities > 1 && $numberOfPossibilities < 5) {
											$this->addToAnalysisArray(
												$unitForArrayExamination->getUnitNumber(), 
												$possibilities
											);
										}	
                                    }
                                }
                                 
                                if ($numberWithValue == 1) {
                                    $unit->setPossibleValues([$possibleValue]);
                                    $unit->setValue($possibleValue);
                                    $progress = true;
									 
									/*
									 * Without this break, the solution will 
									 * contain errors
									 */ 
                                    break;
                                } elseif ($numberWithValue > 1 && $numberWithValue < 5) {
									
									// "Naked pair" method
									$pairs = [];
									foreach ($this->analysisArray as $cell) {
										if (count($cell['possibilities']) == 2) {
											$pairs[] = $cell['possibilities'];
										}
									}
									$numberOfPairs = count($pairs);
									if ($numberOfPairs > 1) {
										/*
										 * Comparison of arrays method similar 
										 * to bubble sort.
										 */
										for ($first = 0; $first < $numberOfPairs; $first++) {
											for($second = 0; $second < $numberOfPairs - $first - 1; $second++) {
												if ($pairs[$first] == $pairs[$first + 1]) {
													// For testing
													var_dump($pairs[$first]);
													var_dump($pairs[$first + 1]);
													echo '<br>';
												}
											}
										}
									}
								}
                            }
						}						
                        
						/*
						 * Set number of empty square if number of 
						 * possibilities is reduced to one.
						 */
						if ($unit->getValue() == ' ' && count($unit->getPossibleValues()) == 1) {
							$unit->setValue($unit->getPossibleValues()[0]);
						}
					}
				}
			}
		}
	}
	
	/**
	 * @param array $sudokuProblems
	 * @param array $sudokuSolutions
	 * @return void
	 */
	public function recordSolutions($sudokuProblems, $sudokuSolutions)
	{
		foreach ($sudokuProblems as $sudokuProblem) {
			foreach ($sudokuSolutions as $sudokuSolution) {
				if ($sudokuProblem->getProblemId() == $sudokuSolution->getProblemId()) {
                    
                    $groupings = $sudokuProblem->getGroupings();
                    $counter = 1;
                    $solutionArray = $sudokuSolution->getSudokuArray();
                    
					foreach ($solutionArray as $square) {
                        // No need to replace a square that is already filled
                        if ($square == ' ') {
                            foreach ($groupings as $group) {
                                /*
                                 * ceil() returns the next integer if number is
                                 * a float
                                 */
                                if ($group->getGroupType() == 'row' && $group->getNumber() == ceil($counter / 9)) {
                                    $subgroup = $group->getMembers();
                                    foreach ($subgroup as $unit) {
                                        // If a number can replace empty square, do it
                                        $value = $unit->getValue();
                                        if ($unit->getUnitNumber() == $counter && $value != ' ') {
                                            $solutionArray[$counter - 1] = $value;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
						
						$counter++;
					}
                    
                    $sudokuSolution->setSudokuArray($solutionArray);
				}
			}
		}		
	}
}
