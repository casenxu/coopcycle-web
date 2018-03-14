<?php

namespace AppBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * A TaskCollectionItem is a task belonging to a TaskCollection.
 */
class TaskCollectionItem
{
    private $id;

    /**
     * @Gedmo\SortableGroup
     */
    private $parent;

    /**
     * @Groups({"task_collection"})
     */
    private $task;

    /**
     * @Gedmo\SortablePosition
     * @Groups({"task_collection"})
     */
    private $position;

    public function getId()
    {
        return $this->id;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(TaskCollection $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    public function getTask()
    {
        return $this->task;
    }

    public function setTask($task)
    {
        $this->task = $task;

        return $this;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }
}
