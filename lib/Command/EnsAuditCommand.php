<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Command;

use OCA\SecureOffice\Service\EnsDiagnosticService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnsAuditCommand extends Command {
    public function __construct(
        private EnsDiagnosticService $diagnosticService,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void {
        $this->setName('secure_office:ens-audit')
            ->setDescription('Audita el cumplimiento del Esquema Nacional de Seguridad (ENS RD 311/2022) en documentos ofimáticos');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('<info>===============================================================</info>');
        $output->writeln('<info>   NEXTCLOUD SECURE OFFICE - AUDITORÍA DE CUMPLIMIENTO ENS    </info>');
        $output->writeln('<info>       Esquema Nacional de Seguridad (Real Decreto 311/2022)   </info>');
        $output->writeln('<info>===============================================================</info>');

        $diag = $this->diagnosticService->runDiagnostics();

        $table = new Table($output);
        $table->setHeaders(['Medida ENS', 'Dimensión', 'Estado', 'Detalles']);

        foreach ($diag['measures'] as $m) {
            $statusFormatted = match ($m['status']) {
                'ok' => '<info>[CONFORME]</info>',
                'warning' => '<comment>[ATENCIÓN]</comment>',
                'error' => '<error>[NO CONFORME]</error>',
                default => '<fg=cyan>[INFO]</fg=cyan>',
            };

            $table->addRow([
                $m['code'],
                $m['title'],
                $statusFormatted,
                $m['details'] . ($m['recommendation'] ? "\nAcción: " . $m['recommendation'] : ''),
            ]);
        }

        $table->render();

        $output->writeln('');
        $output->writeln("Diagnóstico Global: <options=bold>{$diag['overall_label']}</>");
        $output->writeln("Fecha de evaluación: {$diag['timestamp']}");
        $output->writeln('<info>===============================================================</info>');

        return Command::SUCCESS;
    }
}