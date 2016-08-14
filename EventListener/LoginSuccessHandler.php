<?php

namespace Awaresoft\Sonata\AdminBundle\EventListener;

use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LoginSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $locale = $token->getUser()->getLocale();

        $request->getSession()->set('_locale', $locale);
        $request->setLocale($locale);

        return parent::onAuthenticationSuccess($request, $token);
    }
}