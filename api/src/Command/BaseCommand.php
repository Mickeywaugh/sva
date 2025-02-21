<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'app:basecmd', description: 'base class for daemon process')]
class BaseCommand extends Command
{
  protected $pidFile = '';
  public function __construct()
  {
    parent::__construct();
    date_default_timezone_set('Asia/Shanghai');
  }

  protected function configure(): void
  {
    $this
      ->addArgument('action', InputArgument::REQUIRED, 'Action: start|stop|restart')
      ->addOption('begin', 'b', InputOption::VALUE_OPTIONAL, 'An option parameter')
      ->addOption('startId', 'i', InputOption::VALUE_OPTIONAL, 'An option parameter')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);
    $action = $input->getArgument('action');
    $options = ["beginDate" => $input->getOption('begin'), "startId" => $input->getOption('startId')];
    $io->note("options is " . json_encode($options));
    switch ($action) {
      case 'start':
        $this->startDaemon($io, $options);
        break;
      case 'stop':
        $this->stopDaemon($io);
        break;
      case 'restart':
        $this->restartDeamon($io, $options);
        break;
      default:
        $io->error('Invalid action. Use "start|stop|restart".');
        return Command::FAILURE;
    }

    return Command::SUCCESS;
  }

  protected function startDaemon(SymfonyStyle $io, array $options): void
  {
    if ($this->isRunning()) {
      $io->warning('Daemon is already running.');
      return;
    }

    $pid = pcntl_fork();

    if ($pid == -1) {
      $io->error('Could not fork.');
      exit(1);
    } elseif ($pid) {
      // Parent process
      file_put_contents($this->pidFile, $pid);
      $io->success('Daemon started with PID: ' . $pid);
    } else {
      // Child process
      $this->process($io, $options);
    }
  }

  protected function stopDaemon(SymfonyStyle $io): void
  {
    if (!$this->isRunning()) {
      $io->warning('Daemon is not running.');
      return;
    }

    $pid = (int)file_get_contents($this->pidFile);
    posix_kill($pid, SIGTERM);
    $fileSystem = new Filesystem();
    $fileSystem->remove($this->pidFile);
    $io->success('Daemon stopped.');
  }

  public function isRunning(): bool
  {
    if (!file_exists($this->pidFile)) {
      return false;
    }

    $pid = (int)file_get_contents($this->pidFile);
    return posix_kill($pid, 0);
  }

  protected function restartDeamon(SymfonyStyle $io, array $options): void
  {
    if ($this->isRunning()) {
      $this->stopDaemon($io);
    }
    $this->startDaemon($io, $options);
  }

  /**
   * Manage your daemon logic here
   */
  protected function process(SymfonyStyle $io, $options = null) {}
}
