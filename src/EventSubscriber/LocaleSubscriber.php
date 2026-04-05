<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private const ALLOWED = ['fr', 'en', 'ar'];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 127]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (str_starts_with($request->getPathInfo(), '/_')) {
            return;
        }

        $lang = $request->query->get('lang');
        if (\is_string($lang) && \in_array($lang, self::ALLOWED, true) && $request->hasSession()) {
            $request->getSession()->set('_locale', $lang);
        }

        $locale = 'fr';
        if ($request->hasSession()) {
            $s = $request->getSession()->get('_locale');
            if (\is_string($s) && \in_array($s, self::ALLOWED, true)) {
                $locale = $s;
            }
        }

        $request->setLocale($locale);
    }
}
