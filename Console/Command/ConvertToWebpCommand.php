<?php
declare(strict_types=1);

namespace Spdivn\WebP\Console\Command;

use Spdivn\WebP\Model\Service\ConvertToWebpService;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @copyright (c) 2026, Spdivn
 * @package Spdivn/WebP
 * @subpackage Console
 *
 * CLI: spdivn:images:convert-to-webp
 *
 * Converts JPG/PNG images in a given directory to WebP format.
 *
 * Examples:
 *   bin/magento spdivn:images:convert-to-webp pub/media/catalog/product
 *   bin/magento spdivn:images:convert-to-webp /absolute/path --driver=imagick --quality=85 --recursive
 *   bin/magento spdivn:images:convert-to-webp pub/media/catalog --dry-run
 */
class ConvertToWebpCommand extends Command
{
    private const NAME               = 'spdivn:images:convert-to-webp';
    private const ARG_PATH           = 'path';
    private const OPT_DRIVER         = 'driver';
    private const OPT_QUALITY        = 'quality';
    private const OPT_KEEP_ORIGINALS = 'keep-originals';
    private const OPT_RECURSIVE      = 'recursive';
    private const OPT_DRY_RUN        = 'dry-run';
    private const DEFAULT_DRIVER     = 'gd';
    private const DEFAULT_QUALITY    = '80';

    public function __construct(
        private readonly AppState $appState,
        private readonly ConvertToWebpService $service,
        private readonly \Magento\Framework\Filesystem\DirectoryList $directoryList,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Convert JPG/PNG images in a directory to WebP format.')
            ->addArgument(self::ARG_PATH, InputArgument::REQUIRED, 'Directory path (absolute or relative to Magento root).')
            ->addOption(self::OPT_DRIVER, null, InputOption::VALUE_REQUIRED, 'Converter driver: gd or imagick.', self::DEFAULT_DRIVER)
            ->addOption(self::OPT_QUALITY, null, InputOption::VALUE_REQUIRED, 'WebP quality (0-100).', self::DEFAULT_QUALITY)
            ->addOption(self::OPT_KEEP_ORIGINALS, null, InputOption::VALUE_NONE, 'Keep original files after conversion.')
            ->addOption(self::OPT_RECURSIVE, null, InputOption::VALUE_NONE, 'Scan subdirectories recursively.')
            ->addOption(self::OPT_DRY_RUN, null, InputOption::VALUE_NONE, 'Simulate: list files that would be converted without modifying anything.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Throwable) {
            // Area already set.
        }

        $configuredPath = rtrim((string) $input->getArgument(self::ARG_PATH), '/\\');
        $driverName     = strtolower((string) $input->getOption(self::OPT_DRIVER));
        $quality        = (int) $input->getOption(self::OPT_QUALITY);
        $keepOriginals  = (bool) $input->getOption(self::OPT_KEEP_ORIGINALS);
        $recursive      = (bool) $input->getOption(self::OPT_RECURSIVE);
        $dryRun         = (bool) $input->getOption(self::OPT_DRY_RUN);

        // Resolve relative path against Magento root
        $path = str_starts_with($configuredPath, '/') || preg_match('/^[A-Za-z]:[\/\\\\]/', $configuredPath)
            ? $configuredPath
            : rtrim($this->directoryList->getRoot() . '/' . ltrim($configuredPath, '/'), '/');

        if ($dryRun) {
            return $this->runDryRun($output, $path, $recursive);
        }

        $output->writeln(sprintf(
            '<info>Starting conversion — driver: %s, quality: %d%s%s</info>',
            $driverName,
            $quality,
            $keepOriginals ? ', keeping originals' : ', deleting originals',
            $recursive ? ', recursive' : ''
        ));

        // Determine files first so we can show a proper progress bar
        $files = $this->service->findImageFiles($path, $recursive);
        $total = count($files);

        $progressBar = new ProgressBar($output, $total > 0 ? $total : 0);
        $progressBar->start();

        // Pass a callback to the service so it can notify us after each file.
        $result = $this->service->execute(
            $path,
            $driverName,
            $quality,
            $keepOriginals,
            $recursive,
            true,
            function (int $processed, int $total) use ($progressBar): void {
                // advance by one step for each processed file (safe regardless of total)
                $progressBar->advance();
            }
        );

        $progressBar->finish();
        $output->writeln('');

        if ($result['error'] !== null) {
            $output->writeln('<error>' . $result['error'] . '</error>');
            return Command::FAILURE;
        }

        $table = new Table($output);
        $table->setHeaders(['Metric', 'Value']);
        $table->addRows([
            ['Total files found',     $result['files']],
            ['Converted successfully', $result['converted']],
            ['Failed',                $result['failed']],
            ['Space saved',           $this->service->formatBytes($result['bytesSaved'])],
            ['DB references updated', (string) $result['dbUpdated']],
        ]);
        $table->render();

        return $result['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function runDryRun(OutputInterface $output, string $path, bool $recursive): int
    {
        if (!is_dir($path)) {
            $output->writeln(sprintf('<error>Path "%s" does not exist or is not a directory.</error>', $path));
            return Command::FAILURE;
        }

        $files = $this->service->findImageFiles($path, $recursive);

        if (empty($files)) {
            $output->writeln('<info>No JPG or PNG files found.</info>');
            return Command::SUCCESS;
        }

        $output->writeln(sprintf('<comment>[DRY-RUN] %d file(s) would be converted:</comment>', count($files)));
        foreach ($files as $file) {
            $dest = (string) preg_replace('/\.(jpe?g|png)$/i', '.webp', $file);
            $output->writeln(sprintf('  %s → %s', $file, $dest));
        }

        return Command::SUCCESS;
    }
}


