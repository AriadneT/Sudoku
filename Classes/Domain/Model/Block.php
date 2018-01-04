<?php
class Block extends Group
{
	/**
     * Type of group is box, so no setter is written for this variable
     *
     * @var string
     */
	private $groupType = 'block';
	
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
