<?php
abstract class Group
{
	/**
     * Group number (i.e. row, column or block number)
     *
     * @var int
     */
	private $number = 0;
	
	/**
     * Group of units belonging to a group
     *
     * @var array
     */
	private $members = [];
	
	/**
     * Returns the group number
     *
     * @return int $number
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Sets the group number
     *
     * @param int $number
     * @return void
     */
    public function setNumber($number)
    {
		$this->number = $number;
    }

	/**
     * Returns the members
     *
     * @return array $members
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * Sets the members
     *
     * @param array $members
     * @return void
     */
    public function setMembers($members)
    {
		$this->members = $members;
    }
	
	/**
     * Adds a unit to a group
     *
     * @param object $unit
     * @return void
     */
    public function addMember($unit)
    {
		if (count($this->members) < 9) {
			array_push($this->members, $unit);
		}
    }
	
	/**
	 * To instantiate a new group
	 *
	 * @param int $number
	 * @param array $subset
	 * @return object $group
	 */
	public function __construct($number, $subset)
	{
		$this->setNumber($number);
		$this->setMembers($subset);
	}
}