<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Command;

use OCA\SecureOffice\Service\EnsDiagnosticService;
use OCP\IL10N;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnsAuditCommand extends Command {
    public function __construct(
        private EnsDiagnosticService $diagnosticService,
        private IL10N $l10n,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void {
        $this->setName('secure_office:ens-audit')
            ->setDescription('Audits National Security Scheme (ENS RD 311/2022) compliance for office documents and files');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('<info>===============================================================</info>');
        $output->writeln('<info>   NEXTCLOUD SECURE OFFICE - ENS COMPLIANCE AUDIT             </info>');
        $output->writeln('<info>       National Security Scheme (Royal Decree 311/2022)        </info>');
        $output->writeln('<info>===============================================================</info>');

        $diag = $this->diagnosticService->runDiagnostics();

        $table = new Table($output);
        $table->setHeaders([
            $this->l10n->t('ENS Measure'),
            $this->l10n->t('Dimension'),
            $this->l10n->t('Status'),
            $this->l10n->t('Details'),
        ]);

        foreach ($diag['measures'] as $m) {
            $statusFormatted = match ($m['status']) {
                'ok' => '<info>[' . $this->l10n->t('COMPLIANT') . ']</info>',
                'warning' => '<comment>[' . $this->l10n->t('WARNING') . ']</comment>',
                'error' => '<error>[' . $this->l10n->t('NON COMPLIANT') . ']</error>',
                default => '<fg=cyan>[' . $this->l10n->t('INFO') . ']</fg=cyan>',
            };

            $table->addRow([
                $m['code'],
                $m['title'],
                $statusFormatted,
                $m['details'] . ($m['recommendation'] ? "\n" . $this->l10n->t('Action: ') . $m['recommendation'] : ''),
            ]);
        }

        $table->render();

        $output->writeln('');
        $output->writeln($this->l10n->t('Global Diagnosis: ') . "<options=bold>{$diag['overall_label']}</>");
        $output->writeln($this->l10n->t('Evaluation date: ') . $diag['timestamp']);
        $output->writeln('<info>===============================================================</info>');

        return Command::SUCCESS;
    }
}