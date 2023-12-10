<?php

namespace MultiProcessor;

use DateTime;
use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\ChildrenPool\Child;
use MultiProcessor\ChildrenPool\ChildrenPool;
use MultiProcessor\Iterator\IteratorInterface;
use MultiProcessor\Queue\Chunk;
use MultiProcessor\Queue\Queue;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;

class MultiProcessor
{
    use LoggerAwareTrait;

    private readonly IteratorInterface $iterator;
    private readonly ChildProcessorInterface $childProcessor;
    private readonly ChildrenPool $childrenPool;
    private readonly Queue $queue;
    private int $totalChunks;
    private int|false $parentPid;
    private int $startTime;

    public function __construct(
        private readonly Settings $settings
    ) {
        $this->settings->validate();

        $this->iterator = $this->settings->getIterator();
        $this->childProcessor = $this->settings->getChildProcessor();

        if ($this->settings->getLogger() !== null) {
            $this->setLogger($this->settings->getLogger());
        }

        $this->childrenPool = new ChildrenPool();
        $this->queue = new Queue();

        $this->parentPid = getmypid();
    }

    public function run(): void
    {
        $this->init();

        $this->totalChunks = $this->iterator->getNumberOfChunks($this->settings->getChunkSize());

        $this->startProcessing();

        $this->finish();
    }

    private function init(): void
    {
        $this->startTime = time();
        $this->logger?->info('Starting MultiProcessor');
        $this->logger?->info('Parent pid: {pid}', ['pid' => $this->parentPid]);
        $this->logger?->info('');

        $this->childProcessor->init();
        $this->iterator->init();
    }

    private function startProcessing(): void
    {
        declare(ticks=1) {
            while(1) {
                $chunk = $this->getChunk();

                // If there are no chunks left it means the script is almost done
                if($chunk === null) {
                    // Wait for all children to exit before breaking the while loop
                    while($this->childrenPool->numberOfChildren() > 0) {
                        $this->waitOnChildToExit();
                    }

                    break;
                }

                $pid = $this->fork();

                if($pid == -1) {
                    // Something is very wrong
                    throw new RuntimeException('Something is very wrong.');
                } elseif($pid) {
                    $this->processParent($pid, $chunk);
                    continue;
                }

                $this->processChild($chunk);
            }
        }
    }

    private function getChunk(): ?Chunk
    {
        $queuedChunk = $this->queue->getChunk();

        if ($queuedChunk !== null) {
            return $queuedChunk;
        }

        $chunk = $this->iterator->getChunk($this->settings->getChunkSize());

        if (empty($chunk->data)) {
            return null;
        }

        return $chunk;
    }

    private function fork(): int
    {
        return pcntl_fork();
    }

    private function processParent(int $pid, Chunk $chunk): void
    {
        $this->childrenPool->addChild(new Child($pid, $chunk));

        // If number of children is equal or bigger than max children. Wait for a child to exit
        if($this->childrenPool->numberOfChildren() >= $this->settings->getMaxChildren()) {
            $this->waitOnChildToExit();
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    private function processChild(Chunk $chunk): never
    {
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

        $this->childrenPool->removeChild($childPid);
    }

    private function finish(): void
    {
        $this->childProcessor->finish();
        $this->iterator->finish();

        $endTime = time();

        $dateTimeFrom = new DateTime('@' . $this->startTime);
        $dateTimeTill = new DateTime('@' . $endTime);

        $time = $dateTimeFrom->diff($dateTimeTill)->format('%h hours, %i minutes and %s seconds');

        $this->logger?->info('');

        $this->logger?->info('MultiProcessor done!');

        $this->logger?->info('');

        $this->logger?->info('Total time spent: {time}', ['time' => $time]);
        $this->logger?->info('Processed {chunks} chunks', ['chunks' => $this->totalChunks]);
    }

}
