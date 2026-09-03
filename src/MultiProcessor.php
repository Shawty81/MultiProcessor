<?php

namespace MultiProcessor;

use DateTime;
use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\ChildrenPool\Child;
use MultiProcessor\ChildrenPool\ChildrenPool;
use MultiProcessor\Iterator\IteratorInterface;
use MultiProcessor\Queue\Chunk;
use MultiProcessor\Queue\Queue;
use MultiProcessor\SigHandling\SigHandler;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use Throwable;

class MultiProcessor implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private readonly IteratorInterface $iterator;
    private readonly ChildProcessorInterface $childProcessor;
    private readonly ChildrenPool $childrenPool;
    private readonly Queue $queue;
    private readonly SigHandler $sigHandler;
    private int $totalChunks;
    private int $parentPid;
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
        $this->parentPid = posix_getpid();

        $this->sigHandler = new SigHandler($this->parentPid);
        $this->sigHandler->registerShutdownCallback(fn() => $this->shutdown());
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
            do {
                $loop = $this->loop();
            } while ($loop);
        }
    }

    private function loop(): bool
    {
        $chunk = $this->getChunk();

        // If there are no chunks left it means the script is almost done
        if ($chunk === null) {
            // Wait for all children to exit before breaking the while loop
            while ($this->childrenPool->numberOfChildren() > 0) {
                if (!$this->waitOnChildToExit()) {
                    break;
                }

                if ($this->queue->size() > 0) {
                    return true;
                }
            }

            return false;
        }

        $pid = $this->fork();

        if ($pid == -1) {
            // Something is very wrong
            throw new RuntimeException('Something is very wrong.');
        } elseif ($pid) {
            $this->processParent($pid, $chunk);
            return true;
        }

        $this->processChild($chunk);
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
        if ($this->childrenPool->numberOfChildren() >= $this->settings->getMaxChildren()) {
            $this->waitOnChildToExit();
        }
    }

    /**
     * @SuppressWarnings("PHPMD.ExitExpression")
     */
    private function processChild(Chunk $chunk): never
    {
        try {
            // If your iterator and ChildProcessor use the same persistent connections some external form of storage (for example MySQL), this is the moment to drop those connections
            $this->iterator->dropConnections();

            // Do whatever needs to be done
            $this->childProcessor->process($chunk);
        } catch (Throwable $throwable) {
            // Only the child has the exception, so it is the only one that can report it.
            // The parent just sees the exit status.
            $this->logger?->error(
                'Child (pid: {childPid}) failed with {class}: {message}' . PHP_EOL . '{trace}',
                [
                    'childPid' => posix_getpid(),
                    'class' => $throwable::class,
                    'message' => $throwable->getMessage(),
                    'trace' => $throwable->getTraceAsString(),
                ]
            );

            exit(255);
        }

        // Child process is done, exit cleanly
        exit(0);
    }

    /**
     * Waits for a child to stop and deals with the way it stopped.
     *
     * Returns false when there was nothing left to reap, so that callers looping on the
     * size of the pool cannot spin forever on a pool that no longer matches reality.
     */
    private function waitOnChildToExit(): bool
    {
        $childPid = pcntl_waitpid(0, $status);

        if ($childPid < 1) {
            $this->logger?->warning('There is no child left to reap, pcntl_waitpid() returned {result}.', ['result' => $childPid]);

            return false;
        }

        $child = $this->childrenPool->removeChild($childPid);

        if ($child === null) {
            $this->logger?->warning('Reaped a child (pid: {childPid}) that is not in the pool, ignoring it.', ['childPid' => $childPid]);

            return true;
        }

        // A child that was killed never got to choose an exit status of its own
        if (pcntl_wifsignaled($status)) {
            $signal = pcntl_wtermsig($status);
            $this->logger?->alert('Child (pid: {childPid}) was killed by signal {signal}.', ['childPid' => $child->pid, 'signal' => $signal]);
            $this->processError($child);

            return true;
        }

        // pcntl_wexitstatus() only fails on a status pcntl_wifexited() has already rejected
        if (pcntl_wifexited($status)) {
            $this->processExitStatus($child, (int) pcntl_wexitstatus($status));
        }

        return true;
    }

    private function processExitStatus(Child $child, int $exitStatus): void
    {
        switch ($exitStatus) {
            case 1:
                // exited because there is no chunk
            case 0:
                // child exited correctly
                return;
            case 255:
                $this->logger?->alert('Child (pid: {childPid}) exited with an error.', ['childPid' => $child->pid]);
                $this->processError($child);

                return;
            default:
                $this->logger?->info(
                    'Child (pid: {childPid}) exited with unknown status [ {status} ].',
                    ['childPid' => $child->pid, 'status' => $exitStatus]
                );

                // Leaving here without killing the children would orphan every one of them
                $this->shutdown();
        }
    }

    private function processError(Child $child): void
    {
        if ($this->settings->isExitOnFatal()) {
            $this->shutdown();
        }

        if (!$this->settings->isRetryOnFatal()) {
            return;
        }

        $chunk = $child->chunk;

        if ($chunk->retries >= $this->settings->getMaxRetries()) {
            $this->logger?->alert(
                'Chunk from Child (pid: {childPid}) failed {attempts} times, giving up on it.',
                ['childPid' => $child->pid, 'attempts' => $chunk->retries + 1]
            );

            return;
        }

        $this->logger?->info('Queueing chunk from Child (pid: {childPid}) to be retried.', ['childPid' => $child->pid]);
        $this->queue->addChunk($chunk->retried());
    }

    /**
     * @SuppressWarnings("PHPMD.ExitExpression")
     */
    private function shutdown(): never
    {
        $this->logger?->critical('');
        $this->logger?->critical('Initiate killing of children.');
        $this->logger?->critical('');

        foreach ($this->childrenPool->getChildren() as $child) {
            $this->logger?->critical('Killing child (pid: {childPid}).', ['childPid' => $child->pid]);
            posix_kill($child->pid, SIGKILL);
        }

        $this->logger?->critical('');
        $this->logger?->critical('Killed all children, MultiProcessor aborted successfully!');

        exit();
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
