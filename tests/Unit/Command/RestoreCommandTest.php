<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Command;

use MakinaCorpus\DbToolsBundle\Command\RestoreCommand;
use MakinaCorpus\DbToolsBundle\Restorer\AbstractRestorer;
use MakinaCorpus\DbToolsBundle\Restorer\RestorerFactory;
use MakinaCorpus\DbToolsBundle\Storage\Storage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RestoreCommandTest extends TestCase
{
    /**
     * Creates a command tester with mocked dependencies
     * 
     * @param array $backupFiles List of backup files to be returned by storage
     */
    private function createCommand(array $backupFiles = []): CommandTester 
    {
        // Storage mock returns our test backup files and a fake path
        $storage = $this->createMock(Storage::class);
        $storage
            ->method('listBackups')
            ->willReturn($backupFiles);
        $storage
            ->method('getStoragePath')
            ->willReturn('/fake/path');

        // Restorer mock just needs to provide a file extension
        $restorer = $this->createMock(AbstractRestorer::class);
        $restorer
            ->method('getExtension')
            ->willReturn('sql');

        // Factory mock returns our mocked restorer
        $restorerFactory = $this->createMock(RestorerFactory::class);
        $restorerFactory
            ->method('create')
            ->willReturn($restorer);

        $command = new RestoreCommand(
            'default',
            $restorerFactory,
            $storage,
            null // timeout
        );

        return new CommandTester($command);
    }

    /**
     * Test listing when no backups are available
     */
    public function testListBackupsEmpty(): void
    {
        $commandTester = $this->createCommand([]);
        
        $commandTester->execute([
            '--list' => true,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(
            'Backups list',
            $output,
            'Output should contain the section title "Backups list"'
        );
        $this->assertStringContainsString(
            'There is no backup files available in /fake/path',
            $output,
            'Output should display the "no backups" warning message with the correct path'
        );
    }

    /**
     * Test listing with some backup files
     * Each backup file is represented by [date, filename]
     */
    public function testListBackupsWithFiles(): void
    {
        // Mock some backup files with date and filename
        $backupFiles = [
            ['1 days', 'backup_2024-03-20_10-30-00.sql'],
            ['2 days', 'backup_2024-03-21_14-45-00.sql'],
        ];
        
        $commandTester = $this->createCommand($backupFiles);
        
        $commandTester->execute([
            '--list' => true,
        ]);

        // Check that output contains both the section title and our backup files
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(
            'Backups list',
            $output,
            'Output should start with the section title "Backups list"'
        );
        $this->assertStringContainsString(
            $backupFiles[0][1] . ' (' . $backupFiles[0][0] . ')',
            $output,
            'Output should contain the first backup file with its age'
        );
        $this->assertStringContainsString(
            $backupFiles[1][1] . ' (' . $backupFiles[1][0] . ')',
            $output,
            'Output should contain the second backup file with its age'
        );
    }
}