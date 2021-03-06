<?php
return [
	'sudokuFiles' => [
		'../Sudoku/Resources/Public/Sudoku/false.sudoku'
	],
	'phpFiles' => [
		'Classes\Domain\Model\SudokuProblem.php',
		'Classes\Domain\Model\SudokuSolution.php',
		'Classes\Domain\Model\Group.php',
		'Classes\Domain\Model\Row.php',
		'Classes\Domain\Model\Column.php',
		'Classes\Domain\Model\Block.php',
		'Classes\Domain\Model\Unit.php',
		'Classes\Domain\Temp\ErrorLogs.php',
		'Classes\Domain\Database\DatabasePreparer.php'
	],
	'databaseParameters' => [
		'host' => 'localhost',
		'databaseName' => 'mysql',
		'user' => 'root',
		'password' =>  ''
	],
	'validFileUnitValues' => [
		' ',
		1,
		2,
		3,
		4,
		5,
		6,
		7,
		8,
		9
	],
	'validFormUnitValues' => [
		'',
		1,
		2,
		3,
		4,
		5,
		6,
		7,
		8,
		9
	],
	'block1' => [
		1,
		2,
		3,
		10,
		11,
		12,
		19,
		20,
		21
	],
	'block2' => [
		4,
		5,
		6,
		13,
		14,
		15,
		22,
		23,
		24
	],
	'block3' => [
		7,
		8,
		9,
		16,
		17,
		18,
		25,
		26,
		27
	],
	'block4' => [
		28,
		29,
		30,
		37,
		38,
		39,
		46,
		47,
		48
	],
	'block5' => [
		31,
		32,
		33,
		40,
		41,
		42,
		49,
		50,
		51
	],
	'block6' => [
		34,
		35,
		36,
		43,
		44,
		45,
		52,
		53,
		54
	],
	'block7' => [
		55,
		56,
		57,
		64,
		65,
		66,
		73,
		74,
		75
	],
	'block8' => [
		58,
		59,
		60,
		67,
		68,
		69,
		76,
		77,
		78
	],
    'htmlFiles' => [
		'start' => '../Sudoku/Resources/Private/Templates/Start.html',
		'error' => '../Sudoku/Resources/Private/Templates/ErrorMessage.html',
		'tableHead' => '../Sudoku/Resources/Private/Templates/TableHead.html',
		'tableBody' => '../Sudoku/Resources/Private/Templates/TableBody.html',
		'tableEnd' => '../Sudoku/Resources/Private/Templates/TableEnd.html',
		'end' => '../Sudoku/Resources/Private/Templates/End.html'
	]
];
