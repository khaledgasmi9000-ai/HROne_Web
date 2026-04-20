<?php

namespace App\Service;

final class RegistrationEmailVerifier
{
    /**
     * @return list<string>
     */
    public function validateForCandidate(?string $email): array
    {
        return $this->validate($email, false);
    }

    /**
     * @return list<string>
     */
    public function validateForRh(?string $email): array
    {
        return $this->validate($email, true);
    }

    /**
     * @return list<string>
     */
    private function validate(?string $email, bool $requireProfessionalDomain): array
    {
        $email = mb_strtolower(trim((string) $email));

        if ($email === '') {
            return ['L email est obligatoire.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['Veuillez saisir une adresse email valide.'];
        }

        $domain = substr(strrchr($email, '@') ?: '', 1);
        if ($domain === '') {
            return ['Le domaine email est introuvable.'];
        }

        $errors = [];

        if ($this->isDisposableDomain($domain)) {
            $errors[] = 'Les emails temporaires ou jetables ne sont pas acceptes.';
        }

        if ($requireProfessionalDomain && $this->isFreeProviderDomain($domain)) {
            $errors[] = 'Veuillez utiliser une adresse email professionnelle pour creer un compte RH.';
        }

        if (!$this->hasDnsRecord($domain)) {
            $errors[] = 'Le domaine email ne semble pas actif ou ne peut pas recevoir de messages.';
        }

        return $errors;
    }

    private function hasDnsRecord(string $domain): bool
    {
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A') || checkdnsrr($domain, 'AAAA');
    }

    private function isDisposableDomain(string $domain): bool
    {
        return in_array($domain, [
            '10minutemail.com',
            'guerrillamail.com',
            'mailinator.com',
            'temp-mail.org',
            'tempmail.com',
            'yopmail.com',
            'dispostable.com',
            'sharklasers.com',
        ], true);
    }

    private function isFreeProviderDomain(string $domain): bool
    {
        return in_array($domain, [
            'gmail.com',
            'hotmail.com',
            'outlook.com',
            'live.com',
            'yahoo.com',
            'icloud.com',
            'proton.me',
            'protonmail.com',
            'gmx.com',
            'aol.com',
            'yandex.com',
        ], true);
    }
}

