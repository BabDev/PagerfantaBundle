<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Twig;

use Twig\Error\SyntaxError;

final class UndefinedCallableHandler
{
    /**
     * @var string[]
     */
    private const SUPPORTED_FUNCTIONS = [
        'pagerfanta',
        'pagerfanta_page_url',
    ];

    /**
     * @throws SyntaxError if the undefined function is supported by this handler
     */
    public function onUndefinedFunction(string $name): ?bool
    {
        if (!\in_array($name, self::SUPPORTED_FUNCTIONS, true)) {
            return false;
        }

        throw new SyntaxError(\sprintf('Unknown function "%s". Did you forget to run "composer require pagerfanta/twig"?', $name));
    }
}
