# PagerfantaBundle

The PagerfantaBundle is a [Symfony](https://symfony.com/) bundle integrating [Pagerfanta](/open-source/packages/pagerfanta/docs) into an application.
    
This bundle is a continuation of the [`WhiteOctoberPagerfantaBundle`](https://github.com/whiteoctober/WhiteOctoberPagerfantaBundle).

The bundle includes:

- A [Twig](https://twig.symfony.com/) function to render Pagerfanta objects with views and options
- Pagerfanta view which supports Twig templates
- Services to easily use Pagerfanta views anywhere in your application
- Serialization of `Pagerfanta\PagerfantaInterface` objects using the [Symfony Serializer component](https://symfony.com/doc/current/components/serializer.html) or the [JMS Serializer](https://jmsyst.com/libs/serializer)
- A flexible API to customize paginated routing
- Default CSS for the `Pagerfanta\View\DefaultView` class

<div class="docs-note">The documentation covers only the features added by this bundle, please review the Pagerfanta documentation for more information about how to use the library.</div>

Want to see a real-world example of this in a screencast? Check out: https://symfonycasts.com/pagerfanta

## Support Matrix

The below table shows the supported PHP and Symfony versions for this bundle. Note that there is not a 1.x version of this bundle, the previous `WhiteOctoberPagerfantaBundle` should be considered the 1.x version.

| Version | Status                  | PHP Versions | Symfony Versions       |
|---------|-------------------------|--------------|------------------------|
| 2.x     | **No Longer Supported** | 7.2-8.0      | 3.4, 4.4, 5.3-5.4      |
| 3.x     | **No Longer Supported** | 7.4-8.2      | 4.4, 5.4, 6.0-6.4      |
| 4.x     | Actively Supported      | 8.1+         | 5.4, 6.4, 7.3-7.4, 8.0 |
| 5.x     | In Development          | 8.4+         | 6.4, 7.4, 8.0          |

<div class="docs-note">When a maximum PHP version is listed, that is the highest version that is tested for support. The bundle may work on newer PHP versions, but support is not guaranteed.</div>
