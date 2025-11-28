<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Redirects old URLs without locale prefix to the new localized URLs
 * Routes are defined in config/routes/redirects.yaml to avoid locale prefix
 */
class RedirectController extends AbstractController
{
    public function redirectRoot(): RedirectResponse
    {
        return $this->redirectToRoute('app_parcours_list', ['_locale' => 'fr']);
    }

    public function redirectCourses(): RedirectResponse
    {
        return $this->redirectToRoute('app_courses_list', ['_locale' => 'fr']);
    }

    public function redirectParcours(): RedirectResponse
    {
        return $this->redirectToRoute('app_parcours_list', ['_locale' => 'fr']);
    }

    public function redirectCourseManage(): RedirectResponse
    {
        return $this->redirectToRoute('app_parcours_list', ['_locale' => 'fr']);
    }

    public function redirectCourseCreate(): RedirectResponse
    {
        return $this->redirectToRoute('app_parcours_create', ['_locale' => 'fr']);
    }

    public function redirectLogin(): RedirectResponse
    {
        return $this->redirectToRoute('app_login', ['_locale' => 'fr']);
    }
}
