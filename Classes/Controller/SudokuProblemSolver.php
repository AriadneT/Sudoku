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
     * Array used for more complicated methods comparing two to three cells 
     * with two possible values
     *
     * @var string
     */
    private $pairs = [];
	
	/**
     * Array used for more complicated methods comparing three cells with two 
     * to three possible values
     *
     * @var string
     */
    private $pairsAndTriples = [];
	
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
     * Returns the pairs array
     *
     * @return array $pairs
     */
    public function getPairs()
    {
        return $this->pairs;
    }

    /**
     * Sets the pairs array
     *
     * @return void
     */
    public function setPairs($pairs)
    {
        $this->pairs = $pairs;
    }
	
	/**
     * Returns the array of pairs and triples
     *
     * @return array $pairsAndTriples
     */
    public function getPairsAndTriples()
    {
        return $this->pairsAndTriples;
    }

    /**
     * Sets the array of pairs and triples
     *
     * @return void
     */
    public function setPairsAndTriples($pairsAndTriples)
    {
        $this->pairsAndTriples = $pairsAndTriples;
    }
	
	/**
     * Adds unit number and possible values to the pairs array
     *
	 * @param int $unitNumber
	 * @param array $possibleValues
     * @return void
     */
    public function addToPairs($unitNumber, $possibleValues)
    {
		array_push(
			$this->pairs, 
			['position' => $unitNumber, 'possibilities' => $possibleValues]
		);
    }
	
	/**
     * Adds unit number and possible values to array of pairs or triples
     *
	 * @param int $unitNumber
	 * @param array $possibleValues
     * @return void
     */
    public function addPairOrTriple($unitNumber, $possibleValues)
    {
		array_push(
			$this->pairsAndTriples, 
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
        $this->setSolution(
			file_get_contents($configurations['htmlFiles']['start'])
		);
		
        /*
         * Steps requiring database use (and anything in between) in the try/
         * catch block to allow recording of any database errors
         */
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
                // Delete '.sudoku' from file name
				$name = substr_replace(basename($sudokuFile), '', -7);
                
                $array = $this->prepareProblemArray($problem, $configurations);
				
				$sudokuProblem = new SudokuProblem($array, $name);
				
				/*
				 * Only save and try to solve the sudoku if it does not have at 
				 * least two of a specific number per row, column and/or block
				 */
				$groups = $sudokuProblem->groupValues($configurations);
				if ($sudokuProblem->check($groups)) {
					$sudokuProblem->saveSudoku($databaseConnection);
					$problemId = 
                        $sudokuProblem->fetchProblemId($databaseConnection);
					$sudokuProblem->setProblemId($problemId);
					$sudokuProblems[] = $sudokuProblem;
					
					// Solution initially identical to the relevant problem
					$sudokuSolution = 
						new SudokuSolution($array, $problemId, $name);
					$sudokuSolutions[] = $sudokuSolution;
				} else {
                    // On-screen error message
					$this->setSolution(
						$this->solution . 
						file_get_contents(
							$configurations['htmlFiles']['error']
						)
					);
					$this->setSolution(
						str_replace(
							'#FILE#', 
							$sudokuProblem->getFileName(), 
							$this->solution
						)
					);
				}				
			}
			/*
			 * Sort sudoku problem into rows, columns, boxes and units 
			 * (as objects)
			 */
			$this->sortProblems($sudokuProblems, $configurations);
					
			// Solve the sudoku as much as possible through logic
			$this->solveSudokus($sudokuProblems);
					
			// Replace missing values in the sudoku solution's array
			$this->recordSolutions($sudokuProblems, $sudokuSolutions);
			
			// Save the sudoku solutions
			$this->saveSolutions($sudokuSolutions, $databaseConnection);
            
		} catch (PDOException $connectionException) {
			// Log database error
			$log = new ErrorLog;
			$log->logError($connectionException);
		}
		
		// Show solution(s)
		$this->setSolution(
			$this->writeSolutions($sudokuSolutions, $configurations)
		);
		
		return $this->solution;
	}
	
	/**
	 * @param string $problem
	 * @param array $configurations
	 * @return array $array
	 */
	public function prepareProblemArray($problem, $configurations)
	{
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
        
        return $array;
    }
        
    /**
	 * @param array $sudokuSolutions
	 * @param array $configurations
	 * @return string $solution
	 */
	public function writeSolutions($sudokuSolutions, $configurations)
	{
		foreach ($sudokuSolutions as $sudokuSolution) {
			$this->setSolution(
                $this->solution . 
				file_get_contents($configurations['htmlFiles']['tableHead'])
            );
			$this->setSolution(
				str_replace(
					'#PROBLEM#', 
					$sudokuSolution->getFileName(), 
					$this->solution
				)
			);
            $this->writeTableBody($sudokuSolution, $configurations);
			
			$this->setSolution(
				$this->solution . 
				file_get_contents($configurations['htmlFiles']['tableEnd'])
			);
		}
		$this->setSolution(
			$this->solution . 
			file_get_contents($configurations['htmlFiles']['end'])
		);
		
		return $this->solution;
	}
	
	/**
	 * @param object $sudokuSolution
	 * @param array $configurations
	 * @return void
	 */
	public function writeTableBody($sudokuSolution, $configurations)
	{
        $counter = 1;
		$this->setSolution(
                $this->solution . 
				file_get_contents($configurations['htmlFiles']['tableBody'])
            );
        
        foreach ($sudokuSolution->getSudokuArray() as $square) {
			$this->setSolution(
				str_replace('#' . $counter . '#', $square, $this->solution)
			);

            $counter++;
        }
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
            $this->instantiateGroups($sudokuProblem);
            
			$counter = 1;
			$groupings = $sudokuProblem->getGroupings();
            
			foreach ($sudokuProblem->getSudokuArray() as $square) {
				$unit = new Unit($counter, $square);
				
				// Fill the rows with the appropriate units
                $this->fillRows($counter, $groupings, $unit);
		
				// Fill the columns with the appropriate units
                $this->fillColumns($counter, $groupings, $unit);
				
				// Fill the blocks with the appropriate units
                $this->fillBlocks(
					$counter, 
					$groupings, 
					$unit, 
					$configurations
				);
				
				$counter++;
			}
		}
	}
    
    /**
	 * @param object $sudokuProblem
	 * @return void
	 */
	public function instantiateGroups($sudokuProblem)
	{
        for ($index = 1; $index < 10; $index++) {
            $row = new Row($index, []);
            $column = new Column($index, []);
            $block = new Block($index, []);
            
            $sudokuProblem->addGroup($row);
            $sudokuProblem->addGroup($column);
            $sudokuProblem->addGroup($block);
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
			$groupings = $sudokuProblem->getGroupings();
			$progress = true;
			
			/*
			 * If number of possibilities is reduced, progress is made and it 
			 * is worthwhile to repeat the process. Otherwise stop trying to 
			 * reduce possibilities.
			 */
			while ($progress == true) {
				$progress = false;
				
				/*
				 * The most basic methods "last possible member" and "last 
				 * remaining cell"
				 */
				$progress = $this->implementBasics($groupings, $progress);
				
				foreach ($groupings as $group) {
					$subgroup = $group->getMembers();
					foreach ($subgroup as $unit) {
						/*
						 * Set number of empty square if number of 
						 * possibilities is reduced to one.
						 */
						if ($unit->getValue() == ' ') {
							$this->checkForSetSquare($unit);
							if ($unit->getValue() != ' ') {
								$progress = true;
							}
						}
					}
				}
			}
			
			/* 
			 * "Naked pair" method. It was helpful and perhaps necessary 
			 * to keep it and other more complex methods separate from 
			 * other methods to keep them from interfering with each other 
			 * and causing errors.
			 */
			do {
				$progress = false;
				$progress = $this->implementNakedPairMethod(
					$groupings, 
					'column', 
					$progress
				);
				$progress = $this->implementBasics($groupings, $progress);
				$progress = $this->implementNakedPairMethod(
					$groupings, 
					'row', 
					$progress
				);
				$progress = $this->implementBasics($groupings, $progress);
				$progress = $this->implementNakedPairMethod(
					$groupings, 
					'block', 
					$progress
				);
				$progress = $this->implementBasics($groupings, $progress);
				/* 
				 * "Naked triple" method using cells with two to three 
				 * possibilities
				 */
				$progress = 
					$this->implementTriplesNakedTriple($groupings, $progress);
				$progress = $this->implementBasics($groupings, $progress);
			} while ($progress == true);
		}
	}
	
	/**
	 * @param object $unit
	 * @param array $subgroup
	 * @param bool $progress
	 * @return bool $progress
	 */
	public function implementLastPossibleMember($unit, $subgroup, $progress)
	{
        $value = $unit->getValue();
        
        foreach ($subgroup as $cell) {
            $possibleValues = $cell->getPossibleValues();
			
            if ($cell->getValue() == ' ' && in_array($value, $possibleValues)) {
                // Remove value from possible values
                $cell->removeValue($possibleValues, $value);
                
                $progress = true;
            }
        }
        
        return $progress;
    }
    
    /**
	 * @param object $unit
	 * @param array $subgroup
	 * @param bool $progress
	 * @return bool $progress
	 */
	public function implementLastRemainingCell($unit, $subgroup, $progress)
	{
        foreach ($unit->getPossibleValues() as $possibleValue) {
			// Count number of specific numeral in each row/column/block
			$numberWithValue = $this->countInGroup($subgroup, $possibleValue);
             
            if ($numberWithValue == 1) {
                $unit->setPossibleValues([$possibleValue]);
                $unit->setValue($possibleValue);
                $progress = true;               
                // Without this break, the solution will contain errors
                break;
            }
        }
        
        return $progress;
    }
        
    /**
	 * @param array $subgroup
	 * @param int $possibleValue
	 * @return int $numberWithValue
	 */
	public function countInGroup($subgroup, $possibleValue)
	{
		$numberWithValue = 0;
		
		foreach ($subgroup as $unitForArrayExamination) {
			$possibilities = $unitForArrayExamination->getPossibleValues();
			if (in_array($possibleValue, $possibilities)) {
				$numberWithValue++;
			}
		}
		
		return $numberWithValue;
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
							$solutionArray = 
								$this->fillEmptySquare(
									$groupings, 
									$counter, 
									$solutionArray
								);
                        }
						
						$counter++;
					}
                    
                    $sudokuSolution->setSudokuArray($solutionArray);
				}
			}
		}		
	}
	
	/**
 	 * @param array $groupings
 	 * @param string $category
 	 * @param bool $progress
	 * @return int $progress
	 */
	public function implementNakedPairMethod($groupings, $category, $progress)
	{
		foreach ($groupings as $group) {
			// Try one type of group at a time to avoid errors.
			if ($group->getGroupType() == $category) {
				$this->setPairs([]);
				$subgroup = $group->getMembers();
				
				foreach ($subgroup as $unit) {
					$possibleValues = $unit->getPossibleValues();
					$numberOfPossibilities = count($possibleValues);
					
					if ($numberOfPossibilities == 2) {
						$this->addToPairs(
							$unit->getUnitNumber(), 
							$possibleValues
						);
					}
				}
				$numberOfPairs = count($this->pairs);
				
				if ($numberOfPairs > 1) {
					for ($first = 0; $first < $numberOfPairs - 1; $first++) {
						$firstPair = $this->pairs[$first]['possibilities'];
						for ($second = $first + 1; $second < $numberOfPairs; $second++) {
							$secondPair = 
								$this->pairs[$second]['possibilities'];
							if ($firstPair == $secondPair) {
								$firstCellNumber = 
									$this->pairs[$first]['position'];
								$secondCellNumber = 
									$this->pairs[$second]['position'];
								
								/*									
								 * Remove numbers from naked pair as 
								 * possibilities in empty units that lack this 
								 * "naked pair"
								 */
								foreach ($firstPair as $pairedNumber) {
									foreach ($subgroup as $unit) {
										$cellNumber = $unit->getUnitNumber();
										$potentialValues = 
												$unit->getPossibleValues();
										if (count($potentialValues) > 1 && $cellNumber != $secondCellNumber) {
											if ($cellNumber != $firstCellNumber) {
												if (in_array($pairedNumber, $potentialValues)) {
													$unit->removeValue(
														$potentialValues, 
														$pairedNumber
													);
													$progress = true;
													$this->checkForSetSquare($unit);
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
 		}
		
		return $progress;
	}
	
	/**
	 * @param array $groupings
	 * @param int $counter
	 * @param array $solutionArray
	 * @return void
	 */
	public function fillEmptySquare($groupings, $counter, $solutionArray)
	{
		foreach ($groupings as $group) {
			// ceil() returns the next integer if number is a float
			if ($group->getGroupType() == 'row' && $group->getNumber() == ceil($counter / 9)) {
				foreach ($group->getMembers() as $unit) {
					// If a number can replace empty square, do it
					$value = $unit->getValue();
					if ($unit->getUnitNumber() == $counter && $value != ' ') {
						$solutionArray[$counter - 1] = $value;
						break;
					} /*elseif ($unit->getUnitNumber() == $counter && $value == ' ') {
						echo $unit->getUnitNumber();
						var_dump($unit->getPossibleValues());
						echo '<br>';
					}*/
				}
			}
		}
		
		return $solutionArray;
	}
    
	/**
	 * @param array $groupings
	 * @param bool $progress
	 * @return bool $progress
	 */
	public function implementTriplesNakedTriple($groupings, $progress)
	{
		foreach ($groupings as $group) {
			$this->setPairsAndTriples([]);
			$subgroup = $group->getMembers();
			
			foreach ($subgroup as $unit) {
				$possibleValues = $unit->getPossibleValues();
				$numberOfPossibilities = count($possibleValues);
				
				if ($numberOfPossibilities > 1 && $numberOfPossibilities < 4) {
					$this->addPairOrTriple(
						$unit->getUnitNumber(), 
						$possibleValues
					);
				}
			}
			$numberOfSets = count($this->pairsAndTriples);
			
			if ($numberOfSets > 2) {
				for ($first = 0; $first < $numberOfSets - 2; $first++) {
					$firstGroup = 
						$this->pairsAndTriples[$first]['possibilities'];
					for ($second = $first + 1; $second < $numberOfSets - 1; $second++) {
						$secondGroup = 
							$this->pairsAndTriples[$second]['possibilities'];
						for ($third = $second + 1; $third < $numberOfSets; $third++) {
							$thirdGroup = 
								$this->pairsAndTriples[$third]['possibilities'];
							
							if ($firstGroup == $secondGroup && $secondGroup == $thirdGroup) {
                                /*
                                 * This version does not need to be implemented 
                                 * because the sudokus have been solved
                                 */
							} elseif ($firstGroup == $secondGroup && count($firstGroup) == 3) {
								if (count($thirdGroup) == 2) {
									$commonality = 0;
									
									foreach ($firstGroup as $entry) {
										foreach ($thirdGroup as $entryNumber) {
											if ($entry == $entryNumber) {
												$commonality++;
											}
										}
									}
									
									if ($commonality == 2) {
										$firstCellNumber = 
											$this->pairsAndTriples[$first]['position'];
										$secondCellNumber = 
											$this->pairsAndTriples[$second]['position'];
										$thirdCellNumber = 
											$this->pairsAndTriples[$third]['position'];
										$trio = [
											$firstCellNumber, 
											$secondCellNumber,
											$thirdCellNumber
											];
										
										/*									
										 * Remove numbers from naked pair as 
										 * possibilities in empty units that lack this 
										 * "naked pair"
										 */
										foreach ($firstGroup as $entry) {
											foreach ($subgroup as $unit) {
												$cellNumber = $unit->getUnitNumber();
												$potentialValues = 
													$unit->getPossibleValues();
												
												if (count($potentialValues) > 1 && !in_array($cellNumber, $trio)) {
													if (in_array($entry, $potentialValues)) {
														$unit->removeValue(
															$potentialValues, 
															$entry
														);
														$progress = true;
														$this->checkForSetSquare($unit);
													}
												}
											}
										}
									}									
								}
							} elseif ($secondGroup == $thirdGroup && count($secondGroup) == 3) {
								if (count($firstGroup) == 2) {
									$commonality = 0;
									
									foreach ($thirdGroup as $entry) {
										foreach ($firstGroup as $entryNumber) {
											if ($entry == $entryNumber) {
												$commonality++;
											}
										}
									}
									
									if ($commonality == 2) {
										$firstCellNumber = 
											$this->pairsAndTriples[$first]['position'];
										$secondCellNumber = 
											$this->pairsAndTriples[$second]['position'];
										$thirdCellNumber = 
											$this->pairsAndTriples[$third]['position'];
										$trio = [
											$firstCellNumber, 
											$secondCellNumber,
											$thirdCellNumber
											];
										
										/*									
										 * Remove numbers from naked pair as 
										 * possibilities in empty units that lack this 
										 * "naked pair"
										 */
										foreach ($thirdGroup as $entry) {
											foreach ($subgroup as $unit) {
												$cellNumber = $unit->getUnitNumber();
												$potentialValues = 
													$unit->getPossibleValues();
												
												if (count($potentialValues) > 1 && !in_array($cellNumber, $trio)) {
													if (in_array($entry, $potentialValues)) {
														$unit->removeValue(
															$potentialValues, 
															$entry
														);
														$progress = true;
														$this->checkForSetSquare($unit);
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		return $progress;
	}
    
    /**
	 * @param int $counter
	 * @param array $groupings
	 * @param object $unit
	 * @return void
	 */
	public function fillRows($counter, $groupings, $unit)
	{
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
    }
    
    /**
	 * @param int $counter
	 * @param array $groupings
	 * @param object $unit
	 * @return void
	 */
	public function fillColumns($counter, $groupings, $unit)
	{
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
    }
    
    /**
	 * @param int $counter
	 * @param array $groupings
	 * @param object $unit
	 * @param array $configurations
	 * @return void
	 */
	public function fillBlocks($counter, $groupings, $unit, $configurations)
	{
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
    }
	
	/**
	 * @param array $groupings
	 * @param bool $progress
	 * @return bool $progress
	 */
	public function implementBasics($groupings, $progress)
	{
		foreach ($groupings as $group) {
			$subgroup = $group->getMembers();
			foreach ($subgroup as $unit) {
				/*
				 * If square has a number, remove this number as a possibility 
				 * from other squares in the block/row/column. This is part of 
				 * the "Last Possible Number" method.
				 */
				if ($unit->getValue() != ' ') {
					$progress = $this->implementLastPossibleMember(
						$unit, 
						$subgroup, 
						$progress
					);
				} else {
					/*
					 * Check if square has a possible number that is unique in 
					 * its row, column or block. If so, all other possibilities 
					 * are removed.
					 */
					$progress = $this->implementLastRemainingCell(
						$unit, 
						$subgroup,
						$progress
					);
				}						
			}
		}
		
		return $progress;
	}
	
	/**
	 * @param object $unit
	 * @return void
	 */
	public function checkForSetSquare($unit)
	{
		if (count($unit->getPossibleValues()) == 1) {
			/*
			 * Key is not always 0, even if the array has just one value, so 
			 * using a foreach loop avoids errors
			 */
			foreach ($unit->getPossibleValues() as $justOne) {
				$unit->setValue($justOne);
			}
		}
	}
}
