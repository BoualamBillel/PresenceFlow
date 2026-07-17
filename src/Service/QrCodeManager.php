<?php

namespace App\Service;

use App\Entity\SessionCours;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Component\Clock\ClockInterface;

/**
 * Gère le cycle de vie du jeton d'émargement QR d'une session
 * et la génération de l'image QR code.
 */
final class QrCodeManager
{
    private const TOKEN_VALIDITY = '+5 minutes';

    public function __construct(private readonly ClockInterface $clock)
    {
    }

    /**
     * Génère un nouveau jeton de sécurité pour l'émargement (valide 5 minutes).
     */
    public function regenerateToken(SessionCours $session): void
    {
        $session->setQrCodeToken(bin2hex(random_bytes(16)));
        $session->setQrTokenExpiresAt($this->clock->now()->modify(self::TOKEN_VALIDITY));
    }

    public function isTokenValid(SessionCours $session): bool
    {
        if (!$session->getQrCodeToken() || !$session->getQrTokenExpiresAt()) {
            return false;
        }

        return $session->getQrTokenExpiresAt() > $this->clock->now();
    }

    public function invalidateToken(SessionCours $session): void
    {
        $session->setQrCodeToken(null);
        $session->setQrTokenExpiresAt(null);
    }

    /**
     * Construit l'image QR code (SVG en data-URI) pointant vers l'URL donnée.
     */
    public function buildDataUri(string $url): string
    {
        $builder = new Builder(
            writer: new SvgWriter(),
            data: $url,
            size: 300,
            margin: 10
        );

        return $builder->build()->getDataUri();
    }
}
