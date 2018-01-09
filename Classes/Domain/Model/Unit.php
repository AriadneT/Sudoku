<?php
class Unit
{
	/**
     * Unit number (equivalent to counter position in the sudoku array)
     *
     * @var int
     */
	private $unitNumber = 0;
	
	/**
     * Row number
     *
     * @var int
     */
	private $rowNumber = 0;
	
	/**
     * Column number
     *
     * @var int
     */
	private $columnNumber = 0;
	
	/**
     * Box number
     *
     * @var int
     */
	private $boxNumber = 0;
	
	/**
     * Value
     *
     * @var string
     */
	private $value = '';
	
	/**
     * Possible values
     *
     * @var array
     */
	private $possibleValues = [1, 2, 3, 4, 5, 6, 7, 8, 9];
	
	/**
     * Returns the unit number
     *
     * @return int $unitNumber
     */
    public function getUnitNumber()
    {
        return $this->unitNumber;
    }

    /**
     * Sets the unit number
     *
     * @param int $unitNumber
     * @return void
     */
    public function setUnitNumber($unitNumber)
    {
		$this->unitNumber = $unitNumber;
    }
	
	/**
     * Returns the row number
     *
     * @return int $rowNumber
     */
    public function getRowNumber()
    {
        return $this->rowNumber;
    }

    /**
     * Sets the row number
     *
     * @param int $rowNumber
     * @return void
     */
    public function setRowNumber($rowNumber)
    {
		$this->rowNumber = $rowNumber;
    }
	
	/**
     * Returns the column number
     *
     * @return int $columnNumber
     */
    public function getColumnNumber()
    {
        return $this->columnNumber;
    }

    /**
     * Sets the column number
     *
     * @param int $columnNumber
     * @return void
     */
    public function setColumnNumber($columnNumber)
    {
		$this->columnNumber = $columnNumber;
    }
	
	/**
     * Returns the box number
     *
     * @return int $boxNumber
     */
    public function getBoxNumber()
    {
        return $this->boxNumber;
    }

    /**
     * Sets the box number
     *
     * @param int $boxNumber
     * @return void
     */
    public function setBoxNumber($boxNumber)
    {
		$this->boxNumber = $boxNumber;
    }
	
	/**
     * Returns the value
     *
     * @return string $value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the value
     *
     * @param string $value
     * @return void
     */
    public function setValue($value)
    {
		$this->value = $value;
    }
	
	/**
     * Returns the possible values
     *
     * @return array $possibleValues
     */
    public function getPossibleValues()
    {
        return $this->possibleValues;
    }

    /**
     * Sets the possible values
     *
     * @param array $possibleValues
     * @return void
     */
    public function setPossibleValues($possibleValues)
    {
		$this->possibleValues = $possibleValues;
    }
	
	/**
	 * To instantiate a new unit
	 *
	 * @param int $unitNumber
	 * @param string $value
	 * @return object $unit
	 */
	public function __construct($unitNumber, $value)
	{
		$this->setUnitNumber($unitNumber);
		$this->setValue($value);
		if ($this->value != ' ') {
			$this->setPossibleValues([$this->value]);
		}
	}
    
    /**
	 * @param array $possibleValues
	 * @param int $value
	 * @return void
	 */
	public function removeValue($possibleValues, $value)
	{
		$key = array_search($value, $possibleValues);
        // !== instead of != to ensure that 0 is not interpreted as false
		if ($key !== false) {
            unset($possibleValues[$key]);
            $this->setPossibleValues($possibleValues);
        }
	}
}
