<?php

namespace MultiProcessor;

use DateTime;
use Exception;
use Psr\Log\LoggerAwareTrait;
use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\IteratorInterface;
use RuntimeException;

class MultiProcessor
{
    use LoggerAwareTrait;

    protected IteratorInterface $iterator;
    protected ChildProcessorInterface $childProcessor;

    protected int $maxChildren;

    protected int $childCounter = 0;
    protected int $totalChunks;
    protected int|false $parentPid = false;

    protected int $startTime;

    protected int $endTime;

    public function __construct(Iterator\AbstractIterator $iterator, ChildProcessor\AbstractChildProcessor $childProcessor)
    {
        $this->iterator = $iterator;
        $this->childProcessor = $childProcessor;

        $this->parentPid = getmypid();
    }

    public function setMaxChildren(int $maxChildren): void
    {
        $this->maxChildren = $maxChildren;
    }

    public function run(): void
    {
        $this->init();

        $this->totalChunks = $this->iterator->getNumberOfChunks();

        $this->startProcessing();

        $this->finish();
    }

    private function init(): void
    {
        if(!isset($this->maxChildren)) {
            throw new Exception('Please call MultiProcessor::setMaxChildren(int) before calling MultiProcessor::run()');
        }

        $this->startTime = time();
        $this->logger?->info('Starting MultiProcessor');
        $this->logger?->info('');

        $this->childProcessor->init();
        $this->iterator->init();
    }

    private function startProcessing(): void
    {
        declare(ticks=1) {
            while(1) {
                $chunk = $this->iterator->getChunk();

                // If the chunk is empty it means the script is almost done
                if(empty($chunk)) {
                    // Wait for all children to exit before breaking the while loop
                    while($this->childCounter > 0) {
                        $this->waitOnChildToExit();
                    }

                    break;
                }

                $pid = $this->fork();

                if($pid == -1) {
                    // Something is very wrong
                    throw new RuntimeException('Something is very wrong.');
                } elseif($pid) {
                    $this->processParent();
                    continue;
                }

                $this->processChild($chunk);
            }
        }
    }

    private function fork(): int
    {
        return pcntl_fork();
    }

    private function processParent(): void
    {
        $this->childCounter++;

        // If number of children is equal or bigger than maxChildren. Wait for a child to exit
        if($this->childCounter >= $this->maxChildren) {
            $this->waitOnChildToExit();
        }
    }

    /**
     * @param mixed[] $chunk
     * @return void
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    private function processChild(array $chunk): void
    {
        // if there is no chunk, exit the process
        // if(empty($chunk)) exit(0);

        // If your iterator and ChildProcessor use the same persistent connections some external form of storage (for example MySQL), this is the moment to drop those connections
        $this->iterator->dropConnections();

        // Do whatever needs to be done
        $this->childProcessor->process($chunk);

        // Child process is done, exit cleanly
        exit(0);
    }

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    private function waitOnChildToExit(): void
    {
        // Waits for a child to stop
        $childPid = pcntl_waitpid(0, $status);

        // child exited
        if(pcntl_wifexited($status)) {
            // Check the exit status
            switch(pcntl_wexitstatus($status)) {
                case 1:
                    // exited because there is no chunk
                case 0:
                    // child exited correctly
                    break;
                case 255:
                    // Child fataled. For now, we are not going to do anything with this
                    break;
                default:
                    $this->logger?->info('Child (pid: ' . $childPid . ') exited with unknown status [ ' . pcntl_wexitstatus($status) . ' ]');
                    exit();
            }
        }

        // A Child is done, continueing script, update progressBar (to be implemented) and remove 1 child
        $this->childCounter--;
    }

    private function finish(): void
    {
        $this->childProcessor->finish();
        $this->iterator->finish();

        $this->endTime = time();

        $dateTimeFrom = new DateTime('@' . $this->startTime);
        $dateTimeTill = new DateTime('@' . $this->endTime);

        $time = $dateTimeFrom->diff($dateTimeTill)->format('%h hours, %i minutes and %s seconds');

        $this->logger?->info('');

        $this->logger?->info('MultiProcessor done!');

        $this->logger?->info('Total time spent: {time}', ['time' => $time]);
    }

}
