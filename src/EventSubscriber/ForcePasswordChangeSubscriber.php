<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class ForcePasswordChangeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router,
        private Security $security
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité haute pour bloquer la requête le plus tôt possible
            KernelEvents::REQUEST => ['onKernelRequest', 0], 
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // On ignore les sous-requêtes pour ne pas créer de boucles
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $currentRoute = $request->attributes->get('_route');

        // On définit les routes "ouvertes" (pour éviter une boucle de redirection infinie)
        // On autorise la déconnexion, la barre de debug (_wdt, _profiler) et la route de reset.
        $allowedRoutes = [
            'app_login', 
            'app_logout', 
            'app_force_password_change', 
            '_wdt', 
            '_profiler'
        ];

        if (in_array($currentRoute, $allowedRoutes, true)) {
            return;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        if ($user && $user->isMustChangePassword()) {
            $url = $this->router->generate('app_force_password_change');
            $event->setResponse(new RedirectResponse($url));
        }
    }
}