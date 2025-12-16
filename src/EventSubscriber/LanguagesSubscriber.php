<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Repository\LanguageRepository;

class LanguagesSubscriber implements EventSubscriberInterface
{
    private $twig;
    private $languageRepository;
    public function __construct(\Twig\Environment $twig, LanguageRepository $languageRepository)
    {
        $this->twig = $twig;
        $this->languageRepository = $languageRepository;
    }
    public function onControllerEvent(ControllerEvent $event): void
    {
        $activeLanguages = $this->languageRepository->findBy([], ['code' => 'ASC']);
        $this->twig->addGlobal('active_languages', $activeLanguages);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ControllerEvent::class => 'onControllerEvent',
        ];
    }
}
