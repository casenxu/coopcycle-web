<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Task;
use AppBundle\Entity\TaskCollectionItem;
use AppBundle\Entity\TaskList;
use AppBundle\Entity\ApiUser;
use PHPUnit\Framework\TestCase;

abstract class TaskCollectionTest extends TestCase
{
    protected $taskCollection;

    private function assertAddTask($count)
    {
        $this->assertCount($count, $this->taskCollection->getItems());
        $this->assertInstanceOf(TaskCollectionItem::class, $this->taskCollection->getItems()->get(0));
        $this->assertSame($this->taskCollection, $this->taskCollection->getItems()->get(0)->getParent());
    }

    public function testAddTasksWithoutPosition()
    {
        $task = new Task();
        $task2 = new Task();

        $this->taskCollection->addTask($task);

        $this->assertAddTask(1);

        $this->assertEquals($task, $this->taskCollection->findAt(0)->getTask());

        $this->taskCollection->addTask($task2);

        $this->assertAddTask(2);
        $this->assertEquals($task2, $this->taskCollection->findAt(1)->getTask());
    }

    public function testAddSameTaskTwiceWithoutPosition()
    {
        $task = new Task();

        $this->taskCollection->addTask($task);

        $this->assertAddTask(1);
        $this->assertEquals(0, $this->taskCollection->getItems()->get(0)->getPosition());

        $this->taskCollection->addTask($task);

        $this->assertAddTask(1);
        $this->assertEquals(0, $this->taskCollection->getItems()->get(0)->getPosition());
        $this->assertEquals($task, $this->taskCollection->findAt(0)->getTask());
    }

    public function testAddTaskWithPosition()
    {
        $task = new Task();

        $this->taskCollection->addTask($task, 4);

        $this->assertAddTask(1);
        $this->assertEquals($task, $this->taskCollection->findAt(4)->getTask());
    }

    public function testAddTaskUpdatesPosition()
    {
        $task = new Task();

        $this->taskCollection->addTask($task, 4);

        $this->assertCount(1, $this->taskCollection->getItems());
        $this->assertEquals(4, $this->taskCollection->getItems()->get(0)->getPosition());

        $this->taskCollection->addTask($task, 5);

        $this->assertCount(1, $this->taskCollection->getItems());
        $this->assertEquals(5, $this->taskCollection->getItems()->get(0)->getPosition());
    }

    public function testContainsTask()
    {
        $task1 = new Task();
        $task2 = new Task();

        $this->taskCollection->addTask($task1);

        $this->assertTrue($this->taskCollection->containsTask($task1));
        $this->assertFalse($this->taskCollection->containsTask($task2));

        $this->taskCollection->removeTask($task1);

        $this->assertFalse($this->taskCollection->containsTask($task1));
    }

    public function testUpdateTasksInsertTask()
    {
        $task = new Task();
        $task1 = new Task();
        $newTask = new Task();

        $this->taskCollection->addTask($task, 0);
        $this->taskCollection->addTask($task1, 1);

        $tasksToAssign = new \SplObjectStorage();
        $tasksToAssign[$task] = 0;
        $tasksToAssign[$newTask] = 1;
        $tasksToAssign[$task1] = 2;
        $this->taskCollection->updateTasks($tasksToAssign);

        $this->assertEquals($this->taskCollection->findAt(0)->getTask(), $task);
        $this->assertEquals($this->taskCollection->findAt(1)->getTask(), $newTask);
        $this->assertEquals($this->taskCollection->findAt(2)->getTask(), $task);
    }

    public function testUpdateTasksPushTask()
    {
        $task = new Task();
        $task1 = new Task();
        $newTask = new Task();

        $this->taskCollection->addTask($task, 0);
        $this->taskCollection->addTask($task1, 1);

        $tasksToAssign = new \SplObjectStorage();
        $tasksToAssign[$task] = 0;
        $tasksToAssign[$task1] = 1;
        $tasksToAssign[$newTask] = 2;
        $this->taskCollection->updateTasks($tasksToAssign);

        $this->assertEquals($this->taskCollection->findAt(0)->getTask(), $task);
        $this->assertEquals($this->taskCollection->findAt(1)->getTask(), $task1);
        $this->assertEquals($this->taskCollection->findAt(2)->getTask(), $newTask);
    }

    public function testUpdateTasksRemoveTask()
    {

        $task = new Task();
        $task->setId(1);
        $task1 = new Task();
        $task1->setId(2);
        $toRemove = new Task();
        $toRemove->setId(3);

        $this->taskCollection->addTask($task, 0);
        $this->taskCollection->addTask($task1, 1);
        $this->taskCollection->addTask($toRemove, 2);

        $tasksToAssign = new \SplObjectStorage();
        $tasksToAssign[$task] = 0;
        $tasksToAssign[$task1] = 1;
        $this->taskCollection->updateTasks($tasksToAssign);

        $this->assertEquals($this->taskCollection->findAt(0)->getTask()->getId(), 1);
        $this->assertEquals($this->taskCollection->findAt(1)->getTask()->getId(), 2);
        $this->assertEquals($this->taskCollection->findAt(2), null);

    }

    public function testUpdateTasksRemoveTaskNotLast()
    {
        $task = new Task();
        $task->setId(1);
        $task1 = new Task();
        $task1->setId(2);
        $toRemove = new Task();
        $toRemove->setId(3);

        $this->taskCollection->addTask($task, 0);
        $this->taskCollection->addTask($toRemove, 1);
        $this->taskCollection->addTask($task1, 2);

        $tasksToAssign = new \SplObjectStorage();
        $tasksToAssign[$task] = 0;
        $tasksToAssign[$task1] = 1;
        $this->taskCollection->updateTasks($tasksToAssign);

        $this->assertEquals($this->taskCollection->findAt(0)->getTask()->getId(), 1);
        $this->assertEquals($this->taskCollection->findAt(1)->getTask()->getId(), 2);
        $this->assertEquals($this->taskCollection->findAt(2), null);

    }

    public function testUpdateTasksReorderTasks()
    {

        $task = new Task();
        $task->setId(1);
        $task1 = new Task();
        $task1->setId(2);
        $task2 = new Task();
        $task2->setId(3);

        $this->taskCollection->addTask($task, 0);
        $this->taskCollection->addTask($task1, 1);
        $this->taskCollection->addTask($task2, 2);

        $tasksToAssign = new \SplObjectStorage();
        $tasksToAssign[$task] = 0;
        $tasksToAssign[$task1] = 2;
        $tasksToAssign[$task2] = 1;
        $this->taskCollection->updateTasks($tasksToAssign);

        $this->assertEquals($this->taskCollection->findAt(0)->getTask()->getId(), 1);
        $this->assertEquals($this->taskCollection->findAt(1)->getTask()->getId(), 3);
        $this->assertEquals($this->taskCollection->findAt(2)->getTask()->getId(), 2);

    }
}
