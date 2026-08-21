<?php
use SchemaWeave\Contracts\UrlResolverInterface;

final class SchemaWeave_WordPress_Url_Resolver implements UrlResolverInterface
{
    public function resolve(array $page, array $config): string
    {
        if (!empty($page['url'])) {
            return (string) $page['url'];
        }

        if (!empty($page['id'])) {
            $url = get_permalink((int) $page['id']);
            if ($url) {
                return (string) $url;
            }
        }

        return home_url('/');
    }
}
