<?php declare(strict_types=1);

namespace SitemapPlugin\Routing;

use SitemapPlugin\Builder\SitemapBuilderInterface;
use SitemapPlugin\Exception\RouteExistsException;
use SitemapPlugin\Provider\UrlProviderInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class SitemapLoader
 * @package SitemapPlugin\Routing
 * @author Stefan Doorn <stefan@efectos.nl>
 */
class SitemapLoader extends Loader implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var bool
     */
    private $loaded = false;

    /**
     * @var SitemapBuilderInterface
     */
    private $sitemapBuilder;

    /**
     * @param SitemapBuilderInterface $sitemapBuilder
     */
    public function __construct(SitemapBuilderInterface $sitemapBuilder)
    {
        $this->sitemapBuilder = $sitemapBuilder;
    }

    /**
     * @param mixed $resource
     * @param null $type
     * @return RouteCollection
     * @throws RouteExistsException
     */
    public function load($resource, $type = null): RouteCollection
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "extra" loader twice');
        }

        $routes = new RouteCollection();

        foreach ($this->sitemapBuilder->getProviders() as $provider) {
            /** @var UrlProviderInterface $provider */
            $name = 'sylius_sitemap_' . $provider->getName();

            if ($routes->get($name)) {
                throw new RouteExistsException($name);
            }

            $routes->add(
                $name,
                new Route(
                    '/sitemap/' . $provider->getName() . '.{_format}',
                    [
                        '_controller' => 'sylius.controller.sitemap:showAction',
                        'name' => $provider->getName(),
                    ],
                    [
                        '_format' => 'xml',
                    ],
                    [],
                    '',
                    [],
                    ['GET']
                )
            );
        }

        $this->loaded = true;

        return $routes;
    }

    /**
     * @param mixed $resource
     * @param null $type
     * @return bool
     */
    public function supports($resource, $type = null): bool
    {
        return 'sitemap' === $type;
    }
}
