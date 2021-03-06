<?php

namespace Grossum\CoreBundle\Twig\Extension;

use Locale;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;

/*
 * Returns the current top level/main request route with a new locale
 */

class GrossumTwigSwitchLocaleExtension extends \Twig_Extension
{
    private $request;
    private $router;

    public function __construct(Router $router)
    {
        // Just retrieve the router here
        $this->router = $router;
    }

    /*
     * Listen to the 'kernel.request' event to get the main request, this has several reasons:
     *  - The request can not be injected directly into a Twig extension, this causes a ScopeWideningInjectionException
     *  - Retrieving the request inside of the 'localizeRoute' method might yield us an internal request
     *  - Requesting the request from the container in the constructor breaks the CLI environment (e.g. cache warming)
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequestType() === HttpKernel::MASTER_REQUEST) {
            $this->request = $event->getRequest();
        }
    }

    public function getFunctions()
    {
        return array(
            'localeRoute' => new \Twig_Function_Method($this, 'localizeRoute')
        );
    }

    public function localizeRoute($locale = null)
    {
        // Merge query parameters and route attributes
        $attributes = array_merge($this->request->query->all(), $this->request->attributes->get('_route_params'));

        // Set/override locale
        $attributes['_locale'] = $locale ?: Locale::getDefault();

        return $this->router->generate($this->request->attributes->get('_route'), $attributes);
    }

    public function getName()
    {
        return 'grossum_switch_locale_extension';
    }
}
