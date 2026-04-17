<?php

namespace App\Service;

use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class ContractPdfService
{
    public function __construct(
        private readonly DompdfWrapperInterface $dompdfWrapper,
        private readonly Environment $twig,
        private readonly string $projectDir,
    ) {
    }

    /**
     * @param array<string, mixed> $candidate
     */
    public function generateContractPdf(array $candidate): string
    {
        $candidateName = trim((string) ($candidate['name'] ?? 'Candidat'));
        $offerTitle = trim((string) ($candidate['offerTitle'] ?? 'Poste'));
        $salaryMin = $candidate['minSalary'] ?? null;
        $salaryMax = $candidate['maxSalary'] ?? null;

        $html = $this->twig->render('pdf/contract.html.twig', [
            'candidateName' => $candidateName !== '' ? $candidateName : 'Candidat',
            'candidateEmail' => (string) ($candidate['email'] ?? ''),
            'offerTitle' => $offerTitle !== '' ? $offerTitle : 'Poste',
            'workType' => (string) ($candidate['workType'] ?? '-'),
            'location' => (string) ($candidate['location'] ?? '-'),
            'contractType' => (string) ($candidate['contract'] ?? '-'),
            'experience' => (string) ($candidate['experience'] ?? '-'),
            'salaryMin' => $salaryMin,
            'salaryMax' => $salaryMax,
            'generatedAt' => new \DateTimeImmutable('now'),
            'startDate' => (new \DateTimeImmutable('now'))->modify('+7 days'),
        ]);

        $pdfBinary = $this->dompdfWrapper->getPdf($html, [
            'isRemoteEnabled' => true,
            'defaultPaperSize' => 'a4',
        ]);

        $filesystem = new Filesystem();
        $contractsDir = $this->projectDir . '/var/contracts';
        $filesystem->mkdir($contractsDir);

        $safeName = preg_replace('/[^a-z0-9]+/i', '-', strtolower($candidateName)) ?: 'candidat';
        $filename = sprintf('contrat-%s-%d.pdf', $safeName, time());
        $filePath = $contractsDir . '/' . $filename;

        file_put_contents($filePath, $pdfBinary);

        return $filePath;
    }
}
