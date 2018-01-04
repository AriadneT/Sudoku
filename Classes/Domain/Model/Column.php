<?php
class Column extends Group
{
	/**
     * Type of group is column, so no setter is written for this variable
     *
     * @var string
     */
	private $groupType = 'column';
	
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
