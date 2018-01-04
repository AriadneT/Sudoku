<?php
class Row extends Group
{
	/**
     * Type of group is row, so no setter is written for this variable
     *
     * @var string
     */
	private $groupType = 'row';
	
	/**
     * Returns the group type
     *
     * @return string $groupType
     */
    public function getGroupType()
    {
        return $this->groupType;
    }
}
