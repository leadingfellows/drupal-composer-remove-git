<?php

namespace Leadingfellows\DrupalComposerHelper;


use Composer\Composer;
class Options
{


	private $composer;

	public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

	public function get($key = '')
    {
        $extra = $this->composer->getPackage()->getExtra() + ['drupal-composer-remove-git' => []];
        $extra['drupal-composer-remove-git'] += [
            'web-prefix' => 'web',
            'active' => 'true'
        ];
        return $key ? $extra['drupal-composer-remove-git'][$key] : $extra['drupal-composer-remove-git'];
    }
}
