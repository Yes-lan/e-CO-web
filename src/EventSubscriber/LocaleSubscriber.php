<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private string $defaultLocale;

    public function __construct(string $defaultLocale = 'fr')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->query->get('_locale')) {
            $request->setLocale($locale);
            $request->getSession()->set('_locale', $locale);
        } elseif ($locale = $request->attributes->get('_locale')) {
            $request->setLocale($locale);
            $request->getSession()->set('_locale', $locale);
        } else {
            // If no explicit locale has been set on this request, use the session locale
            $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
