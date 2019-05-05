<?php

namespace Fesor\RequestObject\Bundle\DependeyInjection;

use Fesor\RequestObject\Bundle\RequestObjectEventListener;
use Fesor\RequestObject\DefaultErrorResponseProvider;
use Fesor\RequestObject\ErrorResponseProvider;
use Fesor\RequestObject\HttpPayloadResolver;
use Fesor\RequestObject\PayloadResolver;
use Fesor\RequestObject\RequestObjectBinder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class RequestObjectExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->registerErrorResponseProvider($configs, $container);
        $this->registerPayloadResolver($container);
        $this->registerRequestBinder($container);
        $this->registerEventListener($container);
    }

    private function registerPayloadResolver(ContainerBuilder $container)
    {
        $definition = new Definition(PayloadResolver::class);
        $definition->setAbstract(true);
        $container->setDefinition('request_object.payload_resolver', $definition);

        $implDefinition = new ChildDefinition('request_object.payload_resolver');
        $implDefinition->setClass(HttpPayloadResolver::class);
        $container->setDefinition('request_object.payload_resolver.http', $implDefinition);

        $container->setAlias(PayloadResolver::class, 'request_object.payload_resolver.http');
    }

    private function registerRequestBinder(ContainerBuilder $container)
    {
        $definition = new Definition(RequestObjectBinder::class, []);
        $definition->setAutowired(true);
        $definition->setPublic(false);
        $container->setDefinition('request_object.request_binder', $definition);
    }

    private function registerEventListener(ContainerBuilder $container)
    {
        $definition = new Definition(RequestObjectEventListener::class, [
            new Reference('request_object.request_binder'),
        ]);
        $definition->addTag('kernel.event_listener', [
            'event' => 'kernel.controller',
            'method' => 'onKernelController',
        ]);

        $container->setDefinition('request_object.event_listener.controller', $definition);
    }

    private function registerErrorResponseProvider(array $configs, ContainerBuilder $container)
    {
        $defaultErrorProvider = @$configs[0]['error_response_provider'];
        if ($defaultErrorProvider !== null) {
            $definition = new Definition($defaultErrorProvider);
            $definition->setAutowired(true);
            $definition->setPublic(false);


            $container->setDefinition('request_object.error_provider.default', $definition);
            $container->setAlias(DefaultErrorResponseProvider::class, 'request_object.error_provider.default');
            $container->setAlias(ErrorResponseProvider::class, DefaultErrorResponseProvider::class);
        }
    }
}
