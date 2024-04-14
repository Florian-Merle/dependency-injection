<?php

namespace App;

use App\DependencyInjection\Container as CustomContainer;
use App\DependencyInjection\ContainerBuilder as CustomContainerBuilder;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private CustomContainer $customContainer;

    public function boot(): void
    {
        // before booting the whole kernel, let's build our custom container
        $containerBuilder = new CustomContainerBuilder();
        $this->customContainer = $containerBuilder->buildContainer();

        parent::boot();
    }

    protected function initializeContainer(): void
    {
        parent::initializeContainer();

        // using our custom container, we register its services (instances) into symfony container
        // see https://symfony.com/doc/current/service_container/synthetic_services.html
        foreach ($this->customContainer->getIds() as $id) {
            $this->container->set($id, $this->customContainer->get($id));
        }
    }

    protected function build(ContainerBuilder $container): void
    {
        // because we're registering instances as services in `initializeContainer`
        // those services definition should be synthetic
        // also, I was lazy so I used an anonymous class for the compiler pass
        $container->addCompilerPass(new class($this->customContainer) implements CompilerPassInterface {
            public function __construct(
                private readonly CustomContainer $customContainer
            ) {
            }

            public function process(ContainerBuilder $container): void
            {
                foreach ($this->customContainer->getIds() as $id) {
                    $service = $this->customContainer->get($id);

                    $definition = new Definition(get_class($service));
                    $definition->setSynthetic(true);

                    $container->setDefinition($id, $definition);
                }
            }
        });
    }

    protected function getContainerBuilder(): ContainerBuilder
    {
        $builder = parent::getContainerBuilder();

        // copy parameters from our custom container to symfony container now
        foreach ($this->customContainer->getParameterNames() as $name) {
            $builder->setParameter($name, $this->customContainer->getParameter($name));
        }

        return $builder;
    }
}
