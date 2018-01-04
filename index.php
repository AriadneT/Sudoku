<?php
// Include main function's file so that it can be used
require_once('Classes\Controller\SudokuProblemSolver.php');

$solver = new SudokuProblemSolver();
echo $solver->showSolutions();
