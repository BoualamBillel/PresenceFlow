<?php

namespace App\Tests\Service;

use App\Entity\SessionCours;
use App\Enum\SessionStatut;
use App\Service\SessionStatutCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * Tests unitaire qui vérifie le comportement du service SessionStatutCalculator.
 * Units tests that verify the behavior of the SessionStatutCalculator service. 
 * @covers \App\Service\SessionStatutCalculator
 */
class SessionStatutCalculatorTest extends TestCase
{
    private function makeSession(string $date, string $debut, string $fin): SessionCours
    {
        $session = new SessionCours();
        $session->setDateCours(new \DateTimeImmutable($date));
        $session->setHeureDebut(new \DateTimeImmutable($debut));
        $session->setHeureFin(new \DateTimeImmutable($fin));

        return $session;
    }

    public function testSessionEnCours(): void
    {
        $clock = new MockClock('2026-06-15 10:00:00');
        $calculator = new SessionStatutCalculator($clock);

        $session = $this->makeSession('2026-06-15', '09:00:00', '12:00:00');

        $this->assertSame(SessionStatut::EN_COURS, $calculator->compute($session));
    }

    public function testSessionAVenir(): void
    {
        $clock = new MockClock('2026-06-15 08:00:00');
        $calculator = new SessionStatutCalculator($clock);

        $session = $this->makeSession('2026-06-15', '09:00:00', '12:00:00');

        $this->assertSame(SessionStatut::A_VENIR, $calculator->compute($session));
    }

    public function testSessionTerminee(): void
    {
        $clock = new MockClock('2026-06-15 14:00:00');
        $calculator = new SessionStatutCalculator($clock);

        $session = $this->makeSession('2026-06-15', '09:00:00', '12:00:00');

        $this->assertSame(SessionStatut::TERMINE, $calculator->compute($session));
    }

    public function testSessionDunAutreJour(): void
    {
        $clock = new MockClock('2026-06-15 10:00:00');
        $calculator = new SessionStatutCalculator($clock);

        $session = $this->makeSession('2026-06-16', '09:00:00', '12:00:00');

        $this->assertSame(SessionStatut::A_VENIR, $calculator->compute($session));
    }
}